<?php
namespace App\Http\Helpers;
use App\Facades\{Data, Developer, Skeleton, Filemanager, Profile};
use App\Http\Controllers\Controller;
use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\{Auth, Cache, Config, Log};
use Exception;
/**
 * Controller for handling dynamic and static Select2 dropdown data.
 */
class SelectHelper extends Controller
{
    /**
     * Handle AJAX requests for Select2 dropdown data (dynamic or static).
     *
     * @param Request $request HTTP request object.
     * @param array $params Route parameters with token.
     * @return JsonResponse Dropdown data or error message.
     */
    public function index(Request $request, array $params = []): JsonResponse
    {
        try {
            $token = $params['token'] ?? $request->input('skeleton_token');
            if (!is_string($token) || empty($token)) {
                return response()->json(['status' => false, 'message' => 'Invalid token'], 400);
            }
            $selectedValue = $request->input('selected_value');
            $searchTerm = $request->input('q') ?? '';
            $preselected = $request->input('selected') ? (array)$request->input('selected') : null;
            $setType = $request->input('set');
            $id = $request->input('id');
            $reqSet = Skeleton::resolveToken($token);
            
            if (!isset($reqSet['key']) || !isset($reqSet['table']) || !isset($reqSet['value'])) {
                return response()->json(['status' => false, 'message' => 'Invalid token configuration'], 400);
            }
            $results = [];
            if ($setType === 'user' || $setType === 'scope') {
                $filters = [];
                if ($id && $setType === 'user') {
                    $filters['user_id'] = $id;
                } elseif ($id && $setType === 'scope') {
                    $filters['scope_id'] = $id;
                } elseif ($id && $setType === 'role') {
                    $filters['role_id'] = $id;
                } elseif ($selectedValue) {
                    $filters['scope_id'] = $selectedValue;
                }
                if ($searchTerm) {
                    $filters['OR'] = [
                        ['first_name' => "%$searchTerm%"],
                        ['last_name' => "%$searchTerm%"],
                        ['email' => "%$searchTerm%"],
                        ['username' => "%$searchTerm%"],
                    ];
                }
                $results = Profile::users($filters, ['value' => 'user_id', 'view' => 'name', 'group' => 'role', 'avatar' => 'avatar', 'scope' => 'scope'], 'array');
            } else {
                $condition = [];
                if ($selectedValue && isset($reqSet['column']) && isset($reqSet['value'])) {
                    $condition[$reqSet['column']] = $selectedValue;
                }
                if ($searchTerm) {
                    $condition['search'] = $searchTerm;
                }
                $results = $this->options(
                    tokenOrTable: $token,
                    output: 'json',
                    columns: $reqSet['value'] ?? null,
                    condition: $condition,
                    selected: $preselected
                );
            }
            // \Log::info($request->all());
            // \Log::info($reqSet);
            // \Log::info($results);
            return response()->json(['status' => true, 'data' => $results]);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => 'Failed to fetch dropdown data'], 500);
        }
    }
    /**
     * Parse columns string into an array (e.g., 'module_id|name' to ['module_id' => 'name']).
     *
     * @param string $columns Column string in 'idColumn|valueColumn' or JSON format
     * @return array Parsed columns array
     */
    private function parseColumns(string $columns): array
    {
        if (empty($columns)) {
            return ['id' => 'name'];
        }
        $decoded = json_decode($columns, true);
        if (is_array($decoded) && !empty($decoded)) {
            return $decoded;
        }
        if (strpos($columns, '|') !== false) {
            [$idColumn, $valueColumn] = explode('|', $columns, 2);
            $idColumn = trim($idColumn);
            $valueColumn = trim($valueColumn) ?: $idColumn;
            if ($idColumn) {
                return [$idColumn => $valueColumn];
            }
        }
        return ['id' => 'name'];
    }
    /**
     * Generate dropdown options based on system, table, or token, and output format using Data facade.
     *
     * @param string $tokenOrTable Token or table name
     * @param string $output Output format ('html', 'array', or 'json')
     * @param array|string|null $columns Column mapping for value and display
     * @param array|null $condition Where conditions
     * @param array|null $selected Array of keys to mark as selected
     * @return string|array HTML options string, associative array, or array of value/view/is_selected/avatar/group/uid
     * @throws Exception
     */
    public function options(string $tokenOrTable, string $output, $columns = null, ?array $condition = [], ?array $selected = null)
    {
        try {
            $system = Skeleton::getUserSystem();
            if (!in_array($output, ['html', 'array', 'json'], true)) {
                throw new Exception('Invalid output format. Must be "html", "array", or "json".');
            }
            $table = $tokenOrTable;
            $reqSet = [];
            $tokenLength = config('skeleton.token_length', 27);
            if (
                strlen(substr($tokenOrTable, 0, strrpos($tokenOrTable, '_'))) === $tokenLength &&
                substr_count($tokenOrTable, '_') >= 3
            ) {
                $reqSet = Skeleton::resolveToken($tokenOrTable);
                if (!isset($reqSet['key']) || !isset($reqSet['table']) || !isset($reqSet['value'])) {
                    throw new Exception('Invalid token configuration.');
                }
                $table = $reqSet['table'];
                $system = $reqSet['system'];
                $columns = $columns ?? $reqSet['value'];
            }
            if (empty($table)) {
                throw new Exception('Table name or valid token is required.');
            }
            if (is_string($columns)) {
                $columns = $this->parseColumns($columns);
            }
            if (!is_array($columns) || empty($columns)) {
                $columns = ['id' => 'name'];
            }
            $idColumn = key($columns);
            $valueColumn = reset($columns);
            $data = Data::fetch($system, $table, $condition, 'all');
            $results = array_map(function ($row) use ($idColumn, $valueColumn, $selected) {
                $id = isset($row[$idColumn]) ? htmlspecialchars((string)$row[$idColumn]) : '';
                $text = isset($row[$valueColumn]) ? htmlspecialchars((string)$row[$valueColumn]) : '';
                $isSelected = $selected ? in_array((string)$id, array_map('strval', $selected), true) : false;
                return [
                    'value' => $id,
                    'view' => $text,
                    'is_selected' => $isSelected,
                    'avatar' => isset($row['avatar']) ? htmlspecialchars($row['avatar']) : '',
                    'group' => isset($row['group']) ? htmlspecialchars($row['group']) : '',
                    'uid' => isset($row['uid']) ? htmlspecialchars($row['uid']) : '',
                ];
            }, $data['data'] ?? []);
            if ($output === 'json') {
                return $results;
            }
            if ($output === 'array') {
                $assoc = [];
                foreach ($results as $item) {
                    $assoc[$item['value']] = $item['view'];
                }
                return $assoc;
            }
            $html = '';
            foreach ($results as $item) {
                $selectedAttr = $item['is_selected'] ? ' selected' : '';
                $html .= sprintf(
                    '<option value="%s"%s>%s</option>',
                    $item['value'],
                    $selectedAttr,
                    $item['view']
                );
            }
            return $html;
        } catch (Exception $e) {
            throw $e;
        }
    }
    
}