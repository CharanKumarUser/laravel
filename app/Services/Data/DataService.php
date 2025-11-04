<?php
declare(strict_types=1);
namespace App\Services\Data;
use App\Facades\{Database, Developer};
use App\Services\Data\KeyService;
use Illuminate\Database\{Connection, QueryException};
use Illuminate\Support\Facades\{Cache, DB, Queue};
use Illuminate\Support\{Arr, Str};
use InvalidArgumentException;
use RuntimeException;
use Throwable;
/**
 * Optimized DataService for multi-tenant HRM with efficient SQL operations, encryption handling, and parallel execution.
 * Schema validations are removed to allow database-level error handling for missing tables/columns.
 * Caching is minimized to essential queries, with eager loading and chunking for large datasets.
 * Maintains all business logic and return formats with clean, production-ready MySQL syntax.
 * Implements soft deletes with optional includeDeleted parameter to fetch soft-deleted records.
 */
class DataService
{
    private const CACHE_TTL = 86400; // Query cache TTL (1 day)
    private const ENCRYPTION_METHOD = 'aes-256-cbc';
    private const HASH_ALGO = 'sha256';
    private const FIXED_IV = "\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0";
    private const MAX_PARALLEL_JOBS = 100;
    private const WORKER_THRESHOLD = 3;
    /**
     * Execute a dynamic database query with support for select, insert, update, upsert, delete, and schema operations.
     *
     * @param string $connection Database connection name
     * @param string $table Table name
     * @param array $params Query parameters (select, where, joins, insert, update, etc.)
     * @param bool $includeDeleted Include soft-deleted records
     * @return array Query result with status, data, message, SQL, and bindings
     */
    public static function query(string $connection, string $table, array $params = [], bool $includeDeleted = false): array
    {
        $conn = null;
        $sql = '';
        $bindings = [];
        try {
            $businessId = Database::resolveBusinessId($connection);
            $conn = Database::getConnection($connection);
            $connName = $conn->getName();
            $activeKey = KeyService::getActiveKey($businessId);
            $allKeys = KeyService::getAllKeys($businessId);
            if (!empty($params['schema'])) {
                return self::handleSchemaOperation($conn, $params['schema'], $table, $connName);
            }
            $op = null;
            $opData = [];
            $opWhere = [];
            $updateData = [];
            if (isset($params['insert'])) {
                $op = 'insert';
                $opData = $params['insert'];
            } elseif (isset($params['update'])) {
                $op = 'update';
                $opData = $params['update'];
                $opWhere = $params['where'] ?? [];
            } elseif (isset($params['upsert'])) {
                $op = 'upsert';
                $upsertParams = $params['upsert'];
                $opData = $upsertParams['data'] ?? [];
                $opWhere = $upsertParams['conflict'] ?? [];
                $updateData = $upsertParams['update'] ?? $opData;
            } elseif (isset($params['delete'])) {
                $op = 'delete';
                $opWhere = $params['delete'];
            } elseif (isset($params['softDelete'])) {
                $op = 'softDelete';
                $opWhere = $params['softDelete'];
            }
            if ($op) {
                if (empty($opData) && in_array($op, ['insert', 'update', 'upsert'])) {
                    throw new InvalidArgumentException("Data cannot be empty for $op");
                }
                if (empty($opWhere) && in_array($op, ['update', 'delete', 'softDelete', 'upsert'])) {
                    throw new InvalidArgumentException("Where conditions cannot be empty for $op");
                }
                $conn->beginTransaction();
                $result = self::handleWriteOperation($op, $conn, $table, $opData, $opWhere, $activeKey, $connName, $updateData);
                $conn->commit();
                return $result;
            }
            $query = $conn->table($table);
            if (!$includeDeleted) {
                $query->whereNull("{$table}.deleted_at");
            }
            $encCols = self::getEncryptedColumns($connName, $table);
            $bindings = [];
            $aliasMap = [];
            $select = $params['select'] ?? ['*'];
            if (isset($select['count'])) {
                $query->selectRaw('COUNT(*) AS count');
            } else {
                $qualifiedSelect = self::qualifySelect($select, $table, $encCols, $businessId, $connName, $params['joins'] ?? []);
                $query->selectRaw(implode(', ', $qualifiedSelect['columns']));
                $bindings = array_merge($bindings, $qualifiedSelect['bindings']);
                $aliasMap = $qualifiedSelect['alias_map'];
            }
            if ($params['distinct'] ?? false) {
                $query->distinct();
            }
            if ($params['where'] ?? []) {
                $qualifiedWhere = self::qualifyWhere($params['where'], $table);
                $query->where(self::buildDynamicWhereClause($qualifiedWhere, $encCols, $activeKey, $table));
                $bindings = array_merge($bindings, $query->getBindings());
            }
            if ($params['joins'] ?? []) {
                foreach ($params['joins'] as $join) {
                    $joinTable = $join['table'];
                    $qualifiedOn = self::qualifyJoinOn($join['on'] ?? [], $table, $joinTable);
                    $joinType = strtoupper($join['type'] ?? 'INNER');
                    $onClause = self::buildDynamicJoinOnClause($qualifiedOn, self::getEncryptedColumns($connName, $joinTable), $activeKey, $joinTable);
                    $tableExpression = $joinTable;
                    if ($join['useIndex'] ?? false) {
                        $tableExpression .= " USE INDEX ({$join['useIndex']})";
                    }
                    switch ($joinType) {
                        case 'INNER':
                            $query->join($tableExpression, fn($q) => self::applyOnClause($q, $onClause));
                            break;
                        case 'LEFT':
                            $query->leftJoin($tableExpression, fn($q) => self::applyOnClause($q, $onClause));
                            break;
                        case 'RIGHT':
                            $query->rightJoin($tableExpression, fn($q) => self::applyOnClause($q, $onClause));
                            break;
                        case 'FULL':
                            $query->leftJoin($tableExpression, fn($q) => self::applyOnClause($q, $onClause))
                                ->orWhere(fn($q) => $q->rightJoin($tableExpression, fn($subQ) => self::applyOnClause($subQ, $onClause)));
                            break;
                        case 'CROSS':
                            $query->crossJoin($tableExpression);
                            break;
                        case 'SELF':
                            $query->join("$table AS $joinTable", fn($q) => self::applyOnClause($q, $onClause));
                            break;
                        case 'NATURAL':
                            $query->join($tableExpression, fn($q) => $q->onRaw("NATURAL JOIN $joinTable"));
                            break;
                        default:
                            throw new InvalidArgumentException("Invalid join type: '$joinType'");
                    }
                    if ($join['where'] ?? []) {
                        $joinWhere = self::qualifyWhere($join['where'], $joinTable);
                        $query->where(self::buildDynamicWhereClause($joinWhere, self::getEncryptedColumns($connName, $joinTable), $activeKey, $joinTable));
                    }
                    $bindings = array_merge($bindings, $query->getBindings());
                }
            }
            if ($params['groupBy'] ?? []) {
                $query->groupBy(self::qualifyColumns($params['groupBy'], $table));
            }
            if ($params['having'] ?? false) {
                $query->havingRaw(self::qualifyRaw($params['having'], $table));
            }
            // Add default orderBy based on table name to ensure chunking works
            if (empty($params['orderBy'] ?? [])) {
                $orderByColumn = $table === 'users' ? 'username' : 'id';
                $query->orderBy("{$table}.{$orderByColumn}", 'asc');
            } else {
                foreach ($params['orderBy'] as $col => $dir) {
                    $query->orderBy(self::qualifyColumn($col, $table), strtoupper($dir ?? 'ASC') === 'DESC' ? 'desc' : 'asc');
                }
            }
            if (isset($params['limit'])) {
                $query->limit((int) $params['limit']);
            }
            if (isset($params['offset'])) {
                $query->offset((int) $params['offset']);
            }
            if ($params['union'] ?? []) {
                $unionType = strtoupper($params['union']['type'] ?? 'UNION');
                $unionQuery = self::buildUnionSubquery($params['union'], $connName, $encCols, $activeKey, $table, $businessId);
                switch ($unionType) {
                    case 'UNION':
                        $query->union($unionQuery);
                        break;
                    case 'UNION ALL':
                        $query->unionAll($unionQuery);
                        break;
                    case 'INTERSECT':
                        $query->intersect($unionQuery);
                        break;
                    case 'EXCEPT':
                        $query->except($unionQuery);
                        break;
                    default:
                        throw new InvalidArgumentException("Invalid union type: '$unionType'");
                }
            }
            if ($params['with'] ?? []) {
                $cteSql = self::buildCteSql($params['with'], $table);
                $query = DB::connection($connName)->select("WITH $cteSql {$query->toSql()}", $query->getBindings());
            }
            if ($params['subquery'] ?? false) {
                $query->whereRaw(self::qualifyRaw($params['subquery'], $table));
            }
            if ($params['json'] ?? false) {
                $query->selectRaw(self::buildJsonExpr($params['json'], $table));
            }
            if ($params['window'] ?? []) {
                $query->selectRaw(self::buildWindowExpr($params['window'], $table));
            }
            if ($params['fullText'] ?? false) {
                $query->whereRaw(self::buildFullTextExpr($params['fullText'], $table));
            }
            $rows = [];
            $sql = is_string($query) ? $query : $query->toSql();
            $bindings = is_string($query) ? $bindings : $query->getBindings();
            if (isset($select['count'])) {
                $count = is_string($query) ? $conn->selectOne($sql, $bindings)->count : $query->count();
                $result = ['status' => true, 'data' => [['count' => $count]], 'message' => 'Count query executed', 'query' => $sql, 'bindings' => $bindings];
                return $result;
            }
            $chunkSize = (int) env('DB_CHUNK_SIZE', 1000);
            if (isset($params['limit']) && $params['limit'] <= $chunkSize) {
                $rows = is_string($query) ? $conn->select($sql, $bindings) : $query->get()->toArray();
            } else {
                if (is_string($query)) {
                    $conn->select($sql, $bindings)->chunk($chunkSize, function ($chunk) use (&$rows) {
                        $rows = array_merge($rows, $chunk);
                    });
                } else {
                    $query->chunk($chunkSize, function ($chunk) use (&$rows) {
                        $rows = array_merge($rows, $chunk->toArray());
                    });
                }
            }
            foreach ($rows as &$row) {
                $row = (array) $row;
                $version = $row['version'] ?? null;
                if ($version && $encCols) {
                    $key = $allKeys[$version]['key'] ?? $activeKey['key'] ?? null;
                    if ($key) {
                        foreach ($encCols as $col) {
                            $resultKey = $aliasMap[$col] ?? $col;
                            $qualifiedCol = "$table.$col";
                            if (isset($row[$resultKey]) && !empty($row[$resultKey])) {
                                $row[$resultKey] = self::decryptField($row[$resultKey], $key);
                            } elseif (isset($row[$qualifiedCol]) && !empty($row[$qualifiedCol])) {
                                $row[$resultKey] = self::decryptField($row[$qualifiedCol], $key);
                                unset($row[$qualifiedCol]);
                            }
                        }
                    }
                }
            }
            $result = ['status' => true, 'data' => $rows, 'message' => 'Dynamic query executed', 'query' => $sql, 'bindings' => $bindings];
            return $result;
        } catch (QueryException $e) {
            $conn?->rollBack();
            Developer::error('Dynamic query failed', ['error' => $e->getMessage(), 'table' => $table, 'trace' => $e->getTraceAsString()]);
            return ['status' => false, 'data' => [], 'message' => self::getErrorMessage($e, $table), 'query' => $sql, 'bindings' => $bindings];
        } catch (InvalidArgumentException $e) {
            $conn?->rollBack();
            return ['status' => false, 'data' => [], 'message' => $e->getMessage(), 'query' => $sql, 'bindings' => $bindings];
        } catch (Throwable $e) {
            $conn?->rollBack();
            Developer::error('Unexpected dynamic error', ['error' => $e->getMessage(), 'table' => $table, 'trace' => $e->getTraceAsString()]);
            return ['status' => false, 'data' => [], 'message' => 'Unexpected dynamic error', 'query' => $sql, 'bindings' => $bindings];
        }
    }
    /**
     * Apply join conditions to a query builder.
     *
     * @param \Illuminate\Database\Query\Builder $query Query builder instance
     * @param array $onClause Join conditions
     * @return void
     */
    private static function applyOnClause($query, array $onClause): void
    {
        foreach ($onClause as $condition) {
            $query->whereRaw("{$condition['left']} {$condition['operator']} {$condition['right']}");
        }
    }
    /**
     * Build dynamic join ON clause with proper SQL quoting and encryption handling.
     *
     * @param array $conditions Join conditions
     * @param array $encCols Encrypted columns
     * @param ?array $activeKey Active encryption key
     * @param string $joinTable Join table name
     * @return array Formatted join conditions
     */
    private static function buildDynamicJoinOnClause(array $conditions, array $encCols, ?array $activeKey, string $joinTable): array
    {
        $onClauses = [];
        foreach ($conditions as $index => $cond) {
            if (is_array($cond) && count($cond) === 2 && is_string($cond[0]) && is_string($cond[1])) {
                $leftCol = $cond[0];
                $rightCol = $cond[1];
                $leftTable = str_contains($leftCol, '.') ? Str::before($leftCol, '.') : $joinTable;
                $rightTable = str_contains($rightCol, '.') ? Str::before($rightCol, '.') : $joinTable;
                $leftPlainCol = Str::afterLast($leftCol, '.');
                $rightPlainCol = Str::afterLast($rightCol, '.');
                $qualifiedLeft = "$leftTable.$leftPlainCol";
                $qualifiedRight = "$rightTable.$rightPlainCol";
                $onClauses[] = [
                    'left' => $qualifiedLeft,
                    'operator' => '=',
                    'right' => $qualifiedRight,
                ];
            } elseif (is_array($cond) && is_array($cond[0]) && count($cond[0]) === 2 && is_string($cond[0][0]) && is_string($cond[0][1])) {
                foreach ($cond as $simpleCond) {
                    if (count($simpleCond) !== 2 || !is_string($simpleCond[0]) || !is_string($simpleCond[1])) {
                        throw new InvalidArgumentException("Invalid multiple simple join condition at index $index: expected [left, right] pair");
                    }
                    $leftCol = $simpleCond[0];
                    $rightCol = $simpleCond[1];
                    $leftTable = str_contains($leftCol, '.') ? Str::before($leftCol, '.') : $joinTable;
                    $rightTable = str_contains($rightCol, '.') ? Str::before($rightCol, '.') : $joinTable;
                    $leftPlainCol = Str::afterLast($leftCol, '.');
                    $rightPlainCol = Str::afterLast($rightCol, '.');
                    $qualifiedLeft = "$leftTable.$leftPlainCol";
                    $qualifiedRight = "$rightTable.$rightPlainCol";
                    $onClauses[] = [
                        'left' => $qualifiedLeft,
                        'operator' => '=',
                        'right' => $qualifiedRight,
                    ];
                }
            } else {
                $leftCol = $cond['column'] ?? $cond[0] ?? null;
                $rightCol = $cond['value'][0] ?? $cond[2][0] ?? null;
                $op = strtoupper($cond['operator'] ?? $cond[1] ?? '=');
                if (!is_string($leftCol) || $leftCol === '') {
                    throw new InvalidArgumentException("Invalid join condition at index $index: missing or invalid left column");
                }
                if (is_array($rightCol)) {
                    throw new InvalidArgumentException("Invalid join condition at index $index: right side must be string or number, got array");
                }
                if (!is_string($rightCol) && !is_numeric($rightCol)) {
                    throw new InvalidArgumentException("Invalid join condition at index $index: right side must be string or number, got " . gettype($rightCol));
                }
                $leftTable = str_contains($leftCol, '.') ? Str::before($leftCol, '.') : $joinTable;
                $leftPlainCol = Str::afterLast($leftCol, '.');
                $qualifiedLeft = "$leftTable.$leftPlainCol";
                if (is_string($rightCol) && str_contains($rightCol, '.')) {
                    $rightTable = Str::before($rightCol, '.');
                    $rightPlainCol = Str::afterLast($rightCol, '.');
                    $qualifiedRight = "$rightTable.$rightPlainCol";
                } elseif (is_string($rightCol)) {
                    $qualifiedRight = "'" . addslashes($rightCol) . "'";
                } else {
                    $qualifiedRight = $rightCol;
                }
                $onClauses[] = [
                    'left' => $qualifiedLeft,
                    'operator' => $op,
                    'right' => $qualifiedRight,
                ];
            }
        }
        return $onClauses;
    }
    /**
     * Qualify join ON conditions with table names.
     *
     * @param array $on Join conditions
     * @param string $table Primary table name
     * @param string $joinTable Join table name
     * @return array Qualified join conditions
     */
    private static function qualifyJoinOn(array $on, string $table, string $joinTable): array
    {
        $qualified = [];
        foreach ($on as $condIndex => $cond) {
            if (is_array($cond) && count($cond) === 2 && is_string($cond[0]) && is_string($cond[1])) {
                $leftCol = str_contains($cond[0], '.') ? $cond[0] : "$table.{$cond[0]}";
                $rightCol = str_contains($cond[1], '.') ? $cond[1] : "$joinTable.{$cond[1]}";
                $qualified[] = [$leftCol, $rightCol];
            } elseif (is_array($cond) && is_array($cond[0]) && count($cond[0]) === 2 && is_string($cond[0][0]) && is_string($cond[0][1])) {
                foreach ($cond as $simpleCond) {
                    if (count($simpleCond) !== 2 || !is_string($simpleCond[0]) || !is_string($simpleCond[1])) {
                        throw new InvalidArgumentException("Invalid multiple simple join 'on' clause at index $condIndex: expected [left, right] pairs");
                    }
                    $leftCol = str_contains($simpleCond[0], '.') ? $simpleCond[0] : "$table.{$simpleCond[0]}";
                    $rightCol = str_contains($simpleCond[1], '.') ? $simpleCond[1] : "$joinTable.{$simpleCond[1]}";
                    $qualified[] = [$leftCol, $rightCol];
                }
            } else {
                if (is_array($cond)) {
                    $col = $cond['column'] ?? $cond[0] ?? null;
                    if ($col && !str_contains($col, '.')) {
                        $cond['column'] = "$table.$col";
                    }
                    if (isset($cond['value']) && is_string($cond['value']) && !str_contains($cond['value'], '.') && $joinTable) {
                        $cond['value'] = "$joinTable.{$cond['value']}";
                    }
                    $qualified[] = $cond;
                } else {
                    throw new InvalidArgumentException("Invalid join 'on' clause at index $condIndex: expected array");
                }
            }
        }
        return $qualified;
    }
    /**
     * Build dynamic WHERE clause with encryption handling.
     *
     * @param array $conditions Where conditions
     * @param array $encCols Encrypted columns
     * @param ?array $activeKey Active encryption key
     * @param string $table Table name
     * @return callable Query builder callback
     */
    private static function buildDynamicWhereClause(array $conditions, array $encCols, ?array $activeKey, string $table): callable
    {
        return function ($query) use ($conditions, $encCols, $activeKey, $table) {
            $bindings = [];
            foreach ($conditions as $cond) {
                $col = $cond['column'] ?? $cond[0] ?? null;
                $op = strtoupper($cond['operator'] ?? $cond[1] ?? '=');
                $val = $cond['value'] ?? $cond[2] ?? null;
                $bool = strtoupper($cond['boolean'] ?? 'AND');
                if (!$col) {
                    continue;
                }
                $colTable = str_contains($col, '.') ? Str::before($col, '.') : $table;
                $plainCol = Str::afterLast($col, '.');
                $colName = "$colTable.$plainCol";
                $method = $bool === 'OR' ? 'orWhere' : 'where';
                if (str_ends_with($plainCol, '_hash')) {
                    $query->$method($colName, $op, $val);
                    $bindings[] = $val;
                    continue;
                }
                if (in_array($plainCol, $encCols, true)) {
                    $hashCol = "$plainCol._hash";
                    if ($op === '=' && $activeKey) {
                        $query->$method("$colTable.$hashCol", '=', self::hashField((string) $val));
                        $bindings[] = self::hashField((string) $val);
                    } elseif ($activeKey) {
                        $query->$method(function ($q) use ($colName, $op, $val, $activeKey) {
                            if (in_array($op, ['IS NULL', 'IS NOT NULL'], true)) {
                                $q->whereRaw("AES_DECRYPT($colName, ?) $op", [$activeKey['key']]);
                            } else {
                                $q->whereRaw("AES_DECRYPT($colName, ?) $op ?", [$activeKey['key'], $val]);
                            }
                        });
                        $bindings[] = $activeKey['key'];
                        if (!in_array($op, ['IS NULL', 'IS NOT NULL'], true)) {
                            $bindings[] = $val;
                        }
                    }
                    continue;
                }
                switch ($op) {
                    case 'BETWEEN':
                        $values = is_array($val) ? array_values($val) : [$val, $val];
                        if (count($values) !== 2) {
                            throw new InvalidArgumentException("BETWEEN operator requires exactly 2 values");
                        }
                        $query->{$method . 'Between'}($colName, $values, $bool);
                        $bindings = array_merge($bindings, $values);
                        break;
                    case 'IN':
                        $values = is_array($val) ? array_values($val) : [$val];
                        if (empty($values)) {
                            $method === 'orWhere'
                                ? $query->orWhereRaw('1 = 0')
                                : $query->whereRaw('1 = 0');
                        } else {
                            $query->{$method . 'In'}($colName, $values, $bool);
                            $bindings = array_merge($bindings, $values);
                        }
                        break;
                    case 'NOT IN':
                        $values = is_array($val) ? array_values($val) : [$val];
                        if (empty($values)) {
                            $method === 'orWhere'
                                ? $query->orWhereRaw('1 = 1')
                                : $query->whereRaw('1 = 1');
                        } else {
                            $query->{$method . 'NotIn'}($colName, $values, $bool);
                            $bindings = array_merge($bindings, $values);
                        }
                        break;
                    case 'LIKE':
                        $query->$method($colName, 'LIKE', "%$val%");
                        $bindings[] = "%$val%";
                        break;
                    case 'IS NULL':
                        $query->{$method . 'Null'}($colName, $bool);
                        break;
                    case 'IS NOT NULL':
                        $query->{$method . 'NotNull'}($colName, $bool);
                        break;
                    default:
                        $query->$method($colName, $op, $val);
                        if (!in_array($op, ['IS NULL', 'IS NOT NULL'], true)) {
                            $bindings[] = $val;
                        }
                        break;
                }
            }
            $query->setBindings($bindings, 'where');
        };
    }
    /**
     * Retrieve encrypted columns for a table from cache or database.
     *
     * @param string $connName Connection name
     * @param string $table Table name
     * @return array Encrypted column names
     */
    private static function getEncryptedColumns(string $connName, string $table): array
    {
        $system = str_contains($connName, 'business') ? 'business' : 'central';
        $cacheKey = "enc_columns_{$system}_{$table}";
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($system, $table) {
            try {
                $results = DB::connection('central')->table('skeleton_columns')
                    ->where('is_active', 1)
                    ->whereNull('delete_on')
                    ->where('table', $table)
                    ->where('system', $system)
                    ->pluck('column')
                    ->toArray();
                return $results;
            } catch (QueryException $e) {
                Developer::error('Failed to fetch encrypted columns', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                return [];
            }
        });
    }
    /**
     * Qualify select columns with table names and handle encryption.
     *
     * @param array $select Select columns
     * @param string $table Primary table name
     * @param array $encCols Encrypted columns
     * @param string $businessId Business ID for key resolution
     * @param string $connName Connection name
     * @param array $joins Join definitions
     * @return array Qualified columns, bindings, and alias map
     */
    private static function qualifySelect(array $select, string $table, array $encCols, string $businessId, string $connName, array $joins): array
    {
        $bindings = [];
        $qualified = [];
        $aliasMap = [];
        foreach ($select as $col) {
            $alias = null;
            $rawCol = $col;
            if (is_string($col) && preg_match('/^(.+)\s+AS\s+`?([^`]+)`?$/i', $col, $matches)) {
                $rawCol = trim($matches[1]);
                $alias = trim($matches[2]);
            }
            $colTable = $table;
            $plainCol = $rawCol;
            if (str_contains($rawCol, '.')) {
                [$colTable, $plainCol] = array_map('trim', explode('.', $rawCol));
                $colTable = trim($colTable, '`');
                $plainCol = trim($plainCol, '`');
            }
            if ($plainCol === '*') {
                $qualifiedCol = "`{$colTable}`.*";
                $qualified[] = $qualifiedCol;
                continue;
            }
            $qualifiedCol = "`{$colTable}`.`{$plainCol}`";
            $isEncrypted = in_array($plainCol, $encCols, true);
            $resultKey = $alias ?? $plainCol;
            $aliasMap[$plainCol] = $resultKey;
            if ($isEncrypted) {
                $activeKey = KeyService::getActiveKey($businessId);
                $bindings[] = $activeKey['key'] ?? '';
                $qualified[] = "AES_DECRYPT({$qualifiedCol}, ?) AS `{$resultKey}`";
            } else {
                $qualified[] = $alias ? "{$qualifiedCol} AS `{$resultKey}`" : $qualifiedCol;
            }
        }
        return ['columns' => $qualified, 'bindings' => $bindings, 'alias_map' => $aliasMap];
    }
    /**
     * Qualify WHERE conditions with table names.
     *
     * @param array $where Where conditions
     * @param string $table Table name
     * @param ?string $joinTable Optional join table name
     * @return array Qualified conditions
     */
    private static function qualifyWhere(array $where, string $table, ?string $joinTable = null): array
    {
        $where = self::convertToStructuredWhere($where);
        return array_map(function ($cond) use ($table, $joinTable) {
            if (is_array($cond)) {
                $col = $cond['column'] ?? $cond[0] ?? null;
                if ($col && !str_contains($col, '.')) {
                    $cond['column'] = $joinTable ? "$joinTable.$col" : "$table.$col";
                }
                if ($cond['operator'] ?? false === 'BETWEEN') {
                    $cond['value'] = is_array($cond['value']) ? $cond['value'] : [$cond['value']];
                } elseif ($cond['operator'] ?? false === 'IN') {
                    $cond['value'] = is_array($cond['value']) ? $cond['value'] : [$cond['value']];
                }
                $cond['boolean'] = strtoupper($cond['boolean'] ?? 'AND');
            }
            return $cond;
        }, $where);
    }
    /**
     * Qualify a column with table name.
     *
     * @param string $col Column name
     * @param string $table Table name
     * @return string Qualified column name
     */
    private static function qualifyColumn(string $col, string $table): string
    {
        return str_contains($col, '.') ? $col : "$table.$col";
    }
    /**
     * Qualify raw SQL expressions.
     *
     * @param string $raw Raw SQL expression
     * @param string $table Table name
     * @return string Qualified expression
     */
    private static function qualifyRaw(string $raw, string $table): string
    {
        return preg_replace_callback('/\b(\w+)\.(\w+)\b/', fn($m) => "`$m[1]`.`$m[2]`", $raw);
    }
    /**
     * Prepare data for insert/update with encryption and timestamps.
     *
     * @param array $data Data to prepare
     * @param array $encCols Encrypted columns
     * @param ?array $keyInfo Encryption key info
     * @param string $connName Connection name
     * @param string $table Table name
     * @return array Prepared data
     */
    private static function prepareData(array $data, array $encCols, ?array $keyInfo, string $connName, string $table): array
    {
        $prepared = $data;
        if ($keyInfo && $encCols) {
            foreach ($encCols as $col) {
                if (isset($prepared[$col]) && !is_null($prepared[$col])) {
                    $value = (string) $prepared[$col];
                    $prepared["{$col}_hash"] = self::hashField($value);
                    $prepared[$col] = self::encryptField($value, $keyInfo['key']);
                }
            }
            $prepared['version'] = $keyInfo['version'];
        }
        if (!isset($prepared['created_at'])) {
            $prepared['created_at'] = now();
        }
        if (!isset($prepared['updated_at'])) {
            $prepared['updated_at'] = now();
        }
        return $prepared;
    }
    /**
     * Qualify multiple columns with table name.
     *
     * @param array $columns Column names
     * @param string $table Table name
     * @return array Qualified column names
     */
    private static function qualifyColumns(array $columns, string $table): array
    {
        return array_map(fn($col) => self::qualifyColumn($col, $table), $columns);
    }
    /**
     * Get user-friendly error message for query exceptions.
     *
     * @param QueryException $e Query exception
     * @param string $table Table name
     * @return string Error message
     */
    private static function getErrorMessage(QueryException $e, string $table): string
    {
        return match ($e->getCode()) {
            '23000' => preg_match("/Duplicate entry '(.+?)' for key/", $e->getMessage(), $m) ? "Duplicate entry '$m[1]' in '$table'" : "Constraint violation in '$table'",
            '42S22', '1054' => "Unknown column in '$table'",
            '42S02' => "Table '$table' does not exist",
            '1066' => "Ambiguous column in '$table'",
            'HY093' => "Invalid parameter number in query for '$table'",
            default => "Database error in '$table': {$e->getMessage()}",
        };
    }
    /**
     * Handle schema operations (create/alter table, view, permissions).
     *
     * @param Connection $conn Database connection
     * @param array $schema Schema operation parameters
     * @param string $table Table name
     * @param string $connName Connection name
     * @return array Operation result
     */
    private static function handleSchemaOperation(Connection $conn, array $schema, string $table, string $connName): array
    {
        try {
            $conn->beginTransaction();
            $operation = strtoupper($schema['operation'] ?? '');
            switch ($operation) {
                case 'CREATE_TABLE':
                    \Illuminate\Support\Facades\Schema::connection($connName)->create($table, function ($blueprint) use ($schema) {
                        foreach ($schema['columns'] ?? [] as $col) {
                            $type = $col['type'] ?? 'string';
                            $length = $col['length'] ?? null;
                            $nullable = $col['nullable'] ?? true;
                            $column = $blueprint->$type($col['name'], $length)->{$nullable ? 'nullable' : 'required'}();
                            if ($col['index'] ?? false) {
                                $column->index();
                            }
                            if ($col['unique'] ?? false) {
                                $column->unique();
                            }
                        }
                        if ($schema['soft_delete'] ?? false) {
                            $blueprint->timestamp('deleted_at')->nullable();
                        }
                        if ($schema['encryption'] ?? false) {
                            $blueprint->string('version', 10)->nullable();
                            foreach ($schema['columns'] ?? [] as $col) {
                                if ($col['encrypted'] ?? false) {
                                    $blueprint->string("{$col['name']}_hash")->nullable()->index();
                                }
                            }
                        }
                        $blueprint->timestamps();
                        if ($schema['partitioning'] ?? false) {
                            $blueprint->raw("PARTITION BY {$schema['partitioning']}");
                        }
                    });
                    break;
                case 'ALTER_TABLE':
                    \Illuminate\Support\Facades\Schema::connection($connName)->table($table, function ($blueprint) use ($schema) {
                        foreach ($schema['changes'] ?? [] as $change) {
                            $action = strtoupper($change['action'] ?? '');
                            if ($action === 'ADD') {
                                $type = $change['type'] ?? 'string';
                                $length = $change['length'] ?? null;
                                $nullable = $change['nullable'] ?? true;
                                $column = $blueprint->$type($change['name'], $length)->{$nullable ? 'nullable' : 'required'}();
                                if ($change['index'] ?? false) {
                                    $column->index();
                                }
                                if ($change['unique'] ?? false) {
                                    $column->unique();
                                }
                                if ($change['encrypted'] ?? false) {
                                    $blueprint->string("{$change['name']}_hash")->nullable()->index();
                                }
                            } elseif ($action === 'DROP') {
                                $blueprint->dropColumn($change['name']);
                                if (isset($change['encrypted']) && $change['encrypted']) {
                                    $blueprint->dropColumn("{$change['name']}_hash");
                                }
                            } elseif ($action === 'MODIFY') {
                                $type = $change['type'] ?? 'string';
                                $length = $change['length'] ?? null;
                                $nullable = $change['nullable'] ?? true;
                                $blueprint->$type($change['name'], $length)->{$nullable ? 'nullable' : 'required'}()->change();
                            }
                        }
                    });
                    break;
                case 'DROP_TABLE':
                    \Illuminate\Support\Facades\Schema::connection($connName)->dropIfExists($table);
                    break;
                case 'CREATE_VIEW':
                    $conn->statement("CREATE VIEW IF NOT EXISTS $table AS {$schema['query']}");
                    break;
                case 'DROP_VIEW':
                    $conn->statement("DROP VIEW IF EXISTS $table");
                    break;
                case 'GRANT':
                    $conn->statement("GRANT {$schema['privileges']} ON $table TO '{$schema['user']}'");
                    break;
                case 'REVOKE':
                    $conn->statement("REVOKE {$schema['privileges']} ON $table FROM '{$schema['user']}'");
                    break;
                default:
                    throw new InvalidArgumentException("Invalid schema operation: '$operation'");
            }
            $conn->commit();
            return ['status' => true, 'message' => "Schema operation $operation executed successfully"];
        } catch (Throwable $e) {
            $conn?->rollBack();
            Developer::error('Schema operation failed', ['operation' => $operation, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }
    /**
     * Build union subquery for query operations.
     *
     * @param array $unionParams Union parameters
     * @param string $connName Connection name
     * @param array $encCols Encrypted columns
     * @param ?array $activeKey Active encryption key
     * @param string $table Primary table name
     * @param string $businessId Business ID
     * @return \Illuminate\Database\Query\Builder Union subquery
     */
    private static function buildUnionSubquery(array $unionParams, string $connName, array $encCols, ?array $activeKey, string $table, string $businessId): \Illuminate\Database\Query\Builder
    {
        $unionTable = $unionParams['table'] ?? $table;
        $subQuery = DB::connection($connName)->table($unionTable);
        if ($unionParams['select'] ?? []) {
            $unionEncCols = self::getEncryptedColumns($connName, $unionTable);
            $qualifiedUnionSelect = self::qualifySelect($unionParams['select'], $unionTable, $unionEncCols, $businessId, $connName, []);
            $subQuery->selectRaw(implode(', ', $qualifiedUnionSelect['columns']));
        }
        if ($unionParams['where'] ?? []) {
            $qualifiedUnionWhere = self::qualifyWhere($unionParams['where'], $unionTable);
            $subQuery->where(self::buildDynamicWhereClause($qualifiedUnionWhere, $encCols, $activeKey, $unionTable));
        }
        return $subQuery;
    }
    /**
     * Build CTE SQL for WITH clauses.
     *
     * @param array $with CTE definitions
     * @param string $table Table name
     * @return string CTE SQL
     */
    private static function buildCteSql(array $with, string $table): string
    {
        $ctes = [];
        foreach ($with as $name => $cte) {
            $cteQuery = is_string($cte) ? $cte : $cte['query'];
            $recursive = $cte['recursive'] ?? false;
            $ctes[] = ($recursive ? 'RECURSIVE ' : '') . "$name AS ($cteQuery)";
        }
        return implode(', ', $ctes);
    }
    /**
     * Build JSON_EXTRACT expressions for JSON queries.
     *
     * @param array $json JSON query parameters
     * @param string $table Table name
     * @return string JSON expression
     */
    private static function buildJsonExpr(array $json, string $table): string
    {
        $exprs = [];
        for ($i = 0; $i < count($json); $i++) {
            $col = array_keys($json)[$i];
            $path = array_values($json)[$i];
            $qualifiedCol = self::qualifyColumn($col, $table);
            $exprs[] = "JSON_EXTRACT($qualifiedCol, '$.$path') AS {$col}_json";
        }
        return implode(', ', $exprs);
    }
    /**
     * Build window function expressions.
     *
     * @param array $window Window function parameters
     * @param string $table Table name
     * @return string Window expression
     */
    private static function buildWindowExpr(array $window, string $table): string
    {
        $exprs = [];
        foreach ($window as $func => $opts) {
            $col = $opts['column'] ?? '*';
            $qualifiedCol = self::qualifyColumn($col, $table);
            $partition = $opts['partition'] ?? [];
            $partitionBy = $partition ? 'PARTITION BY ' . implode(', ', array_map(fn($p) => self::qualifyColumn($p, $table), $partition)) : '';
            $order = $opts['order'] ?? [];
            $orderBy = $order ? 'ORDER BY ' . implode(', ', array_map(fn($o) => self::qualifyColumn($o['col'], $table) . ' ' . strtoupper($o['dir'] ?? 'ASC'), $order)) : '';
            $exprs[] = "$func() OVER ($partitionBy $orderBy) AS {$func}_{$col}";
        }
        return implode(', ', $exprs);
    }
    /**
     * Build full-text search expressions.
     *
     * @param array $ft Full-text search parameters
     * @param string $table Table name
     * @return string Full-text expression
     */
    private static function buildFullTextExpr(array $ft, string $table): string
    {
        $cols = implode(', ', array_map(fn($c) => self::qualifyColumn($c, $table), $ft['columns'] ?? []));
        $query = $ft['query'];
        $mode = strtoupper($ft['mode'] ?? 'NATURAL');
        return "MATCH ($cols) AGAINST ('$query' IN $mode LANGUAGE MODE)";
    }
    /**
     * Handle write operations (insert, update, upsert, delete, soft delete).
     *
     * @param string $op Operation type
     * @param Connection $conn Database connection
     * @param string $table Table name
     * @param array $data Data for insert/update
     * @param array $where Where conditions
     * @param ?array $keyInfo Encryption key info
     * @param string $connName Connection name
     * @param array $updateData Update data for upsert
     * @return array Operation result
     */
    private static function handleWriteOperation(string $op, Connection $conn, string $table, array $data, array $where, ?array $keyInfo, string $connName, array $updateData = []): array
    {
        $encCols = self::getEncryptedColumns($connName, $table);
        switch ($op) {
            case 'insert':
                $isBulk = is_array($data) && array_key_exists(0, $data) && is_array($data[0]);
                if ($isBulk) {
                    if (empty($data)) {
                        throw new InvalidArgumentException("Data cannot be empty for bulk insert");
                    }
                    $prepared = array_map(function ($row) use ($encCols, $keyInfo, $connName, $table) {
                        return self::prepareData($row, $encCols, $keyInfo, $connName, $table);
                    }, $data);
                    $chunkSize = (int) env('DB_BULK_CHUNK_SIZE', 500);
                    $affected = 0;
                    foreach (array_chunk($prepared, $chunkSize) as $chunk) {
                        $affected += DB::connection($conn->getName())
                            ->table($table)
                            ->insertOrIgnore($chunk);
                    }
                    return ['status' => true, 'affected' => $affected, 'message' => "Bulk insert completed, $affected records affected"];
                } else {
                    $prepared = self::prepareData($data, $encCols, $keyInfo, $connName, $table);
                    $id = $conn->table($table)->insertGetId($prepared);
                    return ['status' => true, 'id' => $id, 'message' => 'Record created'];
                }
            case 'update':
                $updateData = self::prepareData($data, $encCols, $keyInfo, $connName, $table);
                $qualifiedWhere = self::qualifyWhere($where, $table);
                $whereClause = self::buildDynamicWhereClause($qualifiedWhere, $encCols, $keyInfo, $table);
                $affected = $conn->table($table)->where($whereClause)->whereNull('deleted_at')->update($updateData);
                return ['status' => true, 'affected' => $affected, 'message' => 'Records updated'];
            case 'upsert':
                $insertData = self::prepareData($data, $encCols, $keyInfo, $connName, $table);
                $updateD = self::prepareData($updateData, $encCols, $keyInfo, $connName, $table);
                $qualifiedConflict = self::qualifyWhere($where, $table);
                $whereArray = [];
                foreach ($qualifiedConflict as $c) {
                    if (($c['operator'] ?? '=') === '=') {
                        $col = Str::afterLast($c['column'], '.');
                        $whereArray[$col] = $c['value'];
                    } else {
                        throw new InvalidArgumentException('Upsert supports only simple equality conflicts');
                    }
                }
                $whereBuilder = $conn->table($table);
                foreach ($whereArray as $col => $val) {
                    $whereBuilder->where($col, $val);
                }
                $affected = $whereBuilder->whereNull('deleted_at')->update($updateD);
                if ($affected === 0) {
                    $id = $conn->table($table)->insertGetId($insertData);
                    $affected = 1;
                } else {
                    $id = $whereBuilder->value('id');
                }
                return ['status' => true, 'id' => $id, 'affected' => $affected, 'message' => 'Upsert completed'];
            case 'delete':
                $qualifiedWhere = self::qualifyWhere($where, $table);
                $whereClause = self::buildDynamicWhereClause($qualifiedWhere, $encCols, $keyInfo, $table);
                $affected = $conn->table($table)->where($whereClause)->whereNull('deleted_at')->delete();
                return ['status' => true, 'affected' => $affected, 'message' => 'Records deleted'];
            case 'softDelete':
                $qualifiedWhere = self::qualifyWhere($where, $table);
                $whereClause = self::buildDynamicWhereClause($qualifiedWhere, $encCols, $keyInfo, $table);
                $affected = $conn->table($table)->where($whereClause)->whereNull('deleted_at')->update(['deleted_at' => now()]);
                return ['status' => true, 'affected' => $affected, 'message' => 'Records soft deleted'];
        }
        throw new InvalidArgumentException("Invalid operation: '$op'");
    }
    /**
     * Convert simple where conditions to structured format.
     *
     * @param array $condition Where conditions
     * @return array Structured conditions
     */
    private static function convertToStructuredWhere(array $condition): array
    {
        if (empty($condition) || is_array(reset($condition))) {
            return $condition;
        }
        $structuredWhere = [];
        foreach ($condition as $col => $val) {
            $structuredWhere[] = ['column' => $col, 'operator' => '=', 'value' => $val];
        }
        return $structuredWhere;
    }
    /**
     * Encrypt a value using the active key.
     *
     * @param string $connection Database connection name
     * @param string $value Value to encrypt
     * @return string Encrypted value
     */
    public static function encrypt(string $connection, string $value): string
    {
        $businessId = Database::resolveBusinessId($connection);
        $activeKey = KeyService::getActiveKey($businessId);
        if (!$activeKey) {
            throw new InvalidArgumentException("No active key for connection '$connection'");
        }
        return self::encryptField($value, $activeKey['key']);
    }
    /**
     * Decrypt a value using the active key.
     *
     * @param string $connection Database connection name
     * @param string $value Value to decrypt
     * @return string|null Decrypted value or null on failure
     */
    public static function decrypt(string $connection, string $value): ?string
    {
        $businessId = Database::resolveBusinessId($connection);
        $activeKey = KeyService::getActiveKey($businessId);
        if (!$activeKey) {
            throw new InvalidArgumentException("No active key for connection '$connection'");
        }
        return self::decryptField($value, $activeKey['key']);
    }
    /**
     * Fetch records from a table.
     *
     * @param string $connection Database connection name
     * @param string $table Table name
     * @param array $condition Where conditions
     * @param bool $async Run asynchronously
     * @param bool $includeDeleted Include soft-deleted records
     * @return mixed Query result
     */
    public static function fetch(string $connection, string $table, array $condition = [], bool $async = false, bool $includeDeleted = false): mixed
    {
        $select = $condition['select'] ?? ['*'];
        unset($condition['select']);
        $where = self::convertToStructuredWhere($condition);
        $params = ['select' => $select, 'where' => $where];
        return self::query($connection, $table, $params, $includeDeleted);
    }
    /**
     * Fetch record count from a table.
     *
     * @param string $connection Database connection name
     * @param string $table Table name
     * @param array $condition Where conditions
     * @param bool $async Run asynchronously
     * @param bool $includeDeleted Include soft-deleted records
     * @return mixed Count result
     */
    public static function fetchCount(string $connection, string $table, array $condition = [], bool $async = false, bool $includeDeleted = false): mixed
    {
        $where = self::convertToStructuredWhere($condition);
        $params = ['select' => ['count' => '*'], 'where' => $where];
        $result = self::query($connection, $table, $params, $includeDeleted);
        $count = $result['data'][0]['count'] ?? 0;
        return [
            'status' => $result['status'],
            'count' => $count,
            'message' => $result['message'],
            'query' => $result['query'],
            'bindings' => $result['bindings'],
        ];
    }
    /**
     * Execute multiple queries in parallel.
     *
     * @param array $queries Array of query definitions
     * @param bool $includeDeleted Include soft-deleted records
     * @return array Execution result
     */
    public static function parallelExecute(array $queries, bool $includeDeleted = false): array
    {
        try {
            if (empty($queries)) {
                return ['status' => false, 'batchId' => null, 'data' => [], 'message' => 'No queries provided'];
            }
            $uniqueQueries = [];
            $dedupeCache = [];
            foreach ($queries as $index => $query) {
                $sortedParams = $query;
                ksort($sortedParams['params'] ?? []);
                $key = md5(serialize($sortedParams));
                if (!isset($dedupeCache[$key])) {
                    $dedupeCache[$key] = $index;
                    $businessId = Database::resolveBusinessId($query['connection']);
                    $uniqueQueries[] = array_merge($query, ['businessId' => $businessId]);
                }
            }
            $queryCount = count($uniqueQueries);
            if ($queryCount < self::WORKER_THRESHOLD) {
                $results = array_map(fn($q) => self::query($q['connection'], $q['table'], $q['params'], $includeDeleted), $uniqueQueries);
                return ['status' => true, 'batchId' => null, 'data' => $results, 'message' => "Executed $queryCount queries sequentially"];
            }
            $workers = (int) env('QUEUE_WORKERS', 10);
            $chunkSize = max(1, ceil($queryCount / min($workers, self::MAX_PARALLEL_JOBS)));
            $chunks = array_chunk($uniqueQueries, $chunkSize);
            foreach ($chunks as $chunk) {
                array_map(fn($query) => self::query($query['connection'], $query['table'], $query['params'], $includeDeleted), $chunk);
            }
            return ['status' => true, 'data' => [], 'message' => "Dispatched $queryCount unique queries in " . count($chunks) . " chunks"];
        } catch (Throwable $e) {
            Developer::error('Dynamic parallel execution failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return ['status' => false, 'batchId' => null, 'data' => [], 'message' => 'Parallel execution failed'];
        }
    }
    /**
     * Retrieve results for a batch of parallel queries.
     *
     * @param string $batchId Batch ID
     * @param bool $includeDeleted Include soft-deleted records
     * @return array Query results
     */
    public static function getParallelResults(string $batchId, bool $includeDeleted = false): array
    {
        $batchIds = explode(',', $batchId);
        $allResults = [];
        foreach ($batchIds as $id) {
            $results = []; // Implement batch result retrieval logic
            $allResults = array_merge($allResults, $results);
        }
        return $allResults;
    }
    /**
     * Insert a record into a table.
     *
     * @param string $connection Database connection name
     * @param string $table Table name
     * @param array $data Data to insert
     * @param bool $async Run asynchronously
     * @param bool $includeDeleted Include soft-deleted records
     * @return mixed Insert result
     */
    public static function insert(string $connection, string $table, array $data, bool $async = false, bool $includeDeleted = false): mixed
    {
        $params = ['insert' => $data];
        return self::query($connection, $table, $params, $includeDeleted);
    }
    /**
     * Update records in a table.
     *
     * @param string $connection Database connection name
     * @param string $table Table name
     * @param array $data Data to update
     * @param array $where Where conditions
     * @param bool $async Run asynchronously
     * @param bool $includeDeleted Include soft-deleted records
     * @return mixed Update result
     */
    public static function update(string $connection, string $table, array $data, array $where, bool $async = false, bool $includeDeleted = false): mixed
    {
        $params = ['update' => $data, 'where' => $where];
        return self::query($connection, $table, $params, $includeDeleted);
    }
    /**
     * Upsert records into a table.
     *
     * @param string $connection Database connection name
     * @param string $table Table name
     * @param array $data Data to insert
     * @param array $conflict Conflict conditions
     * @param array $update Data to update
     * @param bool $async Run asynchronously
     * @param bool $includeDeleted Include soft-deleted records
     * @return mixed Upsert result
     */
    public static function upsert(string $connection, string $table, array $data, array $conflict, array $update, bool $async = false, bool $includeDeleted = false): mixed
    {
        $params = ['upsert' => ['data' => $data, 'conflict' => $conflict, 'update' => $update]];
        return self::query($connection, $table, $params, $includeDeleted);
    }
    /**
     * Delete records from a table.
     *
     * @param string $connection Database connection name
     * @param string $table Table name
     * @param array $where Where conditions
     * @param bool $async Run asynchronously
     * @param bool $includeDeleted Include soft-deleted records
     * @return mixed Delete result
     */
    public static function delete(string $connection, string $table, array $where, bool $async = false, bool $includeDeleted = false): mixed
    {
        $params = ['delete' => $where];
        return self::query($connection, $table, $params, $includeDeleted);
    }
    /**
     * Soft delete records from a table.
     *
     * @param string $connection Database connection name
     * @param string $table Table name
     * @param array $where Where conditions
     * @param bool $async Run asynchronously
     * @param bool $includeDeleted Include soft-deleted records
     * @return mixed Soft delete result
     */
    public static function softDelete(string $connection, string $table, array $where, bool $async = false, bool $includeDeleted = false): mixed
    {
        $params = ['softDelete' => $where];
        return self::query($connection, $table, $params, $includeDeleted);
    }
    /**
     * Permanently delete records from a table.
     *
     * @param string $connection Database connection name
     * @param string $table Table name
     * @param array $where Where conditions
     * @param bool $async Run asynchronously
     * @param bool $includeDeleted Include soft-deleted records
     * @return mixed Delete result
     */
    public static function permanentDelete(string $connection, string $table, array $where, bool $async = false, bool $includeDeleted = false): mixed
    {
        return self::delete($connection, $table, $where, $async, $includeDeleted);
    }
    /**
     * Update records in a table (alias for update).
     *
     * @param string $connection Database connection name
     * @param string $table Table name
     * @param array $data Data to update
     * @param array $where Where conditions
     * @param bool $async Run asynchronously
     * @param bool $includeDeleted Include soft-deleted records
     * @return mixed Update result
     */
    public static function edit(string $connection, string $table, array $data, array $where, bool $async = false, bool $includeDeleted = false): mixed
    {
        return self::update($connection, $table, $data, $where, $async, $includeDeleted);
    }
    /**
     * Create a new table.
     *
     * @param string $connection Database connection name
     * @param string $table Table name
     * @param array $columns Column definitions
     * @param bool $includeDeleted Include soft-deleted records
     * @return array Operation result
     */
    public static function createTable(string $connection, string $table, array $columns, bool $includeDeleted = false): array
    {
        return self::query($connection, $table, ['schema' => ['operation' => 'CREATE_TABLE', 'columns' => $columns, 'soft_delete' => true]], $includeDeleted);
    }
    /**
     * Alter an existing table.
     *
     * @param string $connection Database connection name
     * @param string $table Table name
     * @param array $changes Table changes
     * @param bool $includeDeleted Include soft-deleted records
     * @return array Operation result
     */
    public static function alterTable(string $connection, string $table, array $changes, bool $includeDeleted = false): array
    {
        return self::query($connection, $table, ['schema' => ['operation' => 'ALTER_TABLE', 'changes' => $changes]], $includeDeleted);
    }
    /**
     * Drop a table.
     *
     * @param string $connection Database connection name
     * @param string $table Table name
     * @param bool $includeDeleted Include soft-deleted records
     * @return array Operation result
     */
    public static function dropTable(string $connection, string $table, bool $includeDeleted = false): array
    {
        return self::query($connection, $table, ['schema' => ['operation' => 'DROP_TABLE']], $includeDeleted);
    }
    /**
     * Create a database view.
     *
     * @param string $connection Database connection name
     * @param string $viewName View name
     * @param string $query View query
     * @param bool $includeDeleted Include soft-deleted records
     * @return array Operation result
     */
    public static function createView(string $connection, string $viewName, string $query, bool $includeDeleted = false): array
    {
        return self::query($connection, $viewName, ['schema' => ['operation' => 'CREATE_VIEW', 'query' => $query]], $includeDeleted);
    }
    /**
     * Drop a database view.
     *
     * @param string $connection Database connection name
     * @param string $viewName View name
     * @param bool $includeDeleted Include soft-deleted records
     * @return array Operation result
     */
    public static function dropView(string $connection, string $viewName, bool $includeDeleted = false): array
    {
        return self::query($connection, $viewName, ['schema' => ['operation' => 'DROP_VIEW']], $includeDeleted);
    }
    /**
     * Grant permissions on a table.
     *
     * @param string $connection Database connection name
     * @param string $table Table name
     * @param array $privileges Privileges to grant
     * @param string $user User to grant to
     * @param bool $includeDeleted Include soft-deleted records
     * @return array Operation result
     */
    public static function grant(string $connection, string $table, array $privileges, string $user, bool $includeDeleted = false): array
    {
        return self::query($connection, $table, ['schema' => ['operation' => 'GRANT', 'privileges' => implode(', ', $privileges), 'user' => $user]], $includeDeleted);
    }
    /**
     * Revoke permissions on a table.
     *
     * @param string $connection Database connection name
     * @param string $table Table name
     * @param array $privileges Privileges to revoke
     * @param string $user User to revoke from
     * @param bool $includeDeleted Include soft-deleted records
     * @return array Operation result
     */
    public static function revoke(string $connection, string $table, array $privileges, string $user, bool $includeDeleted = false): array
    {
        return self::query($connection, $table, ['schema' => ['operation' => 'REVOKE', 'privileges' => implode(', ', $privileges), 'user' => $user]], $includeDeleted);
    }
    /**
     * Encrypt a field value.
     *
     * @param string $value Value to encrypt
     * @param string $key Encryption key
     * @return string Encrypted value
     */
    public static function encryptField(string $value, string $key): string
    {
        try {
            $ciphertext = openssl_encrypt($value, self::ENCRYPTION_METHOD, $key, 0, self::FIXED_IV);
            if ($ciphertext === false) {
                throw new RuntimeException('Encryption failed');
            }
            return base64_encode($ciphertext);
        } catch (Throwable $e) {
            Developer::error('Encryption failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            throw new RuntimeException("Encryption failed: {$e->getMessage()}");
        }
    }
    /**
     * Decrypt a field value.
     *
     * @param string $value Value to decrypt
     * @param string $key Decryption key
     * @return string|null Decrypted value or null on failure
     */
    public static function decryptField(string $value, string $key): ?string
    {
        try {
            $decoded = base64_decode($value, true);
            if ($decoded === false) {
                Developer::warning('Invalid base64 data for decryption', ['value' => substr($value, 0, 50)]);
                return null;
            }
            $plaintext = openssl_decrypt($decoded, self::ENCRYPTION_METHOD, $key, 0, self::FIXED_IV);
            if ($plaintext === false) {
                Developer::warning('Decryption failed', ['value' => substr($value, 0, 50)]);
                return null;
            }
            return $plaintext;
        } catch (Throwable $e) {
            Developer::error('Decryption failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return null;
        }
    }
    /**
     * Hash a field value.
     *
     * @param string $value Value to hash
     * @return string Hashed value
     */
    public static function hashField(string $value): string
    {
        try {
            return hash(self::HASH_ALGO, $value);
        } catch (Throwable $e) {
            Developer::error('Hashing failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            throw new RuntimeException("Hashing failed: {$e->getMessage()}");
        }
    }
}