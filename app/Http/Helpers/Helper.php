<?php
namespace App\Http\Helpers;
use App\Facades\{Data, Developer, Skeleton, BusinessDB, FileManager, CentralDB};
use InvalidArgumentException;
use RuntimeException;
use Exception;
use stdClass;
use Illuminate\Support\Carbon;
/**
 * Helper class for handling common data operations with robust exception handling.
 * Includes profile string generation, JSON manipulation, table rendering, OTP operations, and value retrieval.
 */
class Helper
{
    /****************************************************************************************************
     *                                                                                                  *
     *                             >>> Database Operations (START) <<<                                   *
     *                                                                                                  *
     ****************************************************************************************************/
    /**
     * Fetch data from a specified table with selected columns and conditions.
     * Returns empty array or JSON array on error.
     *
     * @param string $table Table name
     * @param array|string $columns Columns to select ('all' or array of column names)
     * @param array $condition Where conditions (e.g., ['where' => ['status' => 'active'], 'search' => 'term'])
     * @param string $output Output format ('array' or 'json')
     * @return array|string Fetched data in the specified format or empty array/JSON on error
     */
    public static function fetch(string $table, $columns, array $condition, string $output, ?string $strict = null)
    {
        try {
            if (empty($table)) {
                if (config('skeleton.developer_mode')) {
                    Developer::error('Helper: Error fetching data', [
                        'table' => $table,
                        'error' => 'Table name is required.',
                    ]);
                }
                return $output === 'json' ? '[]' : [];
            }
            if (!in_array($output, ['array', 'json'], true)) {
                if (config('skeleton.developer_mode')) {
                    Developer::error('Helper: Error fetching data', [
                        'table' => $table,
                        'error' => 'Invalid output format. Must be "array" or "json".',
                    ]);
                }
                return $output === 'json' ? '[]' : [];
            }
            if (!is_array($columns) && $columns !== 'all') {
                if (config('skeleton.developer_mode')) {
                    Developer::error('Helper: Error fetching data', [
                        'table' => $table,
                        'error' => 'Columns must be "all" or an array of column names.',
                    ]);
                }
                return $output === 'json' ? '[]' : [];
            }
            if ($strict) {
                $system = 'central';
            } else {
                $system = Skeleton::getUserSystem();
            }
            $data = Data::fetch($system, $table, $condition, 'all');
            $results = [];
            foreach ($data['data'] ?? [] as $row) {
                $item = [];
                if ($columns === 'all') {
                    foreach ((array)$row as $key => $value) {
                        $item[$key] = htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
                    }
                } else {
                    foreach ($columns as $col) {
                        if (!is_string($col) || empty($col)) {
                            continue;
                        }
                        $item[$col] = array_key_exists($col, (array)$row) ? htmlspecialchars((string)$row[$col], ENT_QUOTES, 'UTF-8') : '';
                    }
                }
                $results[] = $item;
            }
            if (config('skeleton.developer_mode')) {
                Developer::debug('Helper: Fetch data', [
                    'system' => $system,
                    'table' => $table,
                    'columns' => $columns,
                    'condition' => $condition,
                    'results_count' => count($results),
                    'sample_result' => $results ? array_slice($results, 0, 3) : [],
                ]);
            }
            return $output === 'json' ? json_encode($results, JSON_UNESCAPED_UNICODE) : $results;
        } catch (Exception $e) {
            if (config('skeleton.developer_mode')) {
                Developer::error('Helper: Error fetching data', [
                    'table' => $table,
                    'error' => $e->getMessage(),
                ]);
            }
            return $output === 'json' ? '[]' : [];
        }
    }
    /****************************************************************************************************
     *                                                                                                  *
     *                             >>> Profile String Operations (START) <<<                            *
     *                                                                                                  *
     ****************************************************************************************************/
    /**
     * Generates a profile string from a name based on specified length.
     *
     * @param string $name The full name to process
     * @param int $length The desired length of the output string (default: 2)
     * @return string The generated profile string
     */
    public static function textProfile(string $name, int $length = 2): string
    {
        try {
            if (empty(trim($name))) {
                return '';
            }
            if ($length < 1) {
                return '';
            }
            // Trim and normalize the name
            $name = trim($name);
            // Split name into words
            $words = array_filter(explode(' ', $name));
            // Initialize result
            $result = '';
            if (count($words) > 1) {
                // Case with multiple words
                foreach ($words as $word) {
                    if (strlen($result) < $length && !empty($word)) {
                        $result .= strtoupper(substr($word, 0, 1));
                    }
                }
                // Pad with first letter of last word if needed
                if (strlen($result) < $length && !empty($words)) {
                    $lastWord = end($words);
                    $result .= strtoupper(substr($lastWord, 0, 1));
                }
            } else {
                // Case with single word
                $result = strtoupper(substr($name, 0, min($length, strlen($name))));
            }
            // Pad with 'X' if result is too short
            if (strlen($result) < $length) {
                $result .= str_repeat('X', $length - strlen($result));
            }
            return substr($result, 0, $length);
        } catch (Exception) {
            return '';
        }
    }
        /**
     * Retrieve a single value from a table based on a matching condition.
     *
     * @param string $table Table name
     * @param string $matchColumn Column to match the value against
     * @param mixed $matchValue Value to match
     * @param string $outputColumn Column to return the value from
     * @param string|null $strict System to use ('central' if specified, else user system)
     * @return string|null The matched value or null if not found or on error
     */
    public static function value(string $table, string $matchColumn, $matchValue, string $outputColumn, ?string $strict = null): ?string
    {
        try {
            if (empty($table) || empty($matchColumn) || empty($outputColumn)) {
                if (config('skeleton.developer_mode')) {
                    Developer::error('Helper: Error retrieving value', [
                        'table' => $table,
                        'matchColumn' => $matchColumn,
                        'outputColumn' => $outputColumn,
                        'error' => 'Table name, match column, and output column are required.',
                    ]);
                }
                return null;
            }
            $system = $strict ? 'central' : Skeleton::getUserSystem();
            $condition = [
                'where' => [$matchColumn => $matchValue],
                'limit' => 1
            ];
            $data = Data::fetch($system, $table, $condition, 'all');
            if (empty($data['data'])) {
                if (config('skeleton.developer_mode')) {
                    Developer::debug('Helper: No data found for value retrieval', [
                        'system' => $system,
                        'table' => $table,
                        'matchColumn' => $matchColumn,
                        'matchValue' => $matchValue,
                        'outputColumn' => $outputColumn,
                    ]);
                }
                return null;
            }
            $row = (array) reset($data['data']);
            if (!array_key_exists($outputColumn, $row)) {
                if (config('skeleton.developer_mode')) {
                    Developer::error('Helper: Output column not found in data', [
                        'table' => $table,
                        'matchColumn' => $matchColumn,
                        'matchValue' => $matchValue,
                        'outputColumn' => $outputColumn,
                        'row' => $row,
                    ]);
                }
                return null;
            }
            $value = htmlspecialchars((string)$row[$outputColumn], ENT_QUOTES, 'UTF-8');
            if (config('skeleton.developer_mode')) {
                Developer::debug('Helper: Value retrieved', [
                    'system' => $system,
                    'table' => $table,
                    'matchColumn' => $matchColumn,
                    'matchValue' => $matchValue,
                    'outputColumn' => $outputColumn,
                    'value' => $value,
                ]);
            }
            return $value;
        } catch (Exception $e) {
            if (config('skeleton.developer_mode')) {
                Developer::error('Helper: Error retrieving value', [
                    'table' => $table,
                    'matchColumn' => $matchColumn,
                    'matchValue' => $matchValue,
                    'outputColumn' => $outputColumn,
                    'error' => $e->getMessage(),
                ]);
            }
            return null;
        }
    }
    /****************************************************************************************************
     *                                                                                                  *
     *                             >>> JSON Manipulation Operations (START) <<<                         *
     *                                                                                                  *
     ****************************************************************************************************/
    /**
     * Modifies a JSON string based on the specified operation and changes.
     * Supports both single objects and arrays of objects, with nested key modifications using dot-notation.
     * If identifierKey and identifierValue are provided, modifies only matching objects in an array; otherwise, modifies all objects or the single object.
     * Returns the original JSON string on any error.
     *
     * @param string $json The input JSON string (single object or array of objects)
     * @param array $changes The changes to apply (key-value pairs or keys depending on operation)
     * @param string $operation The operation to perform (add, update, value, rename_keys, rename_key, rename_key_changes, sort, delete, replace_all, clear, delete_modified_entry, delete_modified)
     * @param string|null $identifierKey The key to identify objects in an array (e.g., 'account_number'), optional
     * @param mixed|null $identifierValue The value to match for the identifier key, optional
     * @return string JSON string with results or original JSON on error
     */
    public static function modifyJson(string $json, array $changes, string $operation, ?string $identifierKey = null, $identifierValue = null): string
    {
        try {
            if (empty(trim($json))) {
                if ($operation === 'add') {
                    $encoded = json_encode([$changes], JSON_UNESCAPED_UNICODE);
                    return $encoded !== false ? $encoded : $json;
                }
                return $json;
            }
            // Validate JSON input
            $data = json_decode($json, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return $json;
            }
            // Validate operation
            $validOperations = [
                'add',
                'update',
                'value',
                'rename_keys',
                'rename_key',
                'rename_key_changes',
                'sort',
                'delete',
                'replace_all',
                'clear',
                'delete_modified_entry',
                'delete_modified'
            ];
            if (!in_array($operation, $validOperations, true)) {
                return $json;
            }
            // Handle operations
            if ($operation === 'add') {
                if (!array_is_list($data)) {
                    $data = [$data];
                }
                $data[] = $changes; // Append new record
            } elseif ($operation === 'delete' && array_is_list($data) && $identifierKey !== null && $identifierValue !== null) {
                // Handle delete operation for array
                if (!is_string($identifierKey) || empty($identifierKey)) {
                    return $json;
                }
                $data = array_filter($data, function ($item) use ($identifierKey, $identifierValue) {
                    $currentValue = self::getNestedValue($item, $identifierKey);
                    return $currentValue !== $identifierValue;
                });
                $data = array_values($data); // Reindex array
            } else {
                // Handle other operations
                if (array_is_list($data)) {
                    if ($identifierKey !== null && $identifierValue !== null) {
                        if (!is_string($identifierKey) || empty($identifierKey)) {
                            return $json;
                        }
                        foreach ($data as &$item) {
                            if (!is_array($item)) {
                                continue;
                            }
                            $currentValue = self::getNestedValue($item, $identifierKey);
                            if ($currentValue !== $identifierValue) {
                                continue;
                            }
                            $item = self::applyOperation($item, $changes, $operation);
                        }
                        unset($item);
                    } else {
                        foreach ($data as &$item) {
                            if (!is_array($item)) {
                                continue;
                            }
                            $item = self::applyOperation($item, $changes, $operation);
                        }
                        unset($item);
                    }
                } else {
                    if ($identifierKey !== null || $identifierValue !== null) {
                        if (config('skeleton.developer_mode')) {
                            Developer::warning('Helper: Ignoring identifier for single JSON object', [
                                'operation' => $operation,
                                'identifierKey' => $identifierKey,
                            ]);
                        }
                    }
                    $data = self::applyOperation($data, $changes, $operation);
                }
            }
            $encoded = json_encode($data, JSON_UNESCAPED_UNICODE);
            return $encoded !== false ? $encoded : $json;
        } catch (Exception) {
            return $json;
        }
    }
    /**
     * Applies the specified operation to a single object.
     *
     * @param array $data Decoded JSON object
     * @param array $changes The changes to apply
     * @param string $operation The operation to perform
     * @return array Modified data
     */
    private static function applyOperation(array $data, array $changes, string $operation): array
    {
        switch ($operation) {
            case 'add':
                return self::addKeys($data, $changes);
            case 'update':
                return self::updateKeys($data, $changes);
            case 'value':
                return self::updateExistingValues($data, $changes);
            case 'rename_keys':
            case 'rename_key_changes':
                return self::renameKeys($data, $changes, $operation === 'rename_key_changes');
            case 'rename_key':
                return self::renameKeyNoChanges($data, $changes);
            case 'sort':
                return self::sortKeys($data, $changes);
            case 'delete':
                return self::deleteKeys($data, $changes);
            case 'replace_all':
                return $changes;
            case 'clear':
                return [];
            case 'delete_modified_entry':
                return self::deleteModifiedEntry($data, $changes);
            case 'delete_modified':
                return self::deleteModified($data);
            default:
                return $data;
        }
    }
    /**
     * Retrieves values for specified keys from a JSON string.
     * Supports dot-notation for nested keys. Returns JSON-encoded string of values.
     * Returns original JSON on error or if input is invalid.
     *
     * @param string $json The input JSON string
     * @param array $keys Array of keys to retrieve (dot-notation supported)
     * @return string JSON-encoded value(s) or original JSON on error
     */
    public static function jsonValue(string $json, array $keys): string
    {
        try {
            if (empty(trim($json))) {
                return $json;
            }
            // Validate JSON input
            $data = json_decode($json, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return $json;
            }
            // Handle single key or multiple keys
            $result = [];
            foreach ($keys as $key) {
                if (!is_string($key) || empty($key)) {
                    $result[] = null;
                    continue;
                }
                $result[] = self::getNestedValue($data, $key);
            }
            // Return single value if only one key, else array of values
            $value = count($result) === 1 ? $result[0] : $result;
            $encoded = json_encode($value, JSON_UNESCAPED_UNICODE);
            return $encoded !== false ? $encoded : $json;
        } catch (Exception) {
            return $json;
        }
    }
    /**
     * Adds key-value pairs only if the key does not exist.
     *
     * @param array $data Decoded JSON data
     * @param array $changes Key-value pairs to add
     * @return array Modified data
     */
    private static function addKeys(array $data, array $changes): array
    {
        try {
            foreach ($changes as $key => $value) {
                if (!is_string($key) || empty($key)) {
                    continue;
                }
                self::setNestedValue($data, $key, $value, false);
            }
            return $data;
        } catch (Exception) {
            return $data;
        }
    }
    /**
     * Updates or adds key-value pairs, including complex nested structures.
     *
     * @param array $data Decoded JSON data
     * @param array $changes Key-value pairs to update or add
     * @return array Modified data
     */
    private static function updateKeys(array $data, array $changes): array
    {
        try {
            foreach ($changes as $key => $value) {
                if (!is_string($key) || empty($key)) {
                    continue;
                }
                self::setNestedValue($data, $key, $value, true);
            }
            return $data;
        } catch (Exception) {
            return $data;
        }
    }
    /**
     * Updates values only for existing keys.
     *
     * @param array $data Decoded JSON data
     * @param array $changes Key-value pairs to update
     * @return array Modified data
     */
    private static function updateExistingValues(array $data, array $changes): array
    {
        try {
            foreach ($changes as $key => $value) {
                if (!is_string($key) || empty($key)) {
                    continue;
                }
                if (self::hasNestedKey($data, $key)) {
                    self::setNestedValue($data, $key, $value, true);
                }
            }
            return $data;
        } catch (Exception) {
            return $data;
        }
    }
    /**
     * Renames keys, storing history in 'modified' array with serial numbers if new key exists.
     *
     * @param array $data Decoded JSON data
     * @param array $changes Map of old keys to new keys
     * @param bool $updateModified Whether to update the 'modified' array on conflict
     * @return array Modified data
     */
    private static function renameKeys(array $data, array $changes, bool $updateModified): array
    {
        try {
            if (empty($changes)) {
                return $data;
            }
            // Initialize modified array if not exists
            if (!isset($data['modified']) || !is_array($data['modified'])) {
                $data['modified'] = [];
            }
            // Find the highest serial number in modified
            $maxSerial = 0;
            foreach ($data['modified'] as $entry) {
                $key = array_key_first($entry);
                if (is_numeric($key) && (int)$key > $maxSerial) {
                    $maxSerial = (int)$key;
                }
            }
            foreach ($changes as $oldKey => $newKey) {
                if (!is_string($oldKey) || !is_string($newKey) || empty($oldKey) || empty($newKey)) {
                    continue;
                }
                if (self::hasNestedKey($data, $oldKey)) {
                    $value = self::getNestedValue($data, $oldKey);
                    if (self::hasNestedKey($data, $newKey) && $newKey !== 'modified' && $updateModified) {
                        // Add to modified and delete old key
                        $data['modified'][] = [(string)(++$maxSerial) => ['old_key' => $oldKey, 'new_key' => $newKey, 'value' => $value]];
                        self::deleteNestedKey($data, $oldKey);
                    } else {
                        // Perform rename and log in modified
                        $data['modified'][] = [(string)(++$maxSerial) => ['old_key' => $oldKey, 'new_key' => $newKey, 'value' => $value]];
                        self::setNestedValue($data, $newKey, $value, true);
                        self::deleteNestedKey($data, $oldKey);
                    }
                }
            }
            return $data;
        } catch (Exception) {
            return $data;
        }
    }
    /**
     * Renames keys without modifying 'modified' array if new key exists.
     *
     * @param array $data Decoded JSON data
     * @param array $changes Map of old keys to new keys
     * @return array Original data if new key exists, else modified data
     */
    private static function renameKeyNoChanges(array $data, array $changes): array
    {
        try {
            if (empty($changes)) {
                return $data;
            }
            foreach ($changes as $oldKey => $newKey) {
                if (!is_string($oldKey) || !is_string($newKey) || empty($oldKey) || empty($newKey)) {
                    return $data;
                }
                if (self::hasNestedKey($data, $oldKey) && self::hasNestedKey($data, $newKey)) {
                    return $data; // Return unchanged if new key exists
                }
            }
            // If no conflicts, use renameKeys with updateModified = false
            return self::renameKeys($data, $changes, false);
        } catch (Exception) {
            return $data;
        }
    }
    /**
     * Deletes specified entries from the 'modified' array by serial number.
     *
     * @param array $data Decoded JSON data
     * @param array $changes Array of serial numbers to delete
     * @return array Modified data
     */
    private static function deleteModifiedEntry(array $data, array $changes): array
    {
        try {
            if (!isset($data['modified']) || !is_array($data['modified'])) {
                return $data;
            }
            foreach ($changes as $serial) {
                if (!is_string($serial) || empty($serial)) {
                    continue;
                }
            }
            $data['modified'] = array_filter($data['modified'], function ($entry) use ($changes) {
                return !in_array(array_key_first($entry), $changes, true);
            });
            // Reindex modified array to maintain consistency
            $data['modified'] = array_values($data['modified']);
            return $data;
        } catch (Exception) {
            return $data;
        }
    }
    /**
     * Deletes the entire 'modified' array from the JSON data.
     *
     * @param array $data Decoded JSON data
     * @return array Modified data
     */
    private static function deleteModified(array $data): array
    {
        try {
            if (isset($data['modified'])) {
                unset($data['modified']);
            }
            return $data;
        } catch (Exception) {
            return $data;
        }
    }
    /**
     * Sorts JSON keys in the order specified in $changes.
     *
     * @param array $data Decoded JSON data
     * @param array $changes Array of keys in desired order
     * @return array Modified data
     */
    private static function sortKeys(array $data, array $changes): array
    {
        try {
            $sorted = [];
            foreach ($changes as $key) {
                if (!is_string($key) || empty($key)) {
                    continue;
                }
                if (array_key_exists($key, $data)) {
                    $sorted[$key] = $data[$key];
                    unset($data[$key]);
                }
            }
            return array_merge($sorted, $data);
        } catch (Exception) {
            return $data;
        }
    }
    /**
     * Deletes specified keys from JSON data.
     *
     * @param array $data Decoded JSON data
     * @param array $changes Array of keys to delete
     * @return array Modified data
     */
    private static function deleteKeys(array $data, array $changes): array
    {
        try {
            foreach ($changes as $key) {
                if (!is_string($key) || empty($key)) {
                    continue;
                }
                self::deleteNestedKey($data, $key);
            }
            return $data;
        } catch (Exception) {
            return $data;
        }
    }
    /**
     * Sets a value for a nested key using dot-notation.
     *
     * @param array &$data Reference to the data array
     * @param string $key Dot-notation key (e.g., user.name)
     * @param mixed $value Value to set (scalar, array, or complex structure)
     * @param bool $overwrite Whether to overwrite existing keys
     * @return void
     */
    private static function setNestedValue(array &$data, string $key, $value, bool $overwrite): void
    {
        try {
            if (empty($key)) {
                return;
            }
            $keys = explode('.', $key);
            if (empty($keys)) {
                return;
            }
            $current = &$data;
            foreach ($keys as $i => $k) {
                if (empty($k)) {
                    return;
                }
                if ($i === count($keys) - 1) {
                    if ($overwrite || !array_key_exists($k, $current)) {
                        $current[$k] = $value;
                    }
                } else {
                    if (!isset($current[$k]) || !is_array($current[$k])) {
                        $current[$k] = [];
                    }
                    $current = &$current[$k];
                }
            }
        } catch (Exception) {
            // Silently return to avoid modifying data on error
        }
    }
    /**
     * Checks if a nested key exists using dot-notation.
     *
     * @param array $data Decoded JSON data
     * @param string $key Dot-notation key
     * @return bool True if key exists
     */
    private static function hasNestedKey(array $data, string $key): bool
    {
        try {
            if (empty($key)) {
                return false;
            }
            $keys = explode('.', $key);
            if (empty($keys)) {
                return false;
            }
            $current = $data;
            foreach ($keys as $k) {
                if (empty($k)) {
                    return false;
                }
                if (!is_array($current) || !array_key_exists($k, $current)) {
                    return false;
                }
                $current = $current[$k];
            }
            return true;
        } catch (Exception) {
            return false;
        }
    }
    /**
     * Gets a value for a nested key using dot-notation.
     *
     * @param array $data Decoded JSON data
     * @param string $key Dot-notation key
     * @return mixed|null Value or null if not found
     */
    private static function getNestedValue(array $data, string $key)
    {
        try {
            if (empty($key)) {
                return null;
            }
            $keys = explode('.', $key);
            if (empty($keys)) {
                return null;
            }
            $current = $data;
            foreach ($keys as $k) {
                if (empty($k)) {
                    return null;
                }
                if (!is_array($current) || !array_key_exists($k, $current)) {
                    return null;
                }
                $current = $current[$k];
            }
            return $current;
        } catch (Exception) {
            return null;
        }
    }
    /**
     * Deletes a nested key using dot-notation.
     *
     * @param array &$data Reference to the data array
     * @param string $key Dot-notation key
     * @return void
     */
    private static function deleteNestedKey(array &$data, string $key): void
    {
        try {
            if (empty($key)) {
                return;
            }
            $keys = explode('.', $key);
            if (empty($keys)) {
                return;
            }
            $current = &$data;
            foreach ($keys as $i => $k) {
                if (empty($k)) {
                    return;
                }
                if ($i === count($keys) - 1) {
                    unset($current[$k]);
                } elseif (isset($current[$k]) && is_array($current[$k])) {
                    $current = &$current[$k];
                } else {
                    return;
                }
            }
        } catch (Exception) {
            // Silently return to avoid modifying data on error
        }
    }
    /****************************************************************************************************
     *                                                                                                  *
     *                             >>> JSON Table Rendering Operations (START) <<<                      *
     *                                                                                                  *
     ****************************************************************************************************/
    /**
     * Renders a JSON string as an HTML table with Bootstrap 5 styles, including collapsible nested tables.
     * Returns an empty string on error.
     *
     * @param string $jsonString The input JSON string to render
     * @param string $view The view type: 'v' for vertical (key-value pairs), 'h' for horizontal (keys as headers)
     * @return string HTML table string with Bootstrap collapse JavaScript or empty string on error
     */
    public static function renderJsonTable(string $jsonString, string $view = 'v'): string
    {
        try {
            if (empty(trim($jsonString))) {
                return '';
            }
            // Validate JSON
            $data = json_decode($jsonString, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return '';
            }
            // Validate view parameter
            if (!in_array($view, ['v', 'h'], true)) {
                return '';
            }
            // Start table with Bootstrap classes
            $output = '<div class="table-responsive">';
            $output .= '<table class="table table-bordered table-sm table-striped">';
            // Render based on view type
            if ($view === 'v') {
                $output .= self::renderVerticalTable($data);
            } else {
                $output .= self::renderHorizontalTable($data);
            }
            $output .= '</table></div>';
            // Include Bootstrap collapse JavaScript (assumes Bootstrap 5 JS is included)
            $output .= <<<HTML
            <script>
            document.querySelectorAll('.collapse-toggle').forEach(button => {
                button.addEventListener('click', () => {
                    const target = button.getAttribute('data-bs-target');
                    const collapseElement = document.querySelector(target);
                    if (collapseElement) {
                        collapseElement.classList.toggle('show');
                    }
                });
            });
            </script>
            HTML;
            return $output;
        } catch (Exception) {
            return '';
        }
    }
    /**
     * Renders data as a vertical table (key-value pairs) with collapsible nested tables.
     *
     * @param mixed $data The decoded JSON data
     * @param int $depth The current nesting level (for indentation)
     * @param string $parentId Unique identifier for parent element
     * @return string HTML table body
     */
    private static function renderVerticalTable($data, int $depth = 0, string $parentId = ''): string
    {
        try {
            $output = '<tbody>';
            if (!is_array($data)) {
                // Handle scalar values
                $output .= '<tr><td colspan="2">' . htmlspecialchars((string)$data, ENT_QUOTES, 'UTF-8') . '</td></tr>';
            } else {
                $index = 0;
                foreach ($data as $key => $value) {
                    $collapseId = $parentId . 'collapse-' . $depth . '-' . $index++;
                    $output .= '<tr>';
                    $output .= '<td style="padding-left: ' . ($depth * 20) . 'px;">' . htmlspecialchars((string)$key, ENT_QUOTES, 'UTF-8') . '</td>';
                    $output .= '<td>';
                    if (is_array($value)) {
                        // Render collapsible nested table
                        $output .= '<button class="btn btn-sm btn-outline-primary collapse-toggle" data-bs-target="#' . htmlspecialchars($collapseId, ENT_QUOTES, 'UTF-8') . '">Toggle</button>';
                        $output .= '<div id="' . htmlspecialchars($collapseId, ENT_QUOTES, 'UTF-8') . '" class="collapse">';
                        $output .= '<div class="table-responsive">';
                        $output .= '<table class="table table-bordered table-sm table-striped">';
                        $output .= self::renderVerticalTable($value, $depth + 1, $collapseId);
                        $output .= '</table></div></div>';
                    } else {
                        $output .= htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
                    }
                    $output .= '</td></tr>';
                }
            }
            $output .= '</tbody>';
            return $output;
        } catch (Exception) {
            return '<tbody></tbody>';
        }
    }
    /**
     * Renders data as a horizontal table (keys as headers, values in rows) with collapsible nested tables.
     *
     * @param mixed $data The decoded JSON data
     * @return string HTML table with headers and body
     */
    private static function renderHorizontalTable($data): string
    {
        try {
            $output = '';
            if (!is_array($data)) {
                // Handle scalar values
                $output .= '<thead><tr><th>Value</th></tr></thead>';
                $output .= '<tbody><tr><td>' . htmlspecialchars((string)$data, ENT_QUOTES, 'UTF-8') . '</td></tr></tbody>';
                return $output;
            }
            // Determine if data is a list of items or a single object
            $isList = array_is_list($data);
            $rows = $isList ? $data : [$data];
            $keys = $isList && !empty($data) ? array_keys($data[0]) : array_keys($data);
            // Render headers
            $output .= '<thead><tr>';
            foreach ($keys as $key) {
                $output .= '<th>' . htmlspecialchars((string)$key, ENT_QUOTES, 'UTF-8') . '</th>';
            }
            $output .= '</tr></thead>';
            // Render rows
            $output .= '<tbody>';
            $rowIndex = 0;
            foreach ($rows as $row) {
                $output .= '<tr>';
                $colIndex = 0;
                foreach ($keys as $key) {
                    $value = $row[$key] ?? '';
                    $collapseId = 'collapse-row-' . $rowIndex . '-col-' . $colIndex++;
                    $output .= '<td>';
                    if (is_array($value)) {
                        // Render collapsible nested table
                        $output .= '<button class="btn btn-sm btn-outline-primary collapse-toggle" data-bs-target="#' . htmlspecialchars($collapseId, ENT_QUOTES, 'UTF-8') . '">Toggle</button>';
                        $output .= '<div id="' . htmlspecialchars($collapseId, ENT_QUOTES, 'UTF-8') . '" class="collapse">';
                        $output .= '<div class="table-responsive">';
                        $output .= '<table class="table table-bordered table-sm table-striped">';
                        $output .= self::renderVerticalTable($value, 1, $collapseId);
                        $output .= '</table></div></div>';
                    } else {
                        $output .= htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
                    }
                    $output .= '</td>';
                }
                $output .= '</tr>';
                $rowIndex++;
            }
            $output .= '</tbody>';
            return $output;
        } catch (Exception) {
            return '<thead><tr><th>Error</th></tr></thead><tbody><tr><td></td></tr></tbody>';
        }
    }
    /****************************************************************************************************
     *                                                                                                  *
     *                             >>> OTP Operations (START) <<<                                       *
     *                                                                                                  *
     ****************************************************************************************************/
    /**
     * Generates a random OTP of specified length and returns it with its MD5 hash.
     *
     * @param int $length The length of the OTP (default: 6)
     * @param bool $combination If true, OTP contains digits and letters; otherwise, only digits
     * @return stdClass Object with 'otp' (string) and 'token' (string, MD5 hash of OTP)
     */
    public static function generateOtp(int $length = 6, bool $combination = false): stdClass
    {
        try {
            if ($length < 1) {
                throw new InvalidArgumentException('OTP length must be at least 1');
            }
            $characters = $combination ? '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz' : '0123456789';
            $charLength = strlen($characters);
            if ($charLength === 0) {
                throw new RuntimeException('Character set for OTP generation is empty');
            }
            $otp = '';
            for ($i = 0; $i < $length; $i++) {
                $otp .= $characters[random_int(0, $charLength - 1)];
            }
            $result = new stdClass();
            $result->otp = $otp;
            $result->token = md5($otp);
            return $result;
        } catch (Exception) {
            $result = new stdClass();
            $result->otp = '';
            $result->token = '';
            return $result;
        }
    }
    /**
     * Verifies if the provided OTP matches the given token.
     *
     * @param string $otp The OTP to verify
     * @param string $token The MD5 hash to compare against
     * @return bool True if md5($otp) matches $token, false otherwise
     */
    public static function verifyOtp(string $otp, string $token): bool
    {
        try {
            if (empty(trim($otp)) || empty(trim($token))) {
                return false;
            }
            return md5($otp) === $token;
        } catch (Exception) {
            return false;
        }
    }
    /****************************************************************************************************
     *                                                                                                  *
     *                             >>> Color Operations (START) <<<                                     *
     *                                                                                                  *
     ****************************************************************************************************/
    /**
     * Generates a random color combination based on the specified type with proper UI contrast.
     *
     * @param string $type The color scheme type (e.g., 'light', 'gradient-light-1', 'gradient-dark-10')
     * @param string $set The output format ('background' for background only, 'color' for foreground only, 'both' for both)
     * @return string|array Background string (hex or gradient), color string, or array with both
     * @throws InvalidArgumentException
     */
    public static function colors(string $type = 'light', string $set = 'both'): string|array
    {
        try {
            $type = strtolower(trim($type));
            $set = strtolower(trim($set));
            // Validate $set parameter
            if (!in_array($set, ['background', 'color', 'both'], true)) {
                throw new InvalidArgumentException("Invalid set parameter: {$set}. Must be 'background', 'color', or 'both'.");
            }
            // Define single-color schemes with at least 10 variations
            $singleColorSchemes = [
                'light' => [
                    'bg' => ['#F5F5F5', '#E0E0E0', '#D3D3D3', '#EEEEEE', '#FAFAFA', '#E8ECEF', '#DCE2E9', '#F0F4F8', '#E6E8EB', '#F7F9FC'],
                    'fg' => ['#333333', '#1A1A1A', '#000000', '#2F2F2F', '#454545', '#1C2526', '#0F1419', '#333333', '#1A1A1A', '#000000']
                ],
                'medium' => [
                    'bg' => ['#808080', '#696969', '#A9A9A9', '#7B7B7B', '#909090', '#6E7A7A', '#5F6B6B', '#8A9393', '#747C7C', '#A0A8A8'],
                    'fg' => ['#FFFFFF', '#E0E0E0', '#F5F5F5', '#D3D3D3', '#E8ECEF', '#F0F4F8', '#FFFFFF', '#E0E0E0', '#F5F5F5', '#D3D3D3']
                ],
                'dark' => [
                    'bg' => ['#333333', '#2F2F2F', '#1A1A1A', '#252525', '#404040', '#2A2A2A', '#1F2727', '#353C3C', '#303838', '#1C2526'],
                    'fg' => ['#FFFFFF', '#E0E0E0', '#D3D3D3', '#F5F5F5', '#E8ECEF', '#F0F4F8', '#FFFFFF', '#E0E0E0', '#D3D3D3', '#F5F5F5']
                ],
                'vibrant' => [
                    'bg' => ['#FF4500', '#DC143C', '#FF69B4', '#FFD700', '#00CED1', '#FF6347', '#ADFF2F', '#FF1493', '#00FA9A', '#FF4500'],
                    'fg' => ['#FFFFFF', '#F5F5F5', '#E0E0E0', '#000000', '#1A1A1A', '#333333', '#000000', '#FFFFFF', '#1A1A1A', '#FFFFFF']
                ],
                'pastel' => [
                    'bg' => ['#FFD1DC', '#B0E0E6', '#98FB98', '#FFE4E1', '#ADD8E6', '#90EE90', '#FFB6C1', '#AFEEEE', '#98FF98', '#F0E68C'],
                    'fg' => ['#333333', '#1A1A1A', '#000000', '#2F2F2F', '#454545', '#1C2526', '#0F1419', '#333333', '#1A1A1A', '#000000']
                ],
                'monochrome' => [
                    'bg' => ['#4A4A4A', '#5B5B5B', '#3C3C3C', '#6B6B6B', '#505050', '#454545', '#555555', '#606060', '#3A3A3A', '#4F4F4F'],
                    'fg' => ['#FFFFFF', '#E0E0E0', '#D3D3D3', '#F5F5F5', '#E8ECEF', '#F0F4F8', '#FFFFFF', '#E0E0E0', '#D3D3D3', '#F5F5F5']
                ],
                'contrast' => [
                    'bg' => ['#000000', '#FFFFFF', '#FF0000', '#00FF00', '#0000FF', '#FFFF00', '#FF00FF', '#00FFFF', '#800000', '#008000'],
                    'fg' => ['#FFFFFF', '#000000', '#FFFFFF', '#000000', '#FFFFFF', '#000000', '#FFFFFF', '#000000', '#FFFFFF', '#FFFFFF']
                ]
            ];
            // Define gradient color schemes with at least 10 color sets per type
            $gradientColorSchemes = [
                'gradient.light' => [
                    'colors' => [
                        ['#E0F7FA', '#B2EBF2', '#80DEEA'], // Cyan shades
                        ['#FCE4EC', '#F8BBD0', '#F48FB1'], // Pink shades
                        ['#E8F5E9', '#C8E6C9', '#A5D6A7'], // Green shades
                        ['#FFF3E0', '#FFE0B2', '#FFCC80'], // Orange shades
                        ['#E1F5FE', '#B3E5FC', '#81D4FA'], // Blue shades
                        ['#F3E5F5', '#E1BEE7', '#CE93D8'], // Purple shades
                        ['#FFFDE7', '#FFF9C4', '#FFF59D'], // Yellow shades
                        ['#ECEFF1', '#CFD8DC', '#B0BEC5'], // Grey shades
                        ['#F1F8E9', '#DCEDC8', '#C5E1A5'], // Light green shades
                        ['#E0F2E9', '#B2DFDB', '#80CBC4'], // Teal shades
                    ],
                    'fg' => ['#333333', '#1A1A1A', '#000000', '#2F2F2F', '#454545', '#1C2526', '#0F1419', '#333333', '#1A1A1A', '#000000']
                ],
                'gradient.medium' => [
                    'colors' => [
                        ['#90A4AE', '#78909C', '#607D8B'], // Blue-grey shades
                        ['#A1887F', '#8D6E63', '#6D4C41'], // Brown shades
                        ['#A5B4FC', '#7986CB', '#5C6BC0'], // Indigo shades
                        ['#B0BEC5', '#90A4AE', '#78909C'], // Grey shades
                        ['#FFAB91', '#FF8A65', '#FF7043'], // Orange shades
                        ['#F06292', '#EC407A', '#D81B60'], // Pink shades
                        ['#81C784', '#66BB6A', '#4CAF50'], // Green shades
                        ['#E57373', '#EF5350', '#F44336'], // Red shades
                        ['#BA68C8', '#AB47BC', '#9C27B0'], // Purple shades
                        ['#4FC3F7', '#29B6F6', '#03A9F4'], // Blue shades
                    ],
                    'fg' => ['#FFFFFF', '#E0E0E0', '#F5F5F5', '#D3D3D3', '#E8ECEF', '#F0F4F8', '#FFFFFF', '#E0E0E0', '#F5F5F5', '#D3D3D3']
                ],
                'gradient.dark' => [
                    'colors' => [
                        ['#263238', '#37474F', '#455A64'], // Dark blue-grey
                        ['#4E342E', '#6D4C41', '#8D6E63'], // Dark brown
                        ['#212121', '#424242', '#616161'], // Dark grey
                        ['#1B2631', '#263238', '#37474F'], // Darker blue-grey
                        ['#311B92', '#512DA8', '#673AB7'], // Deep purple
                        ['#B71C1C', '#D32F2F', '#F44336'], // Deep red
                        ['#1B5E20', '#2E7D32', '#388E3C'], // Deep green
                        ['#0D47A1', '#1976D2', '#2196F3'], // Deep blue
                        ['#3E2723', '#5D4037', '#6D4C41'], // Darker brown
                        ['#1A237E', '#283593', '#3F51B5'], // Indigo
                    ],
                    'fg' => ['#FFFFFF', '#E0E0E0', '#D3D3D3', '#F5F5F5', '#E8ECEF', '#F0F4F8', '#FFFFFF', '#E0E0E0', '#D3D3D3', '#F5F5F5']
                ],
                'gradient.vibrant' => [
                    'colors' => [
                        ['#FF5722', '#F4511E', '#E64A19'], // Deep orange
                        ['#E91E63', '#D81B60', '#C2185B'], // Pink
                        ['#4CAF50', '#43A047', '#388E3C'], // Green
                        ['#2196F3', '#1E88E5', '#1976D2'], // Blue
                        ['#FF9800', '#FB8C00', '#F57C00'], // Orange
                        ['#9C27B0', '#8E24AA', '#7B1FA2'], // Purple
                        ['#FFEB3B', '#FDD835', '#FBC02D'], // Yellow
                        ['#00BCD4', '#00ACC1', '#0097A7'], // Cyan
                        ['#F44336', '#E53935', '#D32F2F'], // Red
                        ['#3F51B5', '#3949AB', '#303F9F'], // Indigo
                    ],
                    'fg' => ['#FFFFFF', '#F5F5F5', '#E0E0E0', '#000000', '#1A1A1A', '#333333', '#000000', '#FFFFFF', '#1A1A1A', '#FFFFFF']
                ],
                'gradient.pastel' => [
                    'colors' => [
                        ['#FFCCBC', '#FFAB91', '#FF8A65'], // Peach
                        ['#B2EBF2', '#80DEEA', '#4DD0E1'], // Cyan
                        ['#C8E6C9', '#A5D6A7', '#81C784'], // Green
                        ['#BBDEFB', '#90CAF9', '#64B5F6'], // Blue
                        ['#F8BBD0', '#F48FB1', '#EC407A'], // Pink
                        ['#E1BEE7', '#CE93D8', '#BA68C8'], // Purple
                        ['#FFF9C4', '#FFF59D', '#FFF176'], // Yellow
                        ['#B2DFDB', '#80CBC4', '#4DB6AC'], // Teal
                        ['#F0F4C3', '#E6EE9C', '#DCE775'], // Lime
                        ['#D7CCC8', '#BCAAA4', '#A1887F'], // Brown
                    ],
                    'fg' => ['#333333', '#1A1A1A', '#000000', '#2F2F2F', '#454545', '#1C2526', '#0F1419', '#333333', '#1A1A1A', '#000000']
                ],
                'gradient.monochrome' => [
                    'colors' => [
                        ['#616161', '#757575', '#8A8A8A'], // Grey
                        ['#424242', '#616161', '#757575'], // Darker grey
                        ['#212121', '#424242', '#616161'], // Darkest grey
                        ['#B0BEC5', '#90A4AE', '#78909C'], // Blue-grey
                        ['#455A64', '#607D8B', '#78909C'], // Dark blue-grey
                        ['#546E7A', '#607D8B', '#78909C'], // Blue-grey shades
                        ['#37474F', '#455A64', '#607D8B'], // Darker blue-grey
                        ['#263238', '#37474F', '#455A64'], // Darkest blue-grey
                        ['#B0BEC5', '#CFD8DC', '#ECEFF1'], // Light grey
                        ['#78909C', '#90A4AE', '#B0BEC5'], // Medium grey
                    ],
                    'fg' => ['#FFFFFF', '#E0E0E0', '#D3D3D3', '#F5F5F5', '#E8ECEF', '#F0F4F8', '#FFFFFF', '#E0E0E0', '#D3D3D3', '#F5F5F5']
                ],
                'gradient.contrast' => [
                    'colors' => [
                        ['#FF0000', '#B71C1C', '#7F0000'], // Red
                        ['#00FF00', '#00C853', '#007300'], // Green
                        ['#0000FF', '#1565C0', '#0D47A1'], // Blue
                        ['#FFFF00', '#FBC02D', '#F57F17'], // Yellow
                        ['#FF00FF', '#C2185B', '#880E4F'], // Magenta
                        ['#00FFFF', '#00ACC1', '#006064'], // Cyan
                        ['#800000', '#B71C1C', '#FF0000'], // Maroon to red
                        ['#008000', '#00C853', '#00FF00'], // Green shades
                        ['#000080', '#1565C0', '#0000FF'], // Navy to blue
                        ['#FFC107', '#FF8F00', '#FF6F00'], // Amber
                    ],
                    'fg' => ['#FFFFFF', '#000000', '#FFFFFF', '#000000', '#FFFFFF', '#000000', '#FFFFFF', '#000000', '#FFFFFF', '#FFFFFF']
                ]
            ];
            // Handle single-color schemes
            if (array_key_exists($type, $singleColorSchemes)) {
                $scheme = $singleColorSchemes[$type];
                $index = random_int(0, count($scheme['bg']) - 1);
                $background = $scheme['bg'][$index];
                $color = $scheme['fg'][$index];
                return match ($set) {
                    'background' => $background,
                    'color' => $color,
                    'both' => ['background' => $background, 'color' => $color],
                    default => throw new InvalidArgumentException("Invalid set parameter: {$set}")
                };
            }
            // Handle gradient schemes
            if (preg_match('/^gradient-(light|medium|dark|vibrant|pastel|monochrome|contrast)-([1-9]|10)$/', $type, $matches)) {
                $baseType = $matches[1];
                $linearity = (int)$matches[2];
                $schemeKey = "gradient.{$baseType}";
                if (!array_key_exists($schemeKey, $gradientColorSchemes)) {
                    throw new InvalidArgumentException("Invalid gradient scheme type: {$type}");
                }
                $scheme = $gradientColorSchemes[$schemeKey];
                $colorSetIndex = random_int(0, count($scheme['colors']) - 1);
                $colors = $scheme['colors'][$colorSetIndex];
                $fgIndex = random_int(0, count($scheme['fg']) - 1);
                $background = self::generateGradient($colors, $linearity);
                $color = $scheme['fg'][$fgIndex];
                return match ($set) {
                    'background' => $background,
                    'color' => $color,
                    'both' => ['background' => $background, 'color' => $color],
                    default => throw new InvalidArgumentException("Invalid set parameter: {$set}")
                };
            }
            throw new InvalidArgumentException("Invalid color scheme type: {$type}");
        } catch (Exception $e) {
            // Fallback based on $set
            return match ($set) {
                'background' => '#FFFFFF',
                'color' => '#000000',
                'both' => ['background' => '#FFFFFF', 'color' => '#000000'],
                default => throw new InvalidArgumentException("Invalid set parameter: {$set}")
            };
        }
    }
    /**
     * Generates a CSS gradient string with the specified number of color stops.
     *
     * @param array $colors Array of hex colors
     * @param int $stops Number of stops (1 to 10)
     * @return string CSS linear-gradient string
     */
    private static function generateGradient(array $colors, int $stops): string
    {
        $stops = max(2, min(10, $stops)); // Ensure stops are between 2 and 10
        // Calculate percentages for stops
        $percentages = [];
        if ($stops == 2) {
            $percentages = [0, 100];
        } else {
            $step = 100 / ($stops - 1);
            for ($i = 0; $i < $stops; $i++) {
                $percentages[] = $i * $step;
            }
        }
        // Select or interpolate colors to match the number of stops
        $selectedColors = [];
        if (count($colors) >= $stops) {
            // Randomly select colors if we have enough
            $indices = array_rand($colors, $stops);
            if (!is_array($indices)) {
                $indices = [$indices];
            }
            foreach ($indices as $index) {
                $selectedColors[] = $colors[$index];
            }
        } else {
            // Repeat or use available colors
            $selectedColors = $colors;
            while (count($selectedColors) < $stops) {
                $selectedColors[] = $colors[array_rand($colors)];
            }
        }
        // Build gradient string
        $gradientStops = [];
        for ($i = 0; $i < $stops; $i++) {
            $gradientStops[] = "{$selectedColors[$i]} {$percentages[$i]}%";
        }
        return "linear-gradient(45deg, " . implode(', ', $gradientStops) . ")";
    }
    /****************************************************************************************************
     *                                                                                                  *
     *                                     >>> Dropdown (START) <<<                                     *
     *                                                                                                  *
     ****************************************************************************************************/
    /**
     * Generates a dropdown based on the name from skeleton_dropdowns table.
     *
     * @param string $name The name of the dropdown to fetch from skeleton_dropdowns
     * @param string $output The output format ('array', 'json', or 'html')
     * @return array|string The dropdown data in the specified format
     */
    public static function dropdown(string $name, string $output = 'array')
    {
        try {
            if (empty(trim($name)) || !in_array($output, ['array', 'json', 'html'], true)) {
                if (config('skeleton.developer_mode')) {
                    Developer::error('Helper: Invalid dropdown parameters', [
                        'name' => $name,
                        'output' => $output,
                        'error' => empty(trim($name)) ? 'Dropdown name is required.' : 'Output must be "array", "json", or "html".',
                    ]);
                }
                return $output === 'json' ? '{}' : ($output === 'html' ? '' : []);
            }
            $data = self::fetch('skeleton_dropdowns', ['pairs'], ['name' => $name], 'array', 'central');
            if (empty($data[0]['pairs'])) {
                if (config('skeleton.developer_mode')) {
                    Developer::error('Helper: No dropdown data found', [
                        'name' => $name,
                        'condition' => ['where' => ['name' => $name]],
                    ]);
                }
                return $output === 'json' ? '{}' : ($output === 'html' ? '' : []);
            }
            // Decode HTML entities (if stored that way in DB)
            $jsonString = html_entity_decode($data[0]['pairs']);
            $pairs = json_decode($jsonString, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                if (config('skeleton.developer_mode')) {
                    Developer::error('Helper: Invalid JSON in dropdown pairs', [
                        'name' => $name,
                        'pairs' => $data[0]['pairs'],
                        'error' => json_last_error_msg(),
                    ]);
                }
                return $output === 'json' ? '{}' : ($output === 'html' ? '' : []);
            }
            // Output formatting
            switch ($output) {
                case 'json':
                    $encoded = json_encode($pairs, JSON_UNESCAPED_UNICODE);
                    return $encoded !== false ? $encoded : '{}';
                case 'html':
                    return implode('', array_map(function ($key, $label) {
                        $value = htmlspecialchars((string)$key, ENT_QUOTES, 'UTF-8');
                        $label = htmlspecialchars((string)$label, ENT_QUOTES, 'UTF-8');
                        return "<option value=\"{$value}\">{$label}</option>";
                    }, array_keys($pairs), $pairs));
                default: // 'array'
                    return $pairs;
            }
        } catch (Exception $e) {
            if (config('skeleton.developer_mode')) {
                Developer::error('Helper: Error generating dropdown', [
                    'name' => $name,
                    'output' => $output,
                    'error' => $e->getMessage(),
                ]);
            }
            return $output === 'json' ? '{}' : ($output === 'html' ? '' : []);
        }
    }

     /**
     * Fetch holidays for a company.
     *
     * @param string|null $companyId
     * @return array
     */

    public static function holidays(?string $companyId = null): array
    {
        $columns = ['id','holiday_id','company_id','name','description','image','color','start_date','end_date','recurring_type','recurring_day','recurring_week','is_active','created_by','updated_by','deleted_at','created_at','updated_at'];
        $query = BusinessDB::table('company_holidays')
            ->select($columns)
            ->where('is_active', 1)
            ->whereNull('deleted_at');

        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }

        $holidays = $query->get()->map(fn($h) => (array) $h)->toArray();

        $weekNumberMap = ['first' => 1, 'second' => 2, 'third' => 3, 'fourth' => 4, 'last' => 5];
        $companyEvents = [];

        foreach ($holidays as $h) {
            $cid = (string) $h['company_id']; // don't overwrite $companyId
            $start = Carbon::parse($h['start_date']);
            $end = Carbon::parse($h['end_date'] ?? $h['start_date']);
            $img = $h['image'] ? FileManager::getFile($h['image']) : "";
            $color = $h['color'] ?? null;
            $html = '<div><b>Holiday</b><br>' . e($h['description'] ?? $h['name']) . '</div>';

            $event = [
                'id' => 'h-' . $h['holiday_id'],
                'type' => 'holiday',
                'title' => $h['name'],
                'start' => $start->format('Y-m-d'),
                'end' => $end->format('Y-m-d'),
                'allDay' => true,
                'img' => $img,
                'color' => $color,
                'html' => $html,
                'assignees' => []
            ];

            if ($h['recurring_type'] && $h['recurring_type'] !== 'none') {
                $periodEnd = $end->copy();
                $current = $start->copy();

                while ($current <= $periodEnd) {
                    $day = strtolower($current->format('l'));
                    $weekOfMonth = ceil($current->day / 7);
                    $valid = ($h['recurring_type'] === 'weekly' && $day === strtolower($h['recurring_day']))
                        || ($h['recurring_type'] === 'monthly' && isset($weekNumberMap[$h['recurring_week']]) && $weekNumberMap[$h['recurring_week']] === $weekOfMonth)
                        || ($h['recurring_type'] === 'yearly' && $current->format('m-d') === $start->format('m-d'));

                    if ($valid) {
                        $recurringEvent = [
                            'id' => $h['holiday_id'] ,
                            'type' => 'holiday',
                            'title' => $h['name'],
                            'start' => $current->format('Y-m-d'),
                            'end' => $current->format('Y-m-d'),
                            'allDay' => true,
                            'img' => $img,
                            'color' => $color,
                            'html' => $html,
                            'assignees' => []
                        ];
                        $companyEvents[$cid][] = $recurringEvent;
                    }
                    $current->addDay();
                }
            } else {
                $companyEvents[$cid][] = $event;
            }
        }
        return $companyId !== null
            ? ($companyEvents[(string) $companyId] ?? [])
            : $companyEvents;
    }

    public static function business(string $output = 'html', ?bool $bool = false)
    {
        try {
            $query = CentralDB::table('business_systems')
                ->select('business_id', 'name')
                ->where('is_active', 1)
                ->whereNull('deleted_at');

            if ($bool === false || $bool === null) {
                $query->whereRaw('LOWER(business_id) != ?', ['central']);
            }

            $systems = $query->orderBy('name')->get();

            if ($systems->isEmpty()) {
                return $output === 'html' ? '' : [];
            }

            if ($output === 'html') {
                $html = '';
                foreach ($systems as $row) {
                    $html .= "<option value=\"{$row->business_id}\">{$row->name}</option>";
                }
                return $html;
            }

            $array = [];
            foreach ($systems as $row) {
                $array[$row->business_id] = $row->name;
            }
            return $array;

        } catch (Exception $e) {
            return $output === 'html' ? '' : [];
        }
    }
    public static function companies(string $output = 'html', ?bool $bool = false)
    {
        try {
            $query = BusinessDB::table('companies')
                ->select('company_id', 'name')
                ->where('is_active', 1)
                ->whereNull('deleted_at');

            if ($bool === true) {
                $userCompanyId = Skeleton::authUser()->company_id ?? null;
                if ($userCompanyId) {
                    $query->where('company_id', $userCompanyId);
                } else {
                    return $output === 'html' ? '' : [];
                }
            }

            $companies = $query->orderBy('name')->get();

            if ($companies->isEmpty()) {
                return $output === 'html' ? '' : [];
            }

            if ($output === 'html') {
                $html = '';
                foreach ($companies as $row) {
                    $html .= "<option value=\"{$row->company_id}\">{$row->name}</option>";
                }
                return $html;
            }

            $array = [];
            foreach ($companies as $row) {
                $array[$row->company_id] = $row->name;
            }
            return $array;

        } catch (Exception $e) {
            return $output === 'html' ? '' : [];
        }
    }




    
    /****************************************************************************************************
     *                                                                                                  *
     *                             >>> Example Usage (START) <<<                                         *
     *                                                                                                  *
     ****************************************************************************************************/
    /**
     * Example Usage:
     *
     * // Profile String Generation
     * use App\Http\Helpers\Helper;
     * echo Helper::textProfile('Kiran Kumar', 2); // Output: KK
     * echo Helper::textProfile('John', 3); // Output: JOH
     * echo Helper::textProfile('', 2); // Output: ''
     *
     * // JSON Manipulation
     * $json = '{"name":"Kiran","city":"Hyderabad","email":"old@example.com","details":{"age":30,"role":"Developer"},"modified":[{"1":{"old_key":"firstName","new_key":"name","value":"Kiran"}}]}';
     * // Add new keys
     * echo Helper::modifyJson($json, ['country' => 'India', 'details.salary' => 50000], 'add');
     * // Output: {"name":"Kiran","city":"Hyderabad","email":"old@example.com","details":{"age":30,"role":"Developer","salary":50000},"modified":[{"1":{"old_key":"firstName","new_key":"name","value":"Kiran"}}],"country":"India"}
     * // Update keys
     * echo Helper::modifyJson($json, ['email' => 'new@example.com'], 'update');
     * // Output: {"name":"Kiran","city":"Hyderabad","email":"new@example.com","details":{"age":30,"role":"Developer"},"modified":[{"1":{"old_key":"firstName","new_key":"name","value":"Kiran"}}]}
     * // Update existing values
     * echo Helper::modifyJson($json, ['city' => 'Mumbai'], 'value');
     * // Output: {"name":"Kiran","city":"Mumbai","email":"old@example.com","details":{"age":30,"role":"Developer"},"modified":[{"1":{"old_key":"firstName","new_key":"name","value":"Kiran"}}]}
     * // Rename keys (no conflict)
     * echo Helper::modifyJson($json, ['city' => 'location'], 'rename_keys');
     * // Output: {"name":"Kiran","location":"Hyderabad","email":"old@example.com","details":{"age":30,"role":"Developer"},"modified":[{"1":{"old_key":"firstName","new_key":"name","value":"Kiran"}},{"2":{"old_key":"city","new_key":"location","value":"Hyderabad"}}]}
     * // Rename keys (conflict, no change)
     * echo Helper::modifyJson($json, ['city' => 'email'], 'rename_key');
     * // Output: {"name":"Kiran","city":"Hyderabad","email":"old@example.com","details":{"age":30,"role":"Developer"},"modified":[{"1":{"old_key":"firstName","new_key":"name","value":"Kiran"}}]}
     * // Rename keys (conflict, update modified)
     * echo Helper::modifyJson($json, ['city' => 'email'], 'rename_key_changes');
     * // Output: {"name":"Kiran","email":"old@example.com","details":{"age":30,"role":"Developer"},"modified":[{"1":{"old_key":"firstName","new_key":"name","value":"Kiran"}},{"2":{"old_key":"city","new_key":"email","value":"Hyderabad"}}]}
     * // Sort keys
     * echo Helper::modifyJson($json, ['email', 'name'], 'sort');
     * // Output: {"email":"old@example.com","name":"Kiran","city":"Hyderabad","details":{"age":30,"role":"Developer"},"modified":[{"1":{"old_key":"firstName","new_key":"name","value":"Kiran"}}]}
     * // Delete keys
     * echo Helper::modifyJson($json, ['city', 'details.age'], 'delete');
     * // Output: {"name":"Kiran","email":"old@example.com","details":{"role":"Developer"},"modified":[{"1":{"old_key":"firstName","new_key":"name","value":"Kiran"}}]}
     * // Replace all
     * echo Helper::modifyJson($json, ['new_key' => 'new_value'], 'replace_all');
     * // Output: {"new_key":"new_value"}
     * // Clear JSON
     * echo Helper::modifyJson($json, [], 'clear');
     * // Output: {}
     * // Delete modified entry
     * echo Helper::modifyJson($json, ['1'], 'delete_modified_entry');
     * // Output: {"name":"Kiran","city":"Hyderabad","email":"old@example.com","details":{"age":30,"role":"Developer"},"modified":[]}
     * // Delete entire modified array
     * echo Helper::modifyJson($json, [], 'delete_modified');
     * // Output: {"name":"Kiran","city":"Hyderabad","email":"old@example.com","details":{"age":30,"role":"Developer"}}
     * // Invalid JSON
     * echo Helper::modifyJson('{invalid}', ['city' => 'location'], 'rename_key');
     * // Output: {invalid}
     * // Invalid operation
     * echo Helper::modifyJson($json, ['city' => 'location'], 'invalid_op');
     * // Output: {"name":"Kiran","city":"Hyderabad","email":"old@example.com","details":{"age":30,"role":"Developer"},"modified":[{"1":{"old_key":"firstName","new_key":"name","value":"Kiran"}}]}
     *
     * // JSON Value Retrieval
     * echo Helper::jsonValue($json, ['name']);
     * // Output: "Kiran"
     * echo Helper::jsonValue($json, ['details']);
     * // Output: {"age":30,"role":"Developer"}
     * echo Helper::jsonValue($json, ['details.age']);
     * // Output: 30
     * echo Helper::jsonValue($json, ['name', 'details.age', 'nonexistent']);
     * // Output: ["Kiran",30,null]
     * echo Helper::jsonValue('{invalid}', ['name']);
     * // Output: {invalid}
     *
     * // JSON Table Rendering (requires Bootstrap 5 CSS and JS)
     * // <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
     * // <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
     * $complexJson = '{
     *     "name": "Kiran",
     *     "city": "Hyderabad",
     *     "details": {"age": 30, "role": "Developer"},
     *     "skills": ["PHP", "JavaScript"],
     *     "projects": [{"id": 1, "name": "Project A"}, {"id": 2, "name": "Project B"}]
     * }';
     * echo Helper::renderJsonTable($complexJson, 'v');
     * // Output: Vertical table with collapsible nested tables for details, skills, and projects
     * echo Helper::renderJsonTable($complexJson, 'h');
     * // Output: Horizontal table with keys as headers, collapsible nested tables
     * echo Helper::renderJsonTable('{invalid}', 'v');
     * // Output: ''
     * echo Helper::renderJsonTable('', 'v');
     * // Output: ''
     *
     * // OTP Generation and Verification
     * $otpData = Helper::generateOtp(6, true);
     * echo "OTP: {$otpData->otp}, Token: {$otpData->token}\n";
     * // Example Output: OTP: X7kP9m, Token: 7c4a8d09ca3762af61e59520943dc264
     * echo Helper::verifyOtp($otpData->otp, $otpData->token) ? "Valid OTP\n" : "Invalid OTP\n";
     * // Output: Valid OTP
     * $otpData = Helper::generateOtp(0, true);
     * // Output: stdClass with empty otp and token
     * echo Helper::verifyOtp('', 'token') ? "Valid OTP\n" : "Invalid OTP\n";
     * // Output: Invalid OTP
     */
}
