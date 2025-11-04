<?php
namespace App\Http\Helpers;
use App\Facades\{Data, Developer, Skeleton, FileManager};
use Exception;
use Illuminate\Support\Facades\{Auth, Cache, Config, Log, Carbon};
/**
 * Helper class for handling common data operations with robust exception handling.
 * Includes profile string generation, JSON manipulation, table rendering, OTP operations, and value retrieval.
 */
class ProfileHelper
{
    /**
     * Universal Users Data Provider
     *
     * Provides a unified way to fetch, filter, and transform user records.
     *
     * Features:
     * - Dynamic filtering (supports AND, OR, NOT, IN, fuzzy matches)
     * - Return formats: array | string | json | html
     * - Custom field selection (e.g. ['name','user_id','avatar'])
     * - Concatenated fields ("first_name last_name")
     * - Key/Value mapping (['user_id'=>'first_name last_name'])
     * - Injects avatar field from Filemanager::getFile() and caches with raw data
     * - Now injects fixed bg_color for each user based on Helper::colors('vibrant', 'background') using deterministic seeding
     * - Caching support with force fetch option
     *
     * @param array       $filters       Filtering conditions (supports AND/OR/NOT nesting)
     * @param mixed       $output        Output structure definition:
     *                                    - string: "first_name last_name"
     *                                    - array : ["user_id" => "first_name last_name"]
     *                                    - array : ["value" => "user_id", "view" => "name", "group" => "role"]
     * @param string|null $type          Output type: array|string|json|html (default: array)
     * @param bool|null   $forceDBFetch  Force DB fetch (ignore cache)
     *
     * @return array|string|JsonResponse
     */
    public function users(array $filters = [], $output = null, ?string $type = "array", ?bool $forceDBFetch = false)
    {
        try {
            /** ----------------------------------------------------
             *  1. Authenticated User Context
             * ---------------------------------------------------- */
            $user = Auth::guard('web')->user() ?? Auth::guard('sanctum')->user();
            if (!$user) {
                throw new Exception('User not authenticated.');
            }
            $business_id = $user->business_id;
            $cacheKey = "users_{$business_id}_set";
            /** ----------------------------------------------------
             *  2. Retrieve Cached Data or Fetch Fresh
             * ---------------------------------------------------- */
            $data = $forceDBFetch ? null : Cache::get($cacheKey);
            if (!$data) {
                $columns = [
                    'users.user_id AS user_id',
                    'users.business_id AS business_id',
                    'users.username AS username',
                    'users.email AS email',
                    'users.first_name AS first_name',
                    'users.last_name AS last_name',
                    'companies.name AS company',
                    'users.profile AS profile',
                    'users.cover AS cover',
                    'user_info.unique_code AS unique_code',
                    'user_info.gender AS gender',
                    'user_info.date_of_birth AS date_of_birth',
                    'roles.role_id AS role_id',
                    'roles.name AS role',
                    'scopes.scope_id AS scope_id',
                    'scopes.code AS code',
                    'scopes.name AS scope',
                    'scopes.group AS `group`',
                    'scopes.parent_id AS parent_id',
                ];
                $system = Skeleton::getUserSystem();
                $results = Data::query($system, 'users', [
                    'select' => $columns,
                    'where' => ['users.business_id' => $business_id],
                    'joins' => [
                        ['type' => 'left', 'table' => 'user_info', 'on' => [['users.user_id', 'user_info.user_id']]],
                        ['type' => 'left', 'table' => 'user_roles', 'on' => [['users.user_id', 'user_roles.user_id']]],
                        ['type' => 'left', 'table' => 'roles', 'on' => [['user_roles.role_id', 'roles.role_id']]],
                        ['type' => 'left', 'table' => 'scope_mapping', 'on' => [['users.user_id', 'scope_mapping.user_id']]],
                        ['type' => 'left', 'table' => 'scopes', 'on' => [['scope_mapping.scope_id', 'scopes.scope_id']]],
                        ['type' => 'left', 'table' => 'companies', 'on' => [['users.company_id', 'companies.company_id']]],
                    ],
                ]);
                $data = $results['data'] ?? [];
                /** Add Avatar and Fixed bg_color to Raw Data Before Caching */
                $data = array_map(function ($row) {
                    $row['avatar'] = !empty($row['profile']) ? FileManager::getFile($row['profile']) : null;
                    
                    // Generate fixed bg_color using deterministic seeding
                    $uid = $row['user_id'];
                    if (!empty($uid)) {
                        $seedStr = (string)$uid;
                        $seed = 0;
                        for ($i = 0; $i < strlen($seedStr); $i++) {
                            $seed = (($seed << 5) - $seed) + ord($seedStr[$i]);
                            $seed = $seed & 0xFFFFFFFF;
                        }
                        mt_srand(abs($seed));
                        $row['bg_color'] = Helper::colors('vibrant', 'background');
                        mt_srand(); // Reset to avoid global side effects
                    } else {
                        $row['bg_color'] = '#6c757d'; // Fallback
                    }
                    
                    return $row;
                }, $data);
                Cache::put($cacheKey, $data, now()->addHour());
            }
            /** ----------------------------------------------------
             *  3. Filtering Engine (AND/OR/NOT, IN, fuzzy)
             * ---------------------------------------------------- */
            $matchRow = function ($row, $filters) use (&$matchRow) {
                if (isset($filters['AND'])) {
                    foreach ($filters['AND'] as $sub) {
                        if (!$matchRow($row, $sub)) return false;
                    }
                    return true;
                }
                if (isset($filters['OR'])) {
                    foreach ($filters['OR'] as $sub) {
                        if ($matchRow($row, $sub)) return true;
                    }
                    return false;
                }
                if (isset($filters['NOT'])) {
                    return !$matchRow($row, $filters['NOT']);
                }
                foreach ($filters as $key => $val) {
                    $cell = $row[$key] ?? null;
                    if (is_array($val)) {
                        if (!in_array($cell, $val)) return false;
                    } elseif (is_string($val) && str_contains($val, '%')) {
                        $pattern = '/^' . str_replace('%', '.*', preg_quote($val, '/')) . '$/i';
                        if (!preg_match($pattern, (string)$cell)) return false;
                    } else {
                        if ($cell != $val) return false;
                    }
                }
                return true;
            };
            if (!empty($filters)) {
                $data = array_filter($data, fn($row) => $matchRow($row, $filters));
            }
            /** ----------------------------------------------------
             *  4. Output Transformer
             * ---------------------------------------------------- */
            $transformed = $data;
            if (is_string($output) && in_array($output, ['user:scope', 'user:role'])) {
                $groupField = $output === 'user:scope' ? 'scope' : 'role';
                $transformed = array_map(function ($row) use ($groupField) {
                    return [
                        'id'     => $row['user_id'] ?? null,
                        'value'  => $row['user_id'] ?? null,
                        'view'   => trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')),
                        'avatar' => $row['avatar'] ?? null,
                        'group'  => $row[$groupField] ?? null,
                        'bg_color' => $row['bg_color'] ?? '#6c757d'
                    ];
                }, $data);
            } elseif ($output) {
                if (is_string($output)) {
                    $parts = explode(" ", $output);
                    $transformed = array_map(function ($row) use ($parts) {
                        return trim(implode(" ", array_map(fn($p) => $row[$p] ?? '', $parts)));
                    }, $data);
                } elseif (is_array($output) && count($output) === 1 && !is_int(array_key_first($output))) {
                    $keyField = array_key_first($output);
                    $valFields = explode(" ", $output[$keyField]);
                    $transformed = [];
                    foreach ($data as $row) {
                        $key = $row[$keyField] ?? null;
                        if ($key) {
                            $val = implode(" ", array_map(fn($f) => $row[$f] ?? '', $valFields));
                            $transformed[$key] = trim($val);
                        }
                    }
                } elseif (is_array($output)) {
                    $transformed = [];
                    foreach ($data as $row) {
                        $entry = [];
                        foreach ($output as $outKey => $field) {
                            if (is_int($outKey)) $outKey = $field;
                            if ($field === 'name') {
                                $entry[$outKey] = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
                            } else {
                                $entry[$outKey] = $row[$field] ?? null;
                            }
                        }
                        // Always inject bg_color if not requested
                        if (!isset($entry['bg_color'])) {
                            $entry['bg_color'] = $row['bg_color'] ?? '#6c757d';
                        }
                        $transformed[] = $entry;
                    }
                }
            } else {
                // Default array output also includes bg_color
                $transformed = array_map(function ($row) {
                    $row['bg_color'] = $row['bg_color'] ?? '#6c757d';
                    return $row;
                }, $data);
            }
            /** ----------------------------------------------------
             *  5. Return Based on Output Type
             * ---------------------------------------------------- */
            switch (strtolower($type)) {
                case 'string':
                    return is_array($transformed) ? reset($transformed) : (string)$transformed;
                case 'json':
                    return response()->json(array_values($transformed));
                case 'html':
                    $html = '';
                    $defaultAvatar = asset("default/preview-square.svg");
                    foreach ($transformed as $row) {
                        if (is_array($row)) {
                            // Determine <option> value: Prefer 'value', fallback to 'id'
                            $optionValue = isset($row['value']) && $row['value'] !== '' ? $row['value'] : ($row['id'] ?? '');
                            // Determine display text: Prefer 'view', fallback to 'value' or 'id'
                            $displayText = isset($row['view']) && $row['view'] !== ''
                                ? $row['view']
                                : (isset($row['value']) ? $row['value'] : ($row['id'] ?? ''));
                            // Avatar fallback to default
                            $avatar = !empty($row['avatar']) ? $row['avatar'] : $defaultAvatar;
                            // Build additional data attributes
                            $avatarAttr = ' data-avatar="' . e($avatar) . '"';
                            $groupAttr  = isset($row['group']) && $row['group'] ? ' data-group="' . e($row['group']) . '"' : '';
                            $idAttr     = isset($row['id']) && $row['id'] ? ' data-id="' . e($row['id']) . '"' : '';
                            $bgColorAttr = isset($row['bg_color']) ? ' data-bg-color="' . e($row['bg_color']) . '"' : '';
                            $html .= '<option value="' . e($optionValue) . '"' . $avatarAttr . $groupAttr . $idAttr . $bgColorAttr . '>' . e($displayText) . '</option>';
                        } elseif (is_string($row)) {
                            $html .= '<option value="' . e($row) . '">' . e($row) . '</option>';
                        }
                    }
                    return $html;
                case 'array':
                default:
                    return $transformed;
            }
        } catch (Exception $e) {
            return $type === 'json'
                ? response()->json([])
                : [];
        }
    }

