<?php
declare(strict_types=1);
namespace App\Http\Helpers;
use App\Facades\{Data, Developer};
use Illuminate\Support\Facades\{Facade, Log};
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;
/**
 * Helper class for generating card data parameters and responses, integrated with Data.
 */
class CardHelper
{
   /**
     * Generates response parameters for DataTables.
     *
     * @param array $set Set for DataService containing columns, conditions, joins, req_set, view
     * @param string|null $business_id Optional business ID; if null, resolved from connection
     * @return array DataTables-compatible response
     * @throws InvalidArgumentException
     */
    public static function generateResponse(array $set, ?string $business_id = null): array
    {
        try {
            // Extract table and connection from $set['req_set']
            $reqSet = $set['req_set'] ?? [];
            $table = $reqSet['table'] ?? null;
            $system = $reqSet['system'] ?? 'central'; // Default to 'central' if not specified

            // Validate table
            if (!$table) {
                throw new InvalidArgumentException('Table not specified in req_set');
            }

            // Resolve connection if business_id is provided
            if ($business_id) {
                $system = $business_id;
            }

            // Build select clause from $set['columns'] (preserve AS aliases for query)
            $select = [];
            $columnMap = [];
            foreach ($set['columns'] ?? [] as $key => $col) {
                $columnName = $col[0]; // e.g., 'roles.id'
                $select[] = $columnName; // Use fully qualified with AS
                $columnMap[$key] = $columnName; // Map DataTables column key to DB column
            }

            // Build query parameters
            $params = [
                'select' => $select ?: ['*'],
                'where' => $set['conditions'] ?? [],
                'joins' => $set['joins'] ?? [],
            ];

            // Apply filters from req_set
            $filters = $reqSet['filters'] ?? [];
            $search = $filters['search'] ?? '';
            $dateRange = $filters['dateRange'] ?? [];
            $sort = $filters['sort'] ?? [];
            $pagination = $filters['pagination'] ?? ['type' => 'offset', 'page' => 1, 'limit' => 10];

            // Validate and add global search conditions (nested OR clause)
            if (!empty($search) && is_string($search)) {
                $searchWhere = [];
                foreach ($set['columns'] as $key => $col) {
                    if ($col[1]) { // Check if column is searchable
                        $searchWhere[] = [
                            'column' => $col[0],
                            'operator' => 'LIKE',
                            'value' => $search, // Pass raw search string
                            'boolean' => 'OR',
                        ];
                    }
                }
                if (!empty($searchWhere)) {
                    // Nest search conditions in an OR group
                    $params['where'][] = [
                        'boolean' => 'AND',
                        'nested' => $searchWhere, // Nested OR conditions
                    ];
                }
            }

            // Add date range conditions
            if (!empty($dateRange) && isset($dateRange['column'], $dateRange['start'], $dateRange['end'])) {
                $params['where'][] = [
                    'column' => $dateRange['column'],
                    'operator' => 'BETWEEN',
                    'value' => [$dateRange['start'], $dateRange['end']],
                    'boolean' => 'AND',
                ];
            }

            // Add sorting
            if (!empty($sort)) {
                $params['orderBy'] = [];
                foreach ($sort as $column => $dir) {
                    if (isset($columnMap[$column])) {
                        $dbCol = $columnMap[$column]; // Use mapped DB column
                        $params['orderBy'][$dbCol] = strtoupper($dir) === 'DESC' ? 'DESC' : 'ASC';
                    }
                }
            }

            // Add pagination
            if (isset($pagination['type']) && $pagination['type'] === 'offset') {
                $params['limit'] = (int) ($pagination['limit'] ?? 10);
                $params['offset'] = (int) (($pagination['page'] ?? 1) - 1) * $params['limit'];
            }

            // Validate conditions
            foreach ($params['where'] as $condition) {
                if (isset($condition['nested'])) {
                    foreach ($condition['nested'] as $nestedCondition) {
                        if (!isset($nestedCondition['column'], $nestedCondition['operator'], $nestedCondition['value'])) {
                            throw new InvalidArgumentException('Invalid nested condition format');
                        }
                        if ($nestedCondition['operator'] === 'LIKE' && !is_string($nestedCondition['value'])) {
                            throw new InvalidArgumentException('LIKE operator requires a string value');
                        }
                    }
                } elseif (isset($condition['operator'], $condition['value'])) {
                    if (in_array($condition['operator'], ['IN', 'NOT IN']) && !is_array($condition['value'])) {
                        throw new InvalidArgumentException('IN/NOT IN operator requires an array value');
                    }
                    if ($condition['operator'] === 'LIKE' && !is_string($condition['value'])) {
                        throw new InvalidArgumentException('LIKE operator requires a string value');
                    }
                }
            }

            // Execute main query
            $result = Data::query($system, $table, $params);

            // Fetch total records (without filters)
            $totalParams = [
                'select' => ['count' => '*'],
            ];
            $totalResult = Data::query($system, $table, $totalParams);
            $recordsTotal = $totalResult['data'][0]['count'] ?? 0;

            // Fetch filtered records count (with search/dateRange/sort but no pagination)
            $filteredParams = $params;
            unset($filteredParams['limit'], $filteredParams['offset']);
            $filteredParams['select'] = ['count' => '*'];
            $filteredResult = Data::query($system, $table, $filteredParams);
            $recordsFiltered = $filteredResult['data'][0]['count'] ?? 0;

            // Prepare valid columns for rendering
            $validColumns = array_keys($set['columns']);

            // Render view for each row
            $data = array_map(
                function (array $row) use ($set, $reqSet, $validColumns): string {
                    // Extract column names without table prefix for rendering
                    $rowData = [];
                    foreach ($row as $key => $value) {
                        $columnName = Str::afterLast($key, '.'); // Remove table prefix
                        $rowData[$columnName] = $value;
                    }
                    return self::renderView(
                        str_replace('::skeletonToken::', $reqSet['token'] ?? '', $set['view']),
                        $rowData,
                        $validColumns,
                        true
                    );
                },
                $result['data'] ?? []
            );

            // Prepare columns metadata
            $columns = array_map(
                function (string $key) use ($set): array {
                    return [
                        'data' => $key,
                        'title' => ucfirst(str_replace('_', ' ', $key)),
                        'searchable' => $set['columns'][$key][1] ?? false,
                        'orderable' => true,
                        'renderHtml' => $set['columns'][$key][1] ?? false,
                    ];
                },
                array_keys($set['columns'])
            );

            return [
                'status' => $result['status'] ?? true,
                'draw' => (int) ($reqSet['draw'] ?? 1),
                'data' => $data,
                'columns' => $columns,
                'recordsTotal' => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'message' => $result['message'] ?? (empty($data) ? 'No records found' : 'Records fetched successfully'),
            ];
        } catch (InvalidArgumentException $e) {
            return [
                'status' => false,
                'draw' => (int) ($reqSet['draw'] ?? 1),
                'data' => [],
                'columns' => [],
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'message' => $e->getMessage(),
            ];
        } catch (Throwable $e) {
            return [
                'status' => false,
                'draw' => (int) ($reqSet['draw'] ?? 1),
                'data' => [],
                'columns' => [],
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'message' => 'Unexpected error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Evaluates a condition string against a data row.
     *
     * @param string $condition The condition to evaluate
     * @param array $row Data row
     * @param array<string> $validColumns Valid column names
     * @return bool Result of the condition evaluation
     */
    public static function evaluateCondition(string $condition, array $row, array $validColumns): bool
    {
        $condition = trim($condition);
        // Handle parenthesized conditions
        if (preg_match('/^\((.+)\)$/s', $condition, $matches)) {
            return self::evaluateCondition($matches[1], $row, $validColumns);
        }
        // Handle logical operators (AND, OR)
        if (preg_match_all('/(.+?)\s+(AND|OR)\s+/s', $condition, $logicalMatches, PREG_OFFSET_CAPTURE)) {
            $parts = [];
            $operators = [];
            $lastOffset = 0;
            foreach ($logicalMatches[0] as $idx => $match) {
                $parts[] = trim(substr($condition, $lastOffset, $match[1] - $lastOffset + strlen($match[0]) - strlen($logicalMatches[2][$idx][0]) - 1));
                $operators[] = $logicalMatches[2][$idx][0];
                $lastOffset = $match[1] + strlen($match[0]);
            }
            $parts[] = trim(substr($condition, $lastOffset));
            $result = self::evaluateCondition($parts[0], $row, $validColumns);
            foreach ($operators as $i => $op) {
                $nextResult = self::evaluateCondition($parts[$i + 1], $row, $validColumns);
                $result = $op === 'AND' ? ($result && $nextResult) : ($result || $nextResult);
            }
            return $result;
        }
        // Handle array condition: column IN [value1, value2, ...]
        if (preg_match('/([\w\.]+)\s*IN\s*\[\s*([^]]*?)\s*\]/i', $condition, $matches)) {
            $column = $matches[1];
            $values = array_filter(array_map('trim', explode(',', $matches[2])));
            $colKey = str_replace('.', '_', $column);
            if (in_array($column, $validColumns, true) && array_key_exists($colKey, $row)) {
                return in_array((string) $row[$colKey], $values, false);
            }
            Developer::warning('Invalid column in IN condition', ['column' => $column, 'colKey' => $colKey, 'condition' => $condition, 'validColumns' => $validColumns]);
            return false;
        }
        // Handle IS NULL or IS NOT NULL
        if (preg_match('/([\w\.]+)\s*IS\s*(NOT\s*)?NULL/i', $condition, $matches)) {
            $column = $matches[1];
            $isNotNull = !empty($matches[2]);
            $colKey = str_replace('.', '_', $column);
            if (in_array($column, $validColumns, true)) {
                $rowValue = $row[$colKey] ?? null;
                return $isNotNull ? ($rowValue !== null && $rowValue !== '') : ($rowValue === null || $rowValue === '');
            }
            Developer::warning('Invalid column in IS NULL condition', ['column' => $column, 'colKey' => $colKey, 'condition' => $condition, 'validColumns' => $validColumns]);
            return false;
        }
        // Handle simple condition: column operator value
        if (preg_match('/([\w\.]+)\s*(=|>|<|!=|LIKE)\s*[\'"]?([^\'"]*)[\'"]?/i', $condition, $matches)) {
            $column = $matches[1];
            $operator = strtoupper($matches[2]);
            $value = $matches[3];
            $colKey = str_replace('.', '_', $column);
            if (in_array($column, $validColumns, true) && array_key_exists($colKey, $row)) {
                $rowValue = $row[$colKey];
                return match ($operator) {
                    '=' => $rowValue == $value,
                    '!=' => $rowValue != $value,
                    '>' => is_numeric($rowValue) && is_numeric($value) && $rowValue > $value,
                    '<' => is_numeric($rowValue) && is_numeric($value) && $rowValue < $value,
                    'LIKE' => stripos((string) $rowValue, str_replace(['%', '_'], '', $value)) !== false,
                    default => false,
                };
            }
            Developer::warning('Column missing in row for condition', ['column' => $column, 'colKey' => $colKey, 'condition' => $condition, 'validColumns' => $validColumns]);
            return false;
        }
        Developer::warning('Invalid condition format', ['condition' => $condition, 'row' => $row, 'validColumns' => $validColumns]);
        return false;
    }
    /**
     * Evaluates a mathematical expression, replacing placeholders with row values.
     *
     * @param string $expr Mathematical expression
     * @param array $row Data row
     * @param array<string> $validColumns Valid column names
     * @return string Calculated result or empty string on error
     */
    private static function evaluateMath(string $expr, array $row, array $validColumns): string
    {
        $expr = preg_replace_callback(
            '/::([\w\.]+)::/',
            fn(array $matches): string => (
                in_array($matches[1], $validColumns, true) &&
                array_key_exists(str_replace('.', '_', $matches[1]), $row) &&
                is_numeric($row[str_replace('.', '_', $matches[1])])
            ) ? $row[str_replace('.', '_', $matches[1])] : (
                Developer::warning('Invalid or non-numeric column in math expression', [
                    'column' => $matches[1],
                    'colKey' => str_replace('.', '_', $matches[1]),
                    'validColumns' => $validColumns,
                    'row' => $row
                ]) || '0'
            ),
            $expr
        );
        if (!preg_match('/^[\d\s\+\-\*\/\(\)\.]+$/', $expr)) {
            Developer::warning('Invalid characters in math expression', ['expr' => $expr]);
            return '';
        }
        try {
            return (string) eval("return $expr;");
        } catch (\Throwable $e) {
            Developer::warning('Error evaluating math expression', ['expr' => $expr, 'error' => $e->getMessage()]);
            return '';
        }
    }
    /**
     * Renders a view string by replacing placeholders, evaluating conditions, and calling facade or class methods.
     *
     * @param string $view View template with placeholders, conditions, or facade/class calls
     * @param array $row Data row
     * @param array<string> $validColumns Valid column names
     * @param bool $renderHtml Whether to render as HTML (skip escaping)
     * @return string Rendered view
     */
    public static function renderView(string $view, array $row, array $validColumns, bool $renderHtml = false): string
    {
        $output = $view;
        // Handle mathematical calculations
        $output = preg_replace_callback(
            '/::MATH\((.+?)\)::/s',
            fn(array $matches): string => self::evaluateMath(trim($matches[1]), $row, $validColumns),
            $output
        );
        // Handle IF/ELSEIF/ELSE conditions
        $pattern = '/::IF\(([^,]+?),\s*([^,]+?)(?:,\s*([^)]+?))?\)::((?:ELSEIF\(([^,]+?),\s*([^,]+?)(?:,\s*([^)]+?))?\)::)*)?(?:ELSE\(([^)]*?)\)::)?/';
        while (preg_match($pattern, $output, $matches)) {
            $conditions = [['condition' => trim($matches[1]), 'value' => trim($matches[2]), 'elseValue' => trim($matches[3] ?? '')]];
            if (!empty($matches[4])) {
                preg_match_all('/ELSEIF\(([^,]+?),\s*([^,]+?)(?:,\s*([^)]+?))?\)::/', $matches[4], $elseifMatches, PREG_SET_ORDER);
                foreach ($elseifMatches as $elseif) {
                    $conditions[] = ['condition' => trim($elseif[1]), 'value' => trim($elseif[2]), 'elseValue' => trim($elseif[3] ?? '')];
                }
            }
            $elseValue = trim($matches[8] ?? end($conditions)['elseValue']);
            $result = $elseValue;
            foreach ($conditions as $cond) {
                if (self::evaluateCondition($cond['condition'], $row, $validColumns)) {
                    $result = $cond['value'];
                    break;
                }
            }
            $output = str_replace($matches[0], self::renderView($result, $row, $validColumns, $renderHtml), $output);
        }
        // Handle complex facade/class method calls
        $output = preg_replace_callback(
            '/::~([A-Z][a-zA-Z]*|(?:[A-Za-z\\\\]+\\\\[A-Za-z]+))(?:->(\w+)\(([^)]*)\)(?:->(\w+)\(([^)]*)\))?)?~::/',
            fn(array $matches): string => (string) self::handleBase64Data(
                self::callMethod(
                    $matches[1],
                    $matches[2] ?? '',
                    self::parseParameters($matches[3] ?? '', $row, $validColumns),
                    $matches[4] ?? '',
                    self::parseParameters($matches[5] ?? '', $row, $validColumns),
                    $row
                ),
                $matches[2] ?? '',
                $matches[1]
            ),
            $output
        );
        // Handle legacy facade method calls
        $output = preg_replace_callback(
            '/::([A-Z][a-zA-Z]*)::(\w+)\(([^)]*)\)::/',
            fn(array $matches): string => (string) self::handleBase64Data(
                self::callMethod($matches[1], $matches[2], self::parseParameters($matches[3], $row, $validColumns), '', [], $row, true),
                $matches[2],
                $matches[1]
            ),
            $output
        );
        // Replace column placeholders
        $output = preg_replace_callback(
            '/::([\w\.]+)::/',
            fn(array $matches): string => (
                in_array($matches[1], $validColumns, true) &&
                array_key_exists(str_replace('.', '_', $matches[1]), $row)
            ) ? (string) self::handleBase64Data($row[str_replace('.', '_', $matches[1])], '', '') : (
                Developer::warning('Invalid column placeholder', [
                    'column' => $matches[1],
                    'colKey' => str_replace('.', '_', $matches[1]),
                    'validColumns' => $validColumns,
                    'row' => $row
                ]) || ''
            ),
            $output
        );
        $output = str_replace(' + ', ' ', $output);
        // Check for unparsed patterns
        if (preg_match('/::~(?:[A-Z][a-zA-Z]*|[A-Za-z\\\\]+\\\\[A-Za-z]+)->\w+\([^)]*\)~::/', $output) || preg_match('/::IF\(/', $output)) {
            Developer::warning('Unparsed function call or condition in final output', [
                'output' => substr($output, 0, 100) . '...',
                'view' => substr($view, 0, 100) . '...',
                'row' => $row,
                'token' => $row['token'] ?? 'unknown',
            ]);
        }
        return $renderHtml ? $output : htmlspecialchars($output, ENT_QUOTES, 'UTF-8');
    }
    /**
     * Calls a method on a class or facade, with optional chaining.
     *
     * @param string $classOrFacade Class or facade name
     * @param string $method1 First method name
     * @param array $params1 Parameters for first method
     * @param string $method2 Second method name (optional)
     * @param array $params2 Parameters for second method (optional)
     * @param array $row Data row
     * @param bool $isLegacy Whether to handle legacy facade calls
     * @return mixed Method result
     */
    private static function callMethod(string $classOrFacade, string $method1, array $params1, string $method2, array $params2, array $row, bool $isLegacy = false): mixed
    {
        $fallback = fn() => $method1 === 'getFile' ? asset('default/preview-square.svg') : '';
        try {
            if (strpos($classOrFacade, '\\') !== false || $isLegacy) {
                $class = $isLegacy ? ($classOrFacade === 'Carbon' ? 'Carbon\Carbon' : ($classOrFacade === 'DB' ? 'Illuminate\Support\Facades\DB' : "App\\Facades\\$classOrFacade")) : $classOrFacade;
                if (!class_exists($class)) {
                    Developer::warning('Class does not exist', ['class' => $class, 'method' => $method1, 'params' => $params1, 'row' => $row]);
                    return $fallback();
                }
                $instance = $classOrFacade === 'Carbon' ? call_user_func_array("$class::$method1", $params1) : (
                    $classOrFacade === 'DB' ? call_user_func_array([\Illuminate\Support\Facades\DB::getFacadeRoot(), $method1], $params1) : call_user_func_array([app($class), $method1], $params1)
                );
            } else {
                $instance = Facade::resolveFacadeInstance($classOrFacade);
                if (!$instance || !method_exists($instance, $method1)) {
                    Developer::warning('Facade method does not exist or instance not resolved', ['facade' => $classOrFacade, 'method' => $method1, 'params' => $params1, 'row' => $row]);
                    return $fallback();
                }
                $instance = call_user_func_array([$instance, $method1], $params1);
            }
            return ($method2 && is_object($instance) && method_exists($instance, $method2)) ? call_user_func_array([$instance, $method2], $params2) : $instance;
        } catch (\Exception $e) {
            Developer::warning('Error calling method', ['classOrFacade' => $classOrFacade, 'method' => $method1, 'params' => $params1, 'error' => $e->getMessage(), 'row' => $row]);
            return $fallback();
        }
    }
    /**
     * Parses parameters for facade or class method calls, handling nested calls, column placeholders, and commas within base64 data.
     *
     * @param string $rawParams Raw parameter string
     * @param array $row Data row
     * @param array<string> $validColumns Valid column names
     * @return array Processed parameters
     */
    private static function parseParameters(string $rawParams, array $row, array $validColumns): array
    {
        if (empty(trim($rawParams))) {
            return [];
        }
        $params = [];
        $current = '';
        $inQuotes = false;
        $quoteChar = null;
        $depth = ['(' => 0, '[' => 0];
        $inBase64 = false;
        for ($i = 0; $i < strlen($rawParams); $i++) {
            $char = $rawParams[$i];
            if ($char === '\\' && $i + 1 < strlen($rawParams) && in_array($rawParams[$i + 1], ['"', "'"])) {
                $current .= $rawParams[++$i];
                continue;
            }
            if (($char === '"' || $char === "'") && ($i === 0 || $rawParams[$i - 1] !== '\\')) {
                if ($inQuotes && $char === $quoteChar) {
                    $inQuotes = false;
                    $quoteChar = null;
                } elseif (!$inQuotes) {
                    $inQuotes = true;
                    $quoteChar = $char;
                }
                $current .= $char;
            } elseif (!$inQuotes && !$inBase64 && substr($rawParams, $i, 5) === 'data:') {
                $inBase64 = true;
                $current .= $char;
            } elseif (in_array($char, ['(', '[']) && !$inQuotes && !$inBase64) {
                $depth[$char]++;
                $current .= $char;
            } elseif (in_array($char, [')', ']']) && !$inQuotes && !$inBase64) {
                $depth[$char === ')' ? '(' : '[']--;
                $current .= $char;
            } elseif ($char === ',' && !$inQuotes && !$inBase64 && $depth['('] === 0 && $depth['['] === 0) {
                $params[] = trim($current);
                $current = '';
            } elseif ($inBase64 && !$inQuotes && $depth['('] === 0 && $depth['['] === 0 && $char === ',' && ($i + 1 >= strlen($rawParams) || !preg_match('/[a-zA-Z0-9\/+;=]/', $rawParams[$i + 1]))) {
                $inBase64 = false;
                $current .= $char;
            } else {
                $current .= $char;
            }
        }
        if (trim($current) !== '') {
            $params[] = trim($current);
        }
        return array_map(
            fn(string $param): mixed => (
                preg_match('/^::~([A-Z][a-zA-Z]*|(?:[A-Za-z\\\\]+\\\\[A-Za-z]+))->(\w+)\(([^)]*)\)(?:->(\w+)\(([^)]*)\))?~::$/', $param, $m) ?
                self::renderView("::~{$m[1]}->{$m[2]}({$m[3]})" . (isset($m[4]) ? "->{$m[4]}({$m[5]})" : '') . "~::", $row, $validColumns, true) : (
                    preg_match('/^::([\w\.]+)::/', $param, $m) &&
                    in_array($m[1], $validColumns, true) &&
                    array_key_exists(str_replace('.', '_', $m[1]), $row) ?
                    self::handleBase64Data($row[str_replace('.', '_', $m[1])], '', '') : (
                        preg_match('/^[\'"]?(.*?)[\'"]?$/', $param, $m) && is_numeric($m[1]) ?
                        (strpos($m[1], '.') !== false ? (float) $m[1] : (int) $m[1]) :
                        $m[1] ?? $param
                    )
                )
            ),
            $params
        );
    }
    /**
     * Handles base64 data by converting it to appropriate HTML src attributes for various file types.
     *
     * @param mixed $data The data to process
     * @param string $methodName The method name that produced the data
     * @param string $classOrFacade The class or facade name
     * @return mixed Processed data or HTML element
     */
    private static function handleBase64Data(mixed $data, string $methodName, string $classOrFacade): mixed
    {
        if (!is_string($data) || !preg_match('/^data:([a-zA-Z\/]+);base64,(.+)$/', $data, $matches)) {
            return is_object($data) ? '' : $data;
        }
        $mime = $matches[1];
        $b64 = $matches[2];
        if (!base64_decode($b64, true)) {
            Developer::warning('Invalid base64 data', ['method' => $methodName, 'classOrFacade' => $classOrFacade, 'mimeType' => $mime]);
            return $methodName === 'getFile' ? asset('default/preview-square.svg') : '';
        }
        return match (true) {
            str_starts_with($mime, 'image/') => "data:$mime;base64,$b64",
            str_starts_with($mime, 'audio/') => "<audio controls src=\"data:$mime;base64,$b64\"></audio>",
            $mime === 'application/pdf' => "<embed src=\"data:$mime;base64,$b64\" type=\"application/pdf\" width=\"100%\" height=\"600px\" />",
            str_starts_with($mime, 'video/') => "<video controls src=\"data:$mime;base64,$b64\"></video>",
            default => "<a href=\"data:$mime;base64,$b64\" download=\"file." . match ($mime) {
                'text/plain' => 'txt',
                'application/json' => 'json',
                'text/csv' => 'csv',
                default => 'dat',
            } . "\">Download file</a>",
        };
    }
}
