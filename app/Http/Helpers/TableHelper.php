<?php
declare(strict_types=1);
namespace App\Http\Helpers;
use App\Facades\{Data, Database, Developer};
use Illuminate\Support\Facades\{Cache, Schema, Facade};
use Illuminate\Support\Str;
use InvalidArgumentException;
/**
 * Optimized TableHelper for processing table data and metadata for DataTables with improved performance.
 * Handles aliased columns by mapping original column names from definitions to row data keys.
 * Supports all custom views, actions, checkboxes, and post-processing without query interference.
 */
class TableHelper
{
    private const CACHE_TTL = 7200;
    /**
     * @var array<string, array{icon: string, suffix: string}>
     */
    private const ACTION_CONFIG = [
        'e' => ['icon' => '<i class="fa fa-edit"></i>', 'suffix' => '_e_'],
        'v' => ['icon' => '<i class="fa fa-eye"></i>', 'suffix' => '_v_'],
        'd' => ['icon' => '<i class="fa fa-trash"></i>', 'suffix' => '_d_'],
    ];
    /**
     * Processes table data with custom column transformations and action buttons.
     *
     * @param array $data Data rows from Data::query
     * @param array $columns Column definitions (e.g., ['id' => ['table.id', bool], 'approval' => ['table.is_approved AS approval', bool]])
     * @param array $custom Custom column definitions (modify/addon)
     * @param array $reqSet Request settings including actions (e.g., 'cevd')
     * @return array Processed data for DataTables
     * @throws InvalidArgumentException
     */
    public static function processData(array $data, array $columns, array $custom, array $reqSet): array
    {
        if (!isset($reqSet['token'], $reqSet['act'])) {
            throw new InvalidArgumentException('Missing token or action column in request settings');
        }
        $validColumns = array_keys($columns);
        $hiddenColumns = array_filter(array_keys($columns), fn($col) => isset($columns[$col][1]) && $columns[$col][1] === false);
        $modifyColumns = [];
        $addonColumns = [];
        // Pre-process custom definitions
        foreach ($custom as $customDef) {
            if (empty($customDef['type']) || empty($customDef['column']) || empty($customDef['view'])) {
                Developer::warning('Invalid custom column definition', ['custom' => $customDef]);
                continue;
            }
            $type = $customDef['type'];
            $column = $customDef['column'];
            if ($type === 'modify' && in_array($column, $validColumns, true)) {
                $modifyColumns[$column][] = $customDef;
            } elseif ($type === 'addon') {
                $addonColumns[$column] = $customDef;
            }
        }
        $action = $reqSet['actions'] ?? '';
        $showCheckboxes = str_contains($action, 'c');
        $actionButtons = array_filter(
            self::ACTION_CONFIG,
            fn(string $key): bool => str_contains($action, $key),
            ARRAY_FILTER_USE_KEY
        );
        $baseToken = preg_replace('/^((?:[^_]*_){3}[^_]*)_.*/', '$1', $reqSet['token']);
        return array_values(array_filter(array_map(function ($row) use ($modifyColumns, $addonColumns, $validColumns, $showCheckboxes, $actionButtons, $reqSet, $hiddenColumns, $baseToken, $columns) {
            if (!is_array($row)) {
                Developer::warning('Invalid row data, expected array', ['row' => $row]);
                return null;
            }
            $mappedRow = [];
            foreach ($columns as $displayName => $columnDef) {
                if (!is_array($columnDef) || empty($columnDef[0])) {
                    Developer::warning('Invalid column definition', ['displayName' => $displayName, 'columnDef' => $columnDef]);
                    continue;
                }
                // Extract original column name (strip AS alias if present)
                $originalCol = Str::before($columnDef[0], ' AS ');
                $plainCol = Str::afterLast($originalCol, '.');
                $colKey = str_replace('.', '_', $plainCol); // e.g., 'is_approved'
                // Get value from row using original column key
                $value = $row[$colKey] ?? null;
                // Skip if column is hidden
                if (isset($columnDef[1]) && $columnDef[1] === false) {
                    continue;
                }
                // Apply custom modifications if any
                if (isset($modifyColumns[$displayName])) {
                    foreach ($modifyColumns[$displayName] as $customDef) {
                        $value = self::renderView($customDef['view'], $row, array_keys($columns), $customDef['renderHtml'] ?? false);
                    }
                }
                $mappedRow[$displayName] = $value ?? ''; // Use displayName as key (e.g., 'approval')
            }
            // Add addon columns
            foreach ($addonColumns as $column => $customDef) {
                $mappedRow[$column] = self::renderView($customDef['view'], $row, array_keys($columns), $customDef['renderHtml'] ?? false);
            }
            // Add checkboxes if enabled
            if ($showCheckboxes) {
                $rowId = (string) ($row[$reqSet['act']] ?? '');
                $mappedRow['selection'] = sprintf('<input type="checkbox" class="row-select skl-checkbox form-check-input" data-id="%s">', htmlspecialchars($rowId, ENT_QUOTES, 'UTF-8'));
            }
            // Add action buttons if enabled
            if ($actionButtons) {
                $menuItems = '';
                $rowId = $row[$reqSet['act']] ?? '';
                foreach ($actionButtons as $act => $config) {
                    $menuItems .= sprintf('<button type="button" class="%s skeleton-popup" data-token="%s">%s</button>', htmlspecialchars($act, ENT_QUOTES, 'UTF-8'), htmlspecialchars($baseToken . $config['suffix'] . $rowId, ENT_QUOTES, 'UTF-8'), $config['icon']);
                }
                $mappedRow['actions'] = sprintf('<div class="table-actions-group">%s</div>', $menuItems);
            }
            return $mappedRow;
        }, $data), fn($row) => $row !== null));
    }
    /**
     * Generates column metadata for DataTables table view.
     *
     * @param array $columns Column definitions
     * @param array $reqSet Request settings
     * @param array $custom Custom column definitions
     * @return array Column metadata
     */
    public static function generateColumnMeta(array $columns, array $reqSet, array $custom): array
    {
        $action = $reqSet['actions'] ?? '';
        $showCheckboxes = str_contains($action, 'c');
        $showActions = str_contains($action, 'e') || str_contains($action, 'v') || str_contains($action, 'd');
        $modifyColumns = array_filter($custom, fn($c) => ($c['type'] ?? '') === 'modify');
        $addonColumns = array_filter($custom, fn($c) => ($c['type'] ?? '') === 'addon');
        $meta = [];
        if ($showCheckboxes) {
            $meta[] = [
                'data' => 'selection',
                'name' => 'selection',
                'title' => '<input type="checkbox" class="form-check-input skl-checkbox select-all-checkbox">',
                'orderable' => false,
                'searchable' => false,
                'visible' => true,
                'width' => 'auto',
                'className' => 'dt-checkbox',
                'isDate' => false,
                'renderHtml' => true,
            ];
        }
        foreach ($columns as $displayName => $columnDef) {
            if (!is_array($columnDef) || empty($columnDef[0])) {
                Developer::warning('Invalid column definition', ['displayName' => $displayName, 'columnDef' => $columnDef]);
                continue;
            }
            $dbColumn = $columnDef[0];
            $isVisible = $columnDef[1] ?? true;
            if (!$isVisible) {
                continue;
            }
            // Extract display name for column (use displayName if aliased)
            $colName = $displayName;
            // Check if renderHtml is needed (from custom)
            $renderHtml = array_reduce($modifyColumns, fn($carry, $customDef) => $carry || ($customDef['column'] === $displayName && ($customDef['renderHtml'] ?? false)), false);
            $meta[] = [
                'data' => $colName,
                'name' => $dbColumn,
                'title' => Str::title(str_replace('_', ' ', $colName)),
                'orderable' => true,
                'searchable' => true,
                'visible' => true,
                'width' => 'auto',
                'className' => 'dt-left skl-pop',
                'isDate' => in_array($colName, ['created_at', 'updated_at'], true),
                'renderHtml' => $renderHtml,
            ];
        }
        foreach ($addonColumns as $column) {
            if (!in_array($column, array_column($meta, 'data'))) {
                $meta[] = [
                    'data' => $column['column'],
                    'name' => $column['column'],
                    'title' => Str::title(str_replace('_', ' ', $column['column'])),
                    'orderable' => false,
                    'searchable' => false,
                    'visible' => true,
                    'width' => 'auto',
                    'className' => 'dt-left',
                    'isDate' => false,
                    'renderHtml' => $column['renderHtml'] ?? false,
                ];
            }
        }
        if ($showActions) {
            $meta[] = [
                'data' => 'actions',
                'name' => 'actions',
                'title' => 'Actions',
                'orderable' => false,
                'searchable' => false,
                'visible' => true,
                'width' => 'auto',
                'className' => 'dt-actions',
                'isDate' => false,
                'renderHtml' => true,
            ];
        }
        return $meta;
    }
    /**
     * Renders a view string with optimized placeholder replacement and condition evaluation.
     *
     * @param string $view View template
     * @param array $row Data row
     * @param array $validColumns Valid column names
     * @param bool $renderHtml Whether to render as HTML
     * @return string Rendered view
     */
    public static function renderView(string $view, array $row, array $validColumns, bool $renderHtml = false): string
    {
        $output = $view;
        $output = preg_replace_callback('/::MATH\((.+?)\)::/s', fn($matches) => self::evaluateMath(trim($matches[1]), $row, $validColumns), $output);
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
        $output = preg_replace_callback(
            '/::~([A-Z][a-zA-Z]*|(?:[A-Za-z\\\\]+\\\\[A-Za-z]+))->(\w+)\(([^)]*)\)(?:->(\w+)\(([^)]*)\))?~::/',
            fn($matches) => self::callMethod(
                $matches[1],
                $matches[2] ?? '',
                self::parseParameters($matches[3] ?? '', $row, $validColumns),
                $matches[4] ?? '',
                self::parseParameters($matches[5] ?? '', $row, $validColumns),
                $row
            ),
            $output
        );
        $output = preg_replace_callback(
            '/::([A-Z][a-zA-Z]*)::(\w+)\(([^)]*)\)::/',
            fn($matches) => self::callMethod($matches[1], $matches[2], self::parseParameters($matches[3], $row, $validColumns), '', [], $row, true),
            $output
        );
        $output = preg_replace_callback('/::([\w\.]+)::/', function ($matches) use ($row, $validColumns) {
            $column = $matches[1];
            $originalCol = Str::before($column, ' AS '); // Strip AS if present
            $plainCol = Str::afterLast($originalCol, '.');
            $colKey = str_replace('.', '_', $plainCol);
            return in_array($column, $validColumns) && isset($row[$colKey]) ? self::handleBase64Data($row[$colKey], '', '') : '';
        }, $output);
        $output = str_replace(' + ', ' ', $output);
        return $renderHtml ? $output : htmlspecialchars($output, ENT_QUOTES, 'UTF-8');
    }
    /**
     * Evaluates a mathematical expression with row values.
     *
     * @param string $expr Mathematical expression
     * @param array $row Data row
     * @param array $validColumns Valid column names
     * @return string Calculated result
     */
    private static function evaluateMath(string $expr, array $row, array $validColumns): string
    {
        $expr = preg_replace_callback('/::([\w\.]+)::/', function ($matches) use ($row, $validColumns) {
            $column = $matches[1];
            $originalCol = Str::before($column, ' AS '); // Strip AS
            $plainCol = Str::afterLast($originalCol, '.');
            $colKey = str_replace('.', '_', $plainCol);
            return in_array($column, $validColumns) && isset($row[$colKey]) && is_numeric($row[$colKey]) ? $row[$colKey] : '0';
        }, $expr);
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
     * Evaluates a condition string for transformations.
     *
     * @param string $condition Condition string
     * @param array $row Data row
     * @param array $validColumns Valid column names
     * @return bool Result of condition evaluation
     */
    public static function evaluateCondition(string $condition, array $row, array $validColumns): bool
    {
        $condition = trim($condition);
        if (preg_match('/^\((.+)\)$/s', $condition, $matches)) {
            return self::evaluateCondition($matches[1], $row, $validColumns);
        }
        if (preg_match_all('/(.+?)\s+(AND|OR)\s+/s', $condition, $matches, PREG_OFFSET_CAPTURE)) {
            $parts = [];
            $operators = [];
            $lastOffset = 0;
            foreach ($matches[0] as $idx => $match) {
                $parts[] = trim(substr($condition, $lastOffset, $match[1] - $lastOffset + strlen($match[0]) - strlen($matches[2][$idx][0]) - 1));
                $operators[] = $matches[2][$idx][0];
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
        if (preg_match('/([\w\.]+)\s*IN\s*\[\s*([^]]*?)\s*\]/i', $condition, $matches)) {
            $column = $matches[1];
            $originalCol = Str::before($column, ' AS '); // Strip AS
            $plainCol = Str::afterLast($originalCol, '.');
            $colKey = str_replace('.', '_', $plainCol);
            $values = array_filter(array_map('trim', explode(',', $matches[2])));
            return in_array($column, $validColumns) && isset($row[$colKey]) && in_array((string) $row[$colKey], $values, false);
        }
        if (preg_match('/([\w\.]+)\s*IS\s*(NOT\s*)?NULL/i', $condition, $matches)) {
            $column = $matches[1];
            $originalCol = Str::before($column, ' AS '); // Strip AS
            $plainCol = Str::afterLast($originalCol, '.');
            $colKey = str_replace('.', '_', $plainCol);
            $isNotNull = !empty($matches[2]);
            $rowValue = $row[$colKey] ?? null;
            return in_array($column, $validColumns) ? ($isNotNull ? ($rowValue !== null && $rowValue !== '') : ($rowValue === null || $rowValue === '')) : false;
        }
        if (preg_match('/([\w\.]+)\s*(=|>|<|!=|LIKE)\s*[\'"]?([^\'"]*)[\'"]?/i', $condition, $matches)) {
            $column = $matches[1];
            $operator = strtoupper($matches[2]);
            $value = $matches[3];
            $originalCol = Str::before($column, ' AS '); // Strip AS
            $plainCol = Str::afterLast($originalCol, '.');
            $colKey = str_replace('.', '_', $plainCol);
            if (in_array($column, $validColumns) && isset($row[$colKey])) {
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
        }
        Developer::warning('Invalid condition format', ['condition' => $condition]);
        return false;
    }
    /**
     * Generates response parameters for DataTables.
     *
     * @param array $set Set for DataService containing columns, conditions, joins, req_set, custom
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
            // Validate table and system
            if (!$table) {
                throw new InvalidArgumentException('Table not specified in req_set');
            }
            // Resolve connection if business_id is provided
            if ($business_id) {
                $system = $business_id;
            }
            // Build select clause from $set['columns'] (preserve AS aliases for query)
            $select = [];
            foreach ($set['columns'] ?? [] as $key => $col) {
                $columnName = $col[0]; // e.g., 'skeleton_modules.is_approved AS approval'
                $select[] = $columnName; // Use fully qualified with AS
            }
            // Build query parameters
            $params = [
                'select' => $select ?: ['*'],
                'where' => $set['conditions'] ?? [],
                'joins' => $set['joins'] ?? [],
            ];
            // Apply filters from req_set
            $filters = $reqSet['filters'] ?? [];
            $search = $filters['search'] ?? [];
            $dateRange = $filters['dateRange'] ?? [];
            $sort = $filters['sort'] ?? [];
            $pagination = $filters['pagination'] ?? ['type' => 'offset', 'page' => 1, 'limit' => 10];
            // Add search conditions (OR across visible columns)
            if (!empty($search)) {
                $searchWhere = [];
                foreach ($set['columns'] as $key => $col) {
                    if ($col[1] && isset($search[$key]) && $search[$key] !== '') {
                        $searchWhere[] = [
                            'column' => $col[0],
                            'operator' => 'LIKE',
                            'value' => '%' . $search[$key] . '%',
                            'boolean' => 'OR',
                        ];
                    }
                }
                if (!empty($searchWhere)) {
                    $params['where'] = array_merge($params['where'], $searchWhere);
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
                foreach ($sort as $s) {
                    if (isset($s['column'], $s['dir']) && in_array($s['column'], array_keys($set['columns']))) {
                        $dbCol = $set['columns'][$s['column']][0]; // Full col with AS
                        $params['orderBy'][$dbCol] = strtoupper($s['dir']) === 'DESC' ? 'DESC' : 'ASC';
                    }
                }
            }
            // Add pagination
            if (isset($pagination['type']) && $pagination['type'] === 'offset') {
                $params['limit'] = (int) ($pagination['limit'] ?? 10);
                $params['offset'] = (int) (($pagination['page'] ?? 1) - 1) * $params['limit'];
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
            // Process data with custom modifications
            $columnMeta = self::generateColumnMeta($set['columns'], $reqSet, $set['custom'] ?? []);
            $processedData = self::processData($result['data'], $set['columns'], $set['custom'] ?? [], $reqSet);
            return [
                'status' => $result['status'] ?? true,
                'draw' => (int) ($reqSet['draw'] ?? 1),
                'data' => $processedData,
                'columns' => $columnMeta,
                'recordsTotal' => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'message' => $result['message'] ?? (empty($processedData) ? 'No records found' : 'Records fetched successfully')
            ];
        } catch (InvalidArgumentException $e) {
            return [
                'status' => false,
                'draw' => (int) ($reqSet['draw'] ?? 1),
                'data' => [],
                'columns' => [],
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'message' => $e->getMessage()
            ];
        } catch (\Throwable $e) {
            return [
                'status' => false,
                'draw' => (int) ($reqSet['draw'] ?? 1),
                'data' => [],
                'columns' => [],
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'message' => 'Unexpected error: ' . $e->getMessage()
            ];
        }
    }
    /**
     * Calls a method on a class or facade with optional chaining.
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
        $fallback = fn() => $method1 === 'getFile' ? asset('default/preview-square.svg') : '2';
        try {
            if (strpos($classOrFacade, '\\') !== false || $isLegacy) {
                $class = $isLegacy ? ($classOrFacade === 'Carbon' ? 'Carbon\Carbon' : ($classOrFacade === 'DB' ? 'Illuminate\Support\Facades\DB' : "App\\Facades\\$classOrFacade")) : $classOrFacade;
                if (!class_exists($class)) {
                    Developer::warning('Class does not exist', ['class' => $class, 'method' => $method1]);
                    return $fallback();
                }
                $instance = $classOrFacade === 'Carbon' ? call_user_func_array("$class::$method1", $params1) : ($classOrFacade === 'DB' ? call_user_func_array([\Illuminate\Support\Facades\DB::getFacadeRoot(), $method1], $params1) : call_user_func_array([app($class), $method1], $params1));
            } else {
                $instance = Facade::resolveFacadeInstance($classOrFacade);
                if (!$instance || !method_exists($instance, $method1)) {
                    Developer::warning('Facade method does not exist', ['facade' => $classOrFacade, 'method' => $method1]);
                    return $fallback();
                }
                $instance = call_user_func_array([$instance, $method1], $params1);
            }
            return ($method2 && is_object($instance) && method_exists($instance, $method2)) ? call_user_func_array([$instance, $method2], $params2) : $instance;
        } catch (\Exception $e) {
            Developer::warning('Error calling method', ['classOrFacade' => $classOrFacade, 'method' => $method1, 'error' => $e->getMessage()]);
            return $fallback();
        }
    }
    /**
     * Parses parameters for method calls.
     *
     * @param string $rawParams Raw parameter string
     * @param array $row Data row
     * @param array $validColumns Valid column names
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
        $escaped = false;
        for ($i = 0; $i < strlen($rawParams); $i++) {
            $char = $rawParams[$i];
            if ($char === '\\' && !$escaped) {
                $escaped = true;
                $current .= $char;
                continue;
            }
            if (($char === '"' || $char === "'") && !$escaped) {
                if ($inQuotes && $char === $quoteChar) {
                    $inQuotes = false;
                    $quoteChar = null;
                } elseif (!$inQuotes) {
                    $inQuotes = true;
                    $quoteChar = $char;
                }
                $current .= $char;
            } elseif (in_array($char, ['(', '[']) && !$inQuotes) {
                $depth[$char]++;
                $current .= $char;
            } elseif (in_array($char, [')', ']']) && !$inQuotes) {
                $depth[$char === ')' ? '(' : '[']--;
                $current .= $char;
            } elseif ($char === ',' && !$inQuotes && $depth['('] === 0 && $depth['['] === 0) {
                $params[] = trim($current);
                $current = '';
            } else {
                $current .= $char;
            }
            $escaped = false;
        }
        if (trim($current) !== '') {
            $params[] = trim($current);
        }
        return array_map(function ($param) use ($row, $validColumns) {
            if (preg_match('/^::~([A-Z][a-zA-Z]*|(?:[A-Za-z\\\\]+\\\\[A-Za-z]+))->(\w+)\(([^)]*)\)(?:->(\w+)\(([^)]*)\))?~::$/', $param, $m)) {
                return self::renderView("::~{$m[1]}->{$m[2]}({$m[3]})" . (isset($m[4]) ? "->{$m[4]}({$m[5]})" : '') . "~::", $row, $validColumns, true);
            }
            if (preg_match('/^::([\w\.]+)::/', $param, $m)) {
                $column = $m[1];
                $originalCol = Str::before($column, ' AS '); // Strip AS if present
                $plainCol = Str::afterLast($originalCol, '.');
                $colKey = str_replace('.', '_', $plainCol);
                return in_array($column, $validColumns) && isset($row[$colKey]) ? self::handleBase64Data($row[$colKey], '', '') : '';
            }
            if (preg_match('/^[\'"]?(.*?)[\'"]?$/', $param, $m)) {
                $val = $m[1];
                return is_numeric($val) ? (strpos($val, '.') !== false ? (float) $val : (int) $val) : $val;
            }
            return $param;
        }, $params);
    }
    /**
     * Handles base64 data for various file types.
     *
     * @param mixed $data Data to process
     * @param string $methodName Method name
     * @param string $classOrFacade Class or facade name
     * @return mixed Processed data
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
            return $methodName === 'getFile' ? asset('default/preview-square.svg') : '1';
        }
        return match (true) {
            str_starts_with($mime, 'image/') => $data,
            str_starts_with($mime, 'audio/') => "<audio controls src=\"$data\"></audio>",
            $mime === 'application/pdf' => "<embed src=\"$data\" type=\"application/pdf\" width=\"100%\" height=\"600px\" />",
            str_starts_with($mime, 'video/') => "<video controls src=\"$data\"></video>",
            default => "<a href=\"$data\" download=\"file." . match ($mime) {
                'text/plain' => 'txt',
                'application/json' => 'json',
                'text/csv' => 'csv',
                default => 'dat',
            } . "\">Download file</a>",
        };
    }
}