    public function userProfile($user_id, $layout = ['flex', 'sm', 'fs-12', 'fs-9'], $columns = [], $bool = false)
    {
        try {
            if (empty($user_id)) {
                return 'No User';
            }
            if (is_string($layout)) {
                $decodedLayout = json_decode($layout, true);
                if (json_last_error() === JSON_ERROR_NONE) $layout = $decodedLayout;
            }
            if (is_string($columns)) {
                $decodedColumns = json_decode($columns, true);
                if (json_last_error() === JSON_ERROR_NONE) $columns = $decodedColumns;
            }
            $user = $this->users(['user_id' => $user_id]);
            $user = !empty($user) ? reset($user) : null;
            $layoutType = strtolower($layout[0] ?? 'flex');
            $avatarSizeClass = 'avatar-' . strtolower($layout[1] ?? 'sm');
            $nameFontClass = $layout[2] ?? 'fs-12';
            $columnsFontClass = $layout[3] ?? 'fs-9';
            if ($user) {
                $fullName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
                $additionalColumns = [];
                foreach ((array)$columns as $column) {
                    $value = $user[$column] ?? null;
                    $additionalColumns[] = $value ?? "No " . ucfirst(str_replace('_', ' ', $column));
                }
                $additionalColumnsHtml = implode(' <span class="mx-1">|</span> ', $additionalColumns);
                $bgColor = $user['bg_color'] ?? '#00b4af'; // Use pre-assigned fixed color from users()
                if ($bool && !empty($user['profile'])) {
                    $avatarHtml = '<span class="avatar ' . $avatarSizeClass . ' avatar-rounded border rounded-circle">
                        <img src="' . FileManager::getFile($user['profile']) . '" alt="User Avatar" class="img-fluid rounded-circle">
                    </span>';
                } else {
                    $avatarHtml = '<span class="avatar ' . $avatarSizeClass . ' avatar-rounded border rounded-circle text-white d-flex justify-content-center align-items-center fw-semibold" style="background:' . $bgColor . '">
                        ' . Helper::textProfile($fullName, 2) . '
                    </span>';
                }
            } else {
                $fullName = 'Unknown User';
                $additionalColumnsHtml = '';
                $avatarHtml = '<span class="avatar ' . $avatarSizeClass . ' avatar-rounded border rounded-circle bg-danger text-white d-flex justify-content-center align-items-center fw-semibold">
                    <i class="ti ti-ban"></i>
                </span>';
            }
            if ($layoutType === 'line') {
                $html = '<a href="' . url('/') . '/t/user-management/user/::user_id::"><div class="d-flex flex-column align-items-center text-center">
                            ' . $avatarHtml . '
                            <div class="' . e($nameFontClass) . ' fw-semibold mt-2">' . e($fullName) . '</div>
                            <div class="text-muted ' . e($columnsFontClass) . '">' . $additionalColumnsHtml . '</div>
                        </div></a>';
            } else {
                $html = '<a href="' . url('/') . '/t/user-management/user/::user_id::"><div class="d-flex align-items-center">
                            ' . $avatarHtml . '
                            <div class="ms-2">
                                <div class="' . e($nameFontClass) . ' fw-semibold">' . e($fullName) . '</div>
                                <div class="text-muted ' . e($columnsFontClass) . '">' . $additionalColumnsHtml . '</div>
                            </div>
                        </div></a>';
            }
            return $html;
        } catch (\Throwable $e) {
            return '<div class="text-danger">Error loading user profile</div>';
        }
    }
    /**
     * Get all child roles (recursively) for a given role_id.
     *
     * @param string $roleId  Parent role ID (e.g., 'ADMIN', 'MANAGER', etc.)
     * @param bool $includeSelf Optional — include the given role itself in the result
     * @return array  Array of [role_id => role_name]
     * @throws Exception
     */
    public static function getChildRoles(string $type = 'role', ?string $roleId = null, bool $includeSelf = false): array
    {
        $system = Skeleton::getUserSystem();

        // Fetch all active roles
        $rolesData = Data::fetch($system, 'roles', [
            'where' => ['is_active' => 1]
        ]);

        if (!$rolesData['status']) {
            throw new Exception('Failed to fetch roles: ' . $rolesData['message']);
        }

        $roles = $rolesData['data'];

        // Build parent → children map and role map
        $childrenMap = [];
        $map = [];
        foreach ($roles as $role) {
            $map[$role['role_id']] = $role;
            if (!empty($role['parent_role_id'])) {
                $childrenMap[$role['parent_role_id']][] = $role;
            }
        }

        // Case 1: Return all roles if type='all' and no roleId provided
        if ($type === 'all' && $roleId === null) {
            $allRoles = [];
            foreach ($roles as $role) {
                $allRoles[$role['role_id']] = $role['name'];
            }
            return $allRoles;
        }

        // Case 2: Return child roles for a specific roleId
        if (empty($roleId)) {
            throw new Exception('Role ID is required when type is not "all".');
        }

        // Recursive function to collect children
        $collect = function ($parentId) use (&$collect, $childrenMap) {
            $result = [];
            if (isset($childrenMap[$parentId])) {
                foreach ($childrenMap[$parentId] as $child) {
                    $result[$child['role_id']] = $child['name'];
                    $result += $collect($child['role_id']);
                }
            }
            return $result;
        };

        $result = $collect($roleId);

        // Include the parent role if requested
        if ($includeSelf && isset($map[$roleId])) {
            $result = [$roleId => $map[$roleId]['name']] + $result;
        }

        return $result;
    }



}