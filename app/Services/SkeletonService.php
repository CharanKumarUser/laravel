<?php

namespace App\Services;

use App\Facades\{BusinessDB, CentralDB, Database, Developer};
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\{Auth, Cache, Config, Session, DB};
use Illuminate\Database\Connection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Throwable;
use Exception;

/**
 * Manages skeleton-related data, permissions, roles, and navigation for authenticated and unauthenticated users.
 */
class SkeletonService
{
    protected const AUTH_SESSION_PREFIX = 'skeleton_tokens_auth_';
    protected const UNAUTH_SESSION_PREFIX = 'skeleton_tokens_unauth_';
    // ----------------------------------- User-Related Functions -----------------------------------
    /**
     * Retrieves the authenticated user with comprehensive user-related data.
     *
     * @param User|null $user The user object to use (optional, defaults to Auth guard).
     * @param bool $throwIfUnauthenticated Whether to throw an exception if no user is authenticated.
     * @param string|null $roleId The role ID to set as active (optional).
     * @param bool $forceReload Whether to force a full database reload, bypassing session data (default: false).
     * @return array|null The enriched user data array or null if not authenticated and not required to throw.
     * @throws AuthenticationException If unauthenticated and $throwIfUnauthenticated is true.
     * @throws RuntimeException If critical data retrieval fails.
     * @throws InvalidArgumentException If invalid roleId or user data is provided.
     */
    public function getAuthenticatedUser(): ?array
    {
        try {
            // Step 1: Authenticate user
            $user = $user ?? (Auth::guard('web')->check()
                ? Auth::guard('web')->user()
                : (Auth::guard('sanctum')->check() ? Auth::guard('sanctum')->user() : null));
            if (!$user) {
                return null;
            }
            // Step 2: Load from session if available and not forced to reload
            $sessionKey = 'auth_user_data_' . $user->user_id;
            $userSet = [];
            $cachedData = Session::get($sessionKey) ?? null;
            if ($cachedData && $this->isValidCachedData($cachedData)) {
                $userSet['user'] = $cachedData['user'];
                $userSet['system'] = $cachedData['system'];
                $userSet['connection'] = Database::getConnection($user->business_id);
                $userSet['role'] = $cachedData['role'];
                $userSet['roles'] = $cachedData['roles'];
                $userSet['permissions'] = $cachedData['permissions'];
                $userSet['navigation'] = $cachedData['navigation'];
                return $userSet;
            }
            // Step 3: Determine system
            try {
                $system = CentralDB::table('business_systems')
                    ->where('business_id', $user->business_id)
                    ->value('system') ?: 'business';
            } catch (Throwable $e) {
                Developer::error('Failed to determine user system', [
                    'user_id' => $user->user_id,
                    'business_id' => $user->business_id,
                    'error' => $e->getMessage(),
                ]);
                $system = 'business';
            }
            // Step 4: Get connection
            try {
                $connection = Database::getConnection($user->business_id);
            } catch (Throwable $e) {
                throw new RuntimeException('Failed to establish database connection: ' . $e->getMessage());
            }
            // Step 5: Get roles
            try {
                $roles = [];
                $roleQuery = $connection->table('user_roles')
                    ->join('roles', 'user_roles.role_id', '=', 'roles.role_id')
                    ->where('user_roles.user_id', $user->user_id)
                    ->whereNull('user_roles.deleted_at')
                    ->where('user_roles.is_active', 1)
                    ->where('roles.is_active', 1)
                    ->whereNull('roles.deleted_at')
                    ->select('roles.id', 'roles.role_id', 'roles.name', 'user_roles.is_active');
                $roleResults = $roleQuery->get();
                foreach ($roleResults as $role) {
                    if (!isset($role->role_id, $role->id, $role->name)) {
                        Developer::warning('Skipping invalid role record', [
                            'user_id' => $user->user_id,
                            'role_data' => json_encode($role),
                        ]);
                        continue;
                    }
                    $roles[trim($role->role_id)] = [
                        'id' => $role->id,
                        'role_id' => trim($role->role_id),
                        'name' => trim($role->name),
                        'active' => (int) $role->is_active,
                    ];
                }
                if (empty($roles)) {
                    Developer::error('No active roles found for user', [
                        'user_id' => $user->user_id,
                    ]);
                    throw new RuntimeException('No active roles found for user.');
                }
            } catch (Throwable $e) {
                Developer::error('Failed to fetch user roles', [
                    'user_id' => $user->user_id,
                    'error' => $e->getMessage(),
                ]);
                throw new RuntimeException('Failed to fetch user roles: ' . $e->getMessage());
            }
            // Step 6: Resolve active role
            try {
                $activeRoleId = $roleId ?? null;
                if ($activeRoleId && !isset($roles[$activeRoleId])) {
                    throw new InvalidArgumentException('Invalid role ID provided.');
                }
                if (!$activeRoleId) {
                    // Find first explicitly active role
                    foreach ($roles as $role) {
                        if ($role['active'] === 1) {
                            $activeRoleId = $role['role_id'];
                            break;
                        }
                    }
                    // If no active role was found, fall back to the first role
                    if (!$activeRoleId) {
                        $firstRole = reset($roles);
                        $activeRoleId = $firstRole['role_id'];
                    }
                }
                $activeRole = $roles[$activeRoleId];
            } catch (Throwable $e) {
                Developer::error('Failed to resolve active role', [
                    'user_id' => $user->user_id,
                    'error' => $e->getMessage(),
                ]);
                throw new RuntimeException('Failed to resolve active role: ' . $e->getMessage());
            }
            // Step 7: Fetch permissions
            try {
                $rolePermissions = $connection->table('role_permissions')
                    ->join('permissions', 'role_permissions.permission_id', '=', 'permissions.permission_id')
                    ->where('permissions.is_approved', 1)
                    ->where('role_permissions.is_active', 1)
                    ->where('role_permissions.role_id', $activeRoleId)
                    ->pluck('permissions.name')
                    ->map(fn($name) => is_string($name) ? trim($name) : null)
                    ->filter()
                    ->toArray();
                $userPermissionsData = $connection->table('user_permissions')
                    ->join('permissions', 'user_permissions.permission_id', '=', 'permissions.permission_id')
                    ->where('permissions.is_approved', 1)
                    ->where('user_permissions.is_active', 1)
                    ->where('user_permissions.user_id', $user->user_id)
                    ->select(['permissions.name', 'user_permissions.is_restricted'])
                    ->get();
                $userPermissions = [];
                $restrictedPermissions = [];
                foreach ($userPermissionsData as $perm) {
                    if (!isset($perm->name) || !is_string($perm->name)) {
                        continue;
                    }
                    $permName = trim($perm->name);
                    if ($perm->is_restricted) {
                        $restrictedPermissions[] = $permName;
                    } else {
                        $userPermissions[] = $permName;
                    }
                }
                $permissions = array_values(array_unique(array_diff(
                    array_merge($rolePermissions, $userPermissions),
                    $restrictedPermissions
                )));
            } catch (Throwable $e) {
                throw new RuntimeException('Failed to fetch permissions data: ' . $e->getMessage());
            }
            // Step 8: Build navigation based on permissions
            try {
                $skeletonData = $this->getSkeletonData();
                $modules = collect($skeletonData['modules'] ?? []);
                $sections = collect($skeletonData['sections'] ?? []);
                $items = collect($skeletonData['items'] ?? []);
                $navigation = [];
                foreach ($modules as $module) {
                    $moduleDisplay = !empty($module['display']) ? $module['display'] : ($module['name'] ?? null);
                    $moduleName = $module['name'] ?? null;
                    if (!$moduleName || ($module['navigable'] ?? 0) != 1 || !in_array("view:{$moduleName}", $permissions)) {
                        continue;
                    }
                    $moduleSections = [];
                    foreach ($sections->where('module_id', $module['module_id']) as $section) {
                        $sectionDisplay = !empty($section['display']) ? $section['display'] : ($section['name'] ?? null);
                        $sectionName = $section['name'] ?? null;
                        if (!$sectionName || ($section['navigable'] ?? 0) != 1 || !in_array("view:{$moduleName}::{$sectionName}", $permissions)) {
                            continue;
                        }
                        $sectionItems = [];
                        foreach ($items->where('section_id', $section['section_id']) as $item) {
                            $itemDisplay = !empty($item['display']) ? $item['display'] : ($item['name'] ?? null);
                            $itemName = $item['name'] ?? null;
                            if (!$itemName || ($item['navigable'] ?? 0) != 1 || !in_array("view:{$moduleName}::{$sectionName}::{$itemName}", $permissions)) {
                                continue;
                            }
                            $sectionItems[] = [
                                'name' => $itemDisplay,
                                'route' => url('/') . '/' .
                                    Str::kebab($moduleName) . '/' .
                                    Str::kebab($sectionName) . '/' .
                                    Str::kebab($itemName),
                                'icon' => $item['icon'] ?? null,
                            ];
                        }
                        $moduleSections[] = [
                            'name' => $sectionDisplay,
                            'route' => url('/') . '/' .
                                Str::kebab($moduleName) . '/' .
                                Str::kebab($sectionName),
                            'icon' => $section['icon'] ?? null,
                            'items' => $sectionItems,
                        ];
                    }
                    if (!empty($moduleSections)) {
                        $navigation[] = [
                            'name' => $moduleDisplay,
                            'icon' => $module['icon'] ?? 'bi bi-grid',
                            'sections' => $moduleSections,
                        ];
                    }
                }
            } catch (Throwable $e) {
                $navigation = [];
            }
            // Step 9: Build user data set
            $dbUser = $connection->table('users')->where('user_id', $user->user_id)->first();
            if (!$dbUser) {
                throw new RuntimeException("User not found in tenant database.");
            }
            $userSet = [
                'user' => $dbUser,
                'system' => $system,
                'connection' => $connection,
                'role' => $activeRole,
                'roles' => $roles,
                'permissions' => $permissions,
                'navigation' => $navigation,
            ];
            // Step 10: Store in session
            try {
                Session::put($sessionKey, [
                    'user' => $dbUser,
                    'system' => $system,
                    'role' => $activeRole,
                    'roles' => $roles,
                    'permissions' => $permissions,
                    'navigation' => $navigation,
                ]);
            } catch (Throwable $e) {
                Developer::error('Failed to store user session data', [
                    'user_id' => $user->user_id,
                    'error' => $e->getMessage(),
                ]);
            }
            return $userSet;
        } catch (AuthenticationException | InvalidArgumentException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new RuntimeException('Failed to retrieve authenticated user data: ' . $e->getMessage());
        }
    }
    /**
     * Retrieves specific authenticated user data or the User object.
     *
     * @param string|null $set The specific data to return (e.g., 'system', 'navigation'). If null, returns the User object.
     * @param User|null $authUser The user object to use (optional, defaults to Auth guard).
     * @param bool $throwIfUnauthenticated Whether to throw an exception if no user is authenticated.
     * @param string|null $roleId The role ID to set as active (optional).
     * @param bool $forceReload Whether to force a full database reload, bypassing session data (default: false).
     * @return mixed The User object (if $set is null) or the requested data set, or null if not authenticated and not required to throw.
     * @throws AuthenticationException If unauthenticated and $throwIfUnauthenticated is true.
     * @throws RuntimeException If critical data retrieval fails.
     * @throws InvalidArgumentException If invalid roleId, user data, or $set is provided.
     */
    public function authUser(?string $set = null)
    {
        $validSets = ['user', 'system', 'connection', 'role', 'roles', 'permissions', 'navigation'];
        if ($set !== null && !in_array($set, $validSets, true)) {
            throw new InvalidArgumentException("Invalid data set requested: {$set}");
        }
        // Fetch the full data set
        $userSet = $this->getAuthenticatedUser();
        // If no authenticated user, return null
        if ($userSet === null) {
            return null;
        }
        // If $set is null, we want to return the user (or empty stdClass if missing)
        if ($set === null) {
            return $userSet['user'] ?? new \stdClass();
        }
        // Return the requested subset
        return $userSet[$set] ?? null;
    }
    /**
     * Validates cached session data for completeness and integrity.
     *
     * @param mixed $data The cached session data.
     * @return bool True if valid, false otherwise.
     */
    protected function isValidCachedData($data): bool
    {
        if (!is_array($data)) {
            return false;
        }
        $requiredKeys = ['system', 'roles', 'permissions', 'role', 'navigation'];
        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $data)) {
                return false;
            }
        }
        // Validate critical fields
        if (!is_string($data['system']) || empty($data['system'])) {
            return false;
        }
        if (!is_array($data['roles']) || empty($data['roles'])) {
            return false;
        }
        if (!is_array($data['permissions']) || !is_array($data['role'])) {
            return false;
        }
        if (!isset($data['role']['id'], $data['role']['name'])) {
            return false;
        }
        return true;
    }
    /**
     * Determines the user's system based on their role or returns 'lander' for unauthenticated users.
     *
     * @return string The system identifier (e.g., 'business') or 'lander' if unauthenticated.
     * @throws RuntimeException If critical data retrieval fails unexpectedly.
     */
    public function getUserSystem(): string
    {
        try {
            $system = $this->authUser('system');
            return $system ?? 'lander';
        } catch (AuthenticationException $e) {
            return 'lander';
        } catch (InvalidArgumentException | RuntimeException $e) {
            Developer::error('Failed to determine user system', ['error' => $e->getMessage()]);
            return 'lander';
        }
    }
    // ----------------------------------- Permissions-Related Functions -----------------------------------
    /**
     * Checks if a user has specific permission(s).
     *
     * @param string|array $permissions
     * @param User|null $user
     * @return bool
     */
    public function hasPermission($permissions): bool
    {
        try {
            $userSet = $this->authUser('permissions');
            if (!$userSet) {
                return false;
            }
            // Normalize input permissions
            $permissions = (array) $permissions;
            if (is_string($permissions[0])) {
                $permissions = array_map('trim', explode(',', str_replace(' ', '', implode(',', $permissions))));
            }
            $permissions = array_map(fn($p) => strtolower(str_replace(' ', '', $p)), $permissions);
            if (empty($permissions)) {
                return false;
            }
            // Normalize user permissions
            $userPermissions = $userSet ?? [];
            $userPermissions = array_map(fn($p) => strtolower(str_replace(' ', '', $p)), $userPermissions);
            return !array_diff($permissions, $userPermissions);
        } catch (AuthenticationException | InvalidArgumentException | RuntimeException $e) {
            return false;
        }
    }
    /**
     * Alias for hasPermission for Controller consistency.
     *
     * @param string|array $permissions
     * @param User|null $user
     * @return bool
     */
    public function can($permissions): bool
    {
        return $this->hasPermission($permissions);
    }
    /**
     * Checks if a user has at least one of the given permissions.
     *
     * @param string|array $permissions
     * @param User|null $user
     * @return bool
     */
    public function hasAnyPermission($permissions): bool
    {
        try {
            $userSet = $this->authUser('permissions');
            if (!$userSet) {
                return false;
            }
            $permissions = (array) $permissions;
            if (is_string($permissions[0])) {
                $permissions = array_map('trim', explode(',', str_replace(' ', '', implode(',', $permissions))));
            }
            $permissions = array_map(fn($p) => strtolower(str_replace(' ', '', $p)), $permissions);
            if (empty($permissions)) {
                return false;
            }
            $userPermissions = $userSet ?? [];
            $userPermissions = array_map(fn($p) => strtolower(str_replace(' ', '', $p)), $userPermissions);
            return (bool) array_intersect($permissions, $userPermissions);
        } catch (AuthenticationException | InvalidArgumentException | RuntimeException $e) {
            return false;
        }
    }
    /**
     * Alias for hasAnyPermission for Controller consistency.
     *
     * @param string|array $permissions
     * @param User|null $user
     * @return bool
     */
    public function has($permissions): bool
    {
        return $this->hasAnyPermission($permissions);
    }
    /**
     * Loads permissions in a grouped JSON structure.
     *
     * @param string $set Scope of permissions: 'all', 'self', 'all-<business_id>'
     * @param string|null $type Type of permissions: 'all', 'user', 'role', null
     * @param string|null $specific Type of ID: 'user-id', 'role-id', null
     * @param string|null $id User_id or role_id
     * @return array JSON-compatible array of grouped permissions
     * @throws InvalidArgumentException If parameters are invalid
     * @throws AuthenticationException If authentication is required
     * @throws RuntimeException If database queries fail
     */
    public function loadPermissions(string $set, ?string $type = null, ?string $specific = null, ?string $id = null): array
    {
        try {
            // Validate parameters
            $validTypes = ['all', 'user', 'role', null];
            $validSpecifics = ['user-id', 'role-id', null];
            if (!in_array($type, $validTypes)) {
                throw new InvalidArgumentException("Invalid type parameter. Must be one of: " . implode(', ', array_filter($validTypes)));
            }
            if (!in_array($specific, $validSpecifics)) {
                throw new InvalidArgumentException("Invalid specific parameter. Must be one of: " . implode(', ', array_filter($validSpecifics)));
            }
            if (($specific === null) !== ($id === null)) {
                throw new InvalidArgumentException("Specific and id must both be null or both provided.");
            }
            if ($id && empty(trim($id))) {
                throw new InvalidArgumentException("ID cannot be empty when provided.");
            }
            if ($set === 'self' && $type !== null && !$specific) {
                throw new InvalidArgumentException("Specific is required when type is provided for set 'self'.");
            }
            // Determine database connection and business_id
            $businessId = 'CENTRAL';
            $connection = null;
            if ($set === 'all' || $set === 'self') {
                $userSet = $this->authUser();
                if (!$userSet) {
                    throw new AuthenticationException('No authenticated user found.');
                }
                $businessId = $userSet->business_id ?? 'CENTRAL';
                $connection = Database::getConnection($businessId);
            } elseif (strpos($set, 'all-') === 0) {
                $businessId = substr($set, 4);
                if (empty($businessId)) {
                    throw new InvalidArgumentException("Invalid business_id in set '$set'.");
                }
                $connection = Database::getConnection($businessId);
            } else {
                throw new InvalidArgumentException("Invalid set parameter. Must be 'all', 'self', or 'all-<business_id>'.");
            }
            // Verify connection
            if (!$connection->getPdo() || !$connection->getDatabaseName()) {
                throw new RuntimeException("Database connection not properly initialized for business_id: {$businessId}.");
            }
            // Initialize variables
            $authUserSet = $this->authUser();
            $authUserId = $authUserSet ? $authUserSet->user_id : null;
            $targetId = $id ?? ($set === 'self' ? $authUserId : null);
            $checkType = $type ?? 'all';
            $checkTargetId = $targetId;
            // Step 1: Fetch permissions based on set
            $permissionDetails = [];
            $allPermissions = [];
            $userPermissions = [];
            $rolePermissions = [];
            $restrictedPermissions = [];
            if ($set === 'self') {
                if (!$authUserSet) {
                    throw new AuthenticationException('No authenticated user found.');
                }
                // For self, fetch authenticated user's permissions
                $authUserPermissions = [];
                $authRolePermissions = [];
                $authRestrictedPermissions = [];
                // Fetch authenticated user's user permissions
                $authUserPermissionsData = $connection->table('user_permissions')
                    ->join('permissions', 'user_permissions.permission_id', '=', 'permissions.permission_id')
                    ->where('user_permissions.user_id', $authUserId)
                    ->where('permissions.is_approved', 1)
                    ->select('permissions.name', 'permissions.permission_id', 'permissions.is_skeleton', 'user_permissions.is_restricted')
                    ->get();
                foreach ($authUserPermissionsData as $perm) {
                    if (!is_string($perm->name)) {
                        continue;
                    }
                    $permName = trim($perm->name);
                    $permData = [
                        'is_skeleton' => $perm->is_skeleton,
                        'permission_id' => $perm->permission_id,
                        'type' => 'user',
                    ];
                    if ($perm->is_restricted) {
                        $authRestrictedPermissions[$permName] = $permData;
                    } else {
                        $authUserPermissions[$permName] = $permData;
                    }
                }
                // Fetch authenticated user's role permissions
                $authRoleQuery = $connection->table('role_permissions')
                    ->join('permissions', 'role_permissions.permission_id', '=', 'permissions.permission_id')
                    ->join('user_roles', 'role_permissions.role_id', '=', 'user_roles.role_id')
                    ->where('user_roles.user_id', $authUserId)
                    ->where('role_permissions.is_active', 1)
                    ->where('user_roles.is_active', 1)
                    ->where('permissions.is_approved', 1)
                    ->select('permissions.name', 'permissions.permission_id', 'permissions.is_skeleton');
                $authRolePermissions = $authRoleQuery->get()
                    ->map(function ($perm) {
                        return [
                            'name' => is_string($perm->name) ? trim($perm->name) : null,
                            'permission_id' => $perm->permission_id,
                            'is_skeleton' => $perm->is_skeleton,
                            'type' => 'role',
                        ];
                    })
                    ->filter(fn($perm) => !is_null($perm['name']))
                    ->keyBy('name')
                    ->toArray();
                // Combine authenticated user's permissions
                $permissionDetails = array_merge($authRolePermissions, $authUserPermissions);
                $allPermissions = array_unique(array_merge(
                    array_keys($authRolePermissions),
                    array_keys($authUserPermissions)
                ));
                $allPermissions = array_diff($allPermissions, array_keys($authRestrictedPermissions));
                // If checking another user/role, fetch their permissions for check status
                if ($id && $id !== $authUserId) {
                    $userPermissions = [];
                    $rolePermissions = [];
                    $restrictedPermissions = [];
                    if (in_array($checkType, ['all', 'user'])) {
                        $userPermissionsData = $connection->table('user_permissions')
                            ->join('permissions', 'user_permissions.permission_id', '=', 'permissions.permission_id')
                            ->where('user_permissions.user_id', $checkTargetId)
                            ->where('permissions.is_approved', 1)
                            ->select('permissions.name', 'permissions.permission_id', 'permissions.is_skeleton', 'user_permissions.is_restricted')
                            ->get();
                        foreach ($userPermissionsData as $perm) {
                            if (!is_string($perm->name)) {
                                continue;
                            }
                            $permName = trim($perm->name);
                            $permData = [
                                'is_skeleton' => $perm->is_skeleton,
                                'permission_id' => $perm->permission_id,
                                'type' => 'user',
                            ];
                            if ($perm->is_restricted) {
                                $restrictedPermissions[$permName] = $permData;
                            } else {
                                $userPermissions[$permName] = $permData;
                            }
                        }
                    }
                    if (in_array($checkType, ['all', 'role'])) {
                        $roleQuery = $connection->table('role_permissions')
                            ->join('permissions', 'role_permissions.permission_id', '=', 'permissions.permission_id')
                            ->where('role_permissions.is_active', 1)
                            ->where('permissions.is_approved', 1);
                        if ($specific === 'user-id') {
                            $roleQuery->join('user_roles', 'role_permissions.role_id', '=', 'user_roles.role_id')
                                ->where('user_roles.user_id', $checkTargetId)
                                ->where('user_roles.is_active', 1);
                        } elseif ($specific === 'role-id') {
                            $roleQuery->where('role_permissions.role_id', $checkTargetId);
                        }
                        $rolePermissions = $roleQuery->select('permissions.name', 'permissions.permission_id', 'permissions.is_skeleton')
                            ->get()
                            ->map(function ($perm) {
                                return [
                                    'name' => is_string($perm->name) ? trim($perm->name) : null,
                                    'permission_id' => $perm->permission_id,
                                    'is_skeleton' => $perm->is_skeleton,
                                    'type' => 'role',
                                ];
                            })
                            ->filter(fn($perm) => !is_null($perm['name']))
                            ->keyBy('name')
                            ->toArray();
                    }
                }
                // For type-specific checks, filter permissions
                if ($checkType === 'user') {
                    $allPermissions = array_keys($authUserPermissions);
                    $allPermissions = array_diff($allPermissions, array_keys($authRestrictedPermissions));
                } elseif ($checkType === 'role') {
                    $allPermissions = array_keys($authRolePermissions);
                }
                if (empty($allPermissions)) {
                    return [];
                }
            } else {
                // For all and all-<business_id>, fetch all permissions
                $role = $this->authUser('role')['role_id'];
                $permissionDetailsQuery = $connection->table('permissions')
                    ->where('is_approved', 1);
                $permissionDetails = $permissionDetailsQuery
                    ->select('name', 'is_skeleton', 'permission_id')
                    ->get()
                    ->keyBy('name')
                    ->map(function ($perm) {
                        return [
                            'is_skeleton' => $perm->is_skeleton,
                            'permission_id' => $perm->permission_id,
                            'type' => 'business',
                        ];
                    })
                    ->toArray();
                $allPermissions = array_keys($permissionDetails);
                // Fetch assigned permissions if id is provided
                if ($id && $specific) {
                    if (in_array($checkType, ['all', 'user'])) {
                        $userPermissionsData = $connection->table('user_permissions')
                            ->join('permissions', 'user_permissions.permission_id', '=', 'permissions.permission_id')
                            ->where('user_permissions.user_id', $checkTargetId)
                            ->where('user_permissions.is_active', 1)
                            ->where('permissions.is_approved', 1)
                            ->select('permissions.name', 'permissions.permission_id', 'permissions.is_skeleton', 'user_permissions.is_restricted')
                            ->get();
                        foreach ($userPermissionsData as $perm) {
                            if (!is_string($perm->name)) {
                                continue;
                            }
                            $permName = trim($perm->name);
                            $permData = [
                                'is_skeleton' => $perm->is_skeleton,
                                'permission_id' => $perm->permission_id,
                                'type' => 'user',
                            ];
                            if ($perm->is_restricted) {
                                $restrictedPermissions[$permName] = $permData;
                            } else {
                                $userPermissions[$permName] = $permData;
                            }
                        }
                    }
                    if (in_array($checkType, ['all', 'role'])) {
                        $roleQuery = $connection->table('role_permissions')
                            ->join('permissions', 'role_permissions.permission_id', '=', 'permissions.permission_id')
                            ->where('role_permissions.is_active', 1)
                            ->where('permissions.is_approved', 1);
                        if ($specific === 'user-id') {
                            $roleQuery->join('user_roles', 'role_permissions.role_id', '=', 'user_roles.role_id')
                                ->where('user_roles.user_id', $checkTargetId)
                                ->where('user_roles.is_active', 1);
                        } elseif ($specific === 'role-id') {
                            $roleQuery->where('role_permissions.role_id', $checkTargetId);
                        }
                        $rolePermissions = $roleQuery->select('permissions.name', 'permissions.permission_id', 'permissions.is_skeleton')
                            ->get()
                            ->map(function ($perm) {
                                return [
                                    'name' => is_string($perm->name) ? trim($perm->name) : null,
                                    'permission_id' => $perm->permission_id,
                                    'is_skeleton' => $perm->is_skeleton,
                                    'type' => 'role',
                                ];
                            })
                            ->filter(fn($perm) => !is_null($perm['name']))
                            ->keyBy('name')
                            ->toArray();
                    }
                    // Update permission details with assigned permissions
                    $permissionDetails = array_merge($permissionDetails, $rolePermissions, $userPermissions);
                }
            }
            // Step 2: Parse and group permissions
            $grouped = [];
            $allActions = ['create', 'view', 'edit', 'delete', 'import', 'export', 'update'];
            foreach ($allPermissions as $permName) {
                if (!isset($permissionDetails[$permName]) || !is_string($permName)) {
                    continue;
                }
                $permId = $permissionDetails[$permName]['permission_id'];
                $isSkeleton = $permissionDetails[$permName]['is_skeleton'];
                $permType = $permissionDetails[$permName]['type'];
                // Split permission name into action and path
                $parts = explode(':', $permName, 2);
                if (count($parts) < 2) {
                    continue;
                }
                $action = trim($parts[0]);
                $pathString = trim($parts[1]);
                if (!in_array($action, $allActions)) {
                    continue;
                }
                // Split path into module, section, item
                $path = array_map('trim', explode('::', $pathString));
                $module = $path[0] ?? null;
                $section = $path[1] ?? null;
                $item = $path[2] ?? null;
                if (!$module) {
                    continue;
                }
                // Initialize module
                if (!isset($grouped[$module])) {
                    $grouped[$module] = ['permissions' => array_fill_keys($allActions, [])];
                }
                // Determine permission status
                $status = 0;
                if ($set === 'self') {
                    if ($id === null || $id === $authUserId) {
                        // For authenticated user or no id, check = 1 for assigned permissions
                        if ($checkType === 'user' && isset($authUserPermissions[$permName]) && !isset($authRestrictedPermissions[$permName])) {
                            $status = 0;
                        } elseif ($checkType === 'role' && isset($authRolePermissions[$permName])) {
                            $status = 0;
                        } elseif ($checkType === 'all' && (isset($authUserPermissions[$permName]) || isset($authRolePermissions[$permName])) && !isset($authRestrictedPermissions[$permName])) {
                            $status = 0;
                        }
                    } else {
                        // For other user/role, check target id's permissions
                        if ($checkType === 'user' && isset($userPermissions[$permName]) && !isset($restrictedPermissions[$permName])) {
                            $status = 1;
                        } elseif ($checkType === 'role' && isset($rolePermissions[$permName])) {
                            $status = 1;
                        } elseif ($checkType === 'all' && (isset($userPermissions[$permName]) || isset($rolePermissions[$permName])) && !isset($restrictedPermissions[$permName])) {
                            $status = 1;
                        }
                    }
                } else {
                    if ($id && $specific) {
                        if ($checkType === 'user' && isset($userPermissions[$permName]) && !isset($restrictedPermissions[$permName])) {
                            $status = 1;
                        } elseif ($checkType === 'role' && isset($rolePermissions[$permName])) {
                            $status = 1;
                        } elseif ($checkType === 'all' && (isset($userPermissions[$permName]) || isset($rolePermissions[$permName])) && !isset($restrictedPermissions[$permName])) {
                            $status = 1;
                        }
                    }
                }
                $permissionData = [
                    'check' => $status,
                    'is_skeleton' => $isSkeleton,
                    'type' => $permType,
                ];
                if (!$section) {
                    // Module-level permission
                    $grouped[$module]['permissions'][$action][$permId] = $permissionData;
                    continue;
                }
                // Initialize section
                if (!isset($grouped[$module][$section])) {
                    $grouped[$module][$section] = ['permissions' => array_fill_keys($allActions, [])];
                }
                if (!$item) {
                    // Section-level permission
                    $grouped[$module][$section]['permissions'][$action][$permId] = $permissionData;
                    continue;
                }
                // Initialize item
                if (!isset($grouped[$module][$section][$item])) {
                    $grouped[$module][$section][$item] = ['permissions' => array_fill_keys($allActions, [])];
                }
                // Item-level permission
                $grouped[$module][$section][$item]['permissions'][$action][$permId] = $permissionData;
            }
            // Step 3: Ensure all levels have permissions initialized
            foreach ($grouped as $module => &$moduleData) {
                if (!isset($moduleData['permissions'])) {
                    $moduleData['permissions'] = array_fill_keys($allActions, []);
                }
                foreach ($moduleData as $section => &$sectionData) {
                    if ($section === 'permissions') {
                        continue;
                    }
                    if (!isset($sectionData['permissions'])) {
                        $sectionData['permissions'] = array_fill_keys($allActions, []);
                    }
                    foreach ($sectionData as $item => &$itemData) {
                        if ($item === 'permissions') {
                            continue;
                        }
                        if (!isset($itemData['permissions'])) {
                            $itemData['permissions'] = array_fill_keys($allActions, []);
                        }
                    }
                }
            }
            return $grouped;
        } catch (AuthenticationException | InvalidArgumentException | RuntimeException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new RuntimeException("Failed to load permissions: " . $e->getMessage());
        }
    }
    /**
     * Creates or updates permissions for a user or role.
     *
     * @param string $type Type of permission: 'user' or 'role'
     * @param string $id User_id or role_id
     * @param array $permissions Array of permission_id values
     * @param string|null $business_id Business ID for database connection, null for self
     * @return bool True on success
     * @throws InvalidArgumentException If parameters are invalid
     * @throws AuthenticationException If authentication is required for self
     * @throws RuntimeException If database queries fail
     */
    public function managePermissions(string $type, string $id, array $permissions, ?string $business_id = null): bool
    {
        try {
            // Validate parameters
            if (!in_array($type, ['user', 'role'])) {
                throw new InvalidArgumentException("Invalid type parameter. Must be 'user' or 'role'.");
            }
            if (empty(trim($id))) {
                throw new InvalidArgumentException("ID cannot be empty.");
            }
            foreach ($permissions as $permId) {
                if (!is_string($permId) || empty(trim($permId))) {
                    throw new InvalidArgumentException("All permission IDs must be non-empty strings.");
                }
            }
            // Determine database connection
            $connection = null;
            if ($business_id !== null) {
                if (empty(trim($business_id))) {
                    throw new InvalidArgumentException("Business ID cannot be empty when provided.");
                }
                $connection = Database::getConnection($business_id);
            } else {
                $userSet = $this->authUser();
                if (!$userSet) {
                    throw new AuthenticationException('No authenticated user found.');
                }
                $businessId = $userSet->business_id ?? 'CENTRAL';
                $connection = Database::getConnection($businessId);
            }
            // Verify connection
            if (!$connection->getPdo() || !$connection->getDatabaseName()) {
                throw new RuntimeException("Database connection not properly initialized for business_id: " . ($business_id ?? 'self'));
            }
            // Validate permission IDs
            $validPermissionIds = $connection->table('permissions')
                ->whereIn('permission_id', $permissions)
                ->where('is_approved', 1)
                ->pluck('permission_id')
                ->toArray();
            $invalidPermissionIds = array_diff($permissions, $validPermissionIds);
            if (!empty($invalidPermissionIds)) {
                // Fetch permission details from central database
                $centralConnection = Database::getConnection('central');
                $centralPermissions = $centralConnection->table('permissions')
                    ->whereIn('permission_id', $invalidPermissionIds)
                    ->where('is_approved', 1)
                    ->select([
                        'permission_id',
                        'name',
                        'description',
                        'is_approved',
                        'is_skeleton',
                        'created_at',
                        'updated_at'
                    ])
                    ->get()
                    ->keyBy('permission_id')
                    ->toArray();
                foreach ($invalidPermissionIds as $permId) {
                    if (isset($centralPermissions[$permId])) {
                        // Insert permission as-is from central database
                        $permData = (array) $centralPermissions[$permId];
                        $connection->table('permissions')->insert($permData);
                        $validPermissionIds[] = $permId;
                        Developer::info('Imported permission from central database', [
                            'permission_id' => $permId,
                            'name' => $permData['name'],
                            'business_id' => $businessId ?? 'self',
                        ]);
                    } else {
                        // If permission is not found in central database, log and skip
                        Developer::warning('Permission not found in central database', [
                            'permission_id' => $permId,
                            'business_id' => $businessId ?? 'self',
                        ]);
                    }
                }
                // Re-validate to ensure all permissions are now valid
                $validPermissionIds = $connection->table('permissions')
                    ->whereIn('permission_id', $permissions)
                    ->where('is_approved', 1)
                    ->pluck('permission_id')
                    ->toArray();
                $stillInvalid = array_diff($permissions, $validPermissionIds);
                if (!empty($stillInvalid)) {
                    Developer::error('Some permissions could not be imported from central database', [
                        'invalid_permission_ids' => $stillInvalid,
                        'business_id' => $businessId ?? 'self',
                    ]);
                    // Continue processing valid permissions instead of throwing an error
                }
            }
            // Begin transaction
            $connection->beginTransaction();
            // Determine table and key
            $table = $type === 'user' ? 'user_permissions' : 'role_permissions';
            $key = $type === 'user' ? 'user_id' : 'role_id';

            // Build base query
            $query = $connection->table($table)
                ->where($key, $id)
                ->where('is_active', 1);

            // Apply 'is_restricted' only for users
            if ($type === 'user') {
                $query->where('is_restricted', 0);
            }

            // Fetch existing permissions
            $existingPermissions = $query->pluck('permission_id')->toArray();


            // Permissions to activate
            $permissionsToActivate = array_intersect($permissions, $validPermissionIds);
            // Permissions to deactivate (existing but not in input)
            $permissionsToDeactivate = array_diff($existingPermissions, $permissions);
            // Update or insert permissions
            foreach ($permissionsToActivate as $permId) {
                $exists = in_array($permId, $existingPermissions);
                if ($exists) {
                    // Update existing permission
                    $connection->table($table)
                        ->where($key, $id)
                        ->where('permission_id', $permId)
                        ->update(['is_active' => 1]);
                } else {
                    // Insert new permission
                    $data = [
                        $key => $id,
                        'permission_id' => $permId,
                        'is_active' => 1,
                    ];
                    if ($type === 'user') {
                        $data['is_restricted'] = 0;
                    }
                    $connection->table($table)->insert($data);
                }
            }
            // Deactivate other permissions
            \Log::info($permissionsToDeactivate);
            \Log::info("Baahubali");
            \Log::info([$table, $key, $id]);
            if (!empty($permissionsToDeactivate)) {
                $connection->table($table)
                    ->where($key, $id)
                    ->whereIn('permission_id', $permissionsToDeactivate)
                    ->update(['is_active' => 0]);
            }
            // Commit transaction
            $connection->commit();
            return true;
        } catch (AuthenticationException | InvalidArgumentException | RuntimeException $e) {
            if (isset($connection) && $connection->getPdo()) {
                $connection->rollBack();
            }
            throw $e;
        } catch (Throwable $e) {
            if (isset($connection) && $connection->getPdo()) {
                $connection->rollBack();
            }
            Developer::error('Failed to manage permissions', [
                'type' => $type,
                'id' => $id,
                'permissions' => $permissions,
                'business_id' => $business_id,
                'error' => $e->getMessage(),
            ]);
            throw new RuntimeException("Failed to manage permissions: " . $e->getMessage());
        }
    }
    /**
     * Checks if a user has a specific role.
     *
     * @param string $role
     * @param User|null $user
     * @return bool
     */
    public function hasRole(string $role): bool
    {
        try {
            $userSet = $this->authUser('role');
            if (!$userSet) {
                return false;
            }
            return $userSet['name'] === $role;
        } catch (AuthenticationException | InvalidArgumentException | RuntimeException $e) {
            Developer::error('Role check failed', [
                'role' => $role,
                'user_id' => $userSet['user']->user_id ?? null,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
    // ----------------------------------- Token-Related Functions -----------------------------------
    /**
     * Initializes the skeleton token map for the user's system or for unauthenticated users.
     *
     * @return array
     */
    public function init(): array
    {
        try {
            $user = $this->authUser();
            $sessionKey = $user ? self::AUTH_SESSION_PREFIX . $user->user_id : self::UNAUTH_SESSION_PREFIX . 'global';
            if (Session::has($sessionKey) && !empty(Session::get($sessionKey))) {
                return $this->formatResponse(true, [], 'Token map already initialized.');
            }
            $system = $this->getUserSystem();
            $this->validateSystem($system);
            $tokens = $this->getSkeletonData($user !== null)['tokens'];
            $map = [];
            $usedTokens = [];
            $allowedSystems = $user ? ['central', 'business', 'open', 'lander'] : ['lander'];
            foreach ($tokens as $config) {
                if (!isset($config['key'], $config['system']) || collect($map)->contains('key', $config['key'])) {
                    continue;
                }
                if (!in_array($config['system'], $allowedSystems)) {
                    continue;
                }
                $token = $this->generateUniqueToken($usedTokens);
                $tokenSystem = $config['system'];
                if ($tokenSystem === 'open') {
                    $tokenSystem = $system;
                }
                $map[$token] = [
                    'key' => $config['key'],
                    'module' => $config['module'],
                    'system' => $tokenSystem,
                    'type' => $config['type'],
                    'table' => $config['table'],
                    'column' => $config['column'],
                    'value' => $config['value'],
                    'validate' => $config['validate'],
                    'act' => $config['act'],
                    'actions' => $config['actions'],
                ];
                $usedTokens[] = $token;
            }
            $this->storeTokenMap($map, $user ? $user->user_id : 'global', $user !== null);
            // For authenticated users, also initialize global session if not already set
            if ($user && !Session::has(self::UNAUTH_SESSION_PREFIX . 'global')) {
                $globalTokens = collect($tokens)->where('system', 'lander')->toArray();
                $globalMap = [];
                $globalUsedTokens = [];
                foreach ($globalTokens as $config) {
                    if (!isset($config['key'], $config['system']) || collect($globalMap)->contains('key', $config['key'])) {
                        continue;
                    }
                    $token = $this->generateUniqueToken($globalUsedTokens);
                    $globalMap[$token] = [
                        'key' => $config['key'],
                        'module' => $config['module'],
                        'system' => $config['system'],
                        'type' => $config['type'],
                        'table' => $config['table'],
                        'column' => $config['column'],
                        'value' => $config['value'],
                        'validate' => $config['validate'],
                        'act' => $config['act'],
                        'actions' => $config['actions'],
                    ];
                    $globalUsedTokens[] = $token;
                }
                $this->storeTokenMap($globalMap, 'global', false);
            }
            return $this->formatResponse(true, [], 'Token map initialized successfully.');
        } catch (AuthenticationException | InvalidArgumentException | RuntimeException $e) {
            Developer::error('Failed to initialize token map', [
                'system' => $system ?? null,
                'error' => $e->getMessage(),
            ]);
            return $this->formatResponse(false, [], 'Failed to initialize token map: ' . $e->getMessage());
        }
    }
    /**
     * Retrieves or generates a token for a configuration key.
     *
     * @param string $key
     * @return array
     */
    public function getTokenForKey(string $key): array
    {
        try {
            $this->validateKey($key);
            $user = $this->authUser();
            $sessionKey = $user ? self::AUTH_SESSION_PREFIX . $user->user_id : self::UNAUTH_SESSION_PREFIX . 'global';
            $map = Session::get($sessionKey, []);
            $entry = collect($map)->firstWhere('key', $key);
            $token = $entry ? array_search($entry, $map, true) : null;
            if ($token) {
                return $this->formatResponse(true, ['token' => $token], 'Token retrieved successfully.');
            }
            // For authenticated users, also check global session
            if ($user) {
                $globalMap = Session::get(self::UNAUTH_SESSION_PREFIX . 'global', []);
                $globalEntry = collect($globalMap)->firstWhere('key', $key);
                $globalToken = $globalEntry ? array_search($globalEntry, $globalMap, true) : null;
                if ($globalToken && $globalEntry['system'] === 'lander') {
                    return $this->formatResponse(true, ['token' => $globalToken], 'Token retrieved from global session.');
                }
            }
            return $this->generateNewTokenForKey($key);
        } catch (AuthenticationException | InvalidArgumentException | RuntimeException $e) {
            Developer::error('Failed to retrieve token', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
            return $this->formatResponse(false, [], 'Failed to retrieve token: ' . $e->getMessage());
        }
    }
    /**
     * Retrieves an existing token for a configuration key.
     *
     * @param string $key
     * @return string
     */
    public function skeletonToken(string $key): string
    {
        try {
            $this->validateKey($key);
            $user = $this->authUser();
            $sessionKey = $user ? self::AUTH_SESSION_PREFIX . $user->user_id : self::UNAUTH_SESSION_PREFIX . 'global';
            $map = Session::get($sessionKey, []);
            $entry = collect($map)->firstWhere('key', $key);
            $token = $entry ? array_search($entry, $map, true) : null;
            if ($token) {
                if (!$user && $entry['system'] !== 'lander') {
                    throw new InvalidArgumentException('Unauthenticated users can only access lander tokens.');
                }
                return $token;
            }
            // For authenticated users, check global session
            if ($user) {
                $globalMap = Session::get(self::UNAUTH_SESSION_PREFIX . 'global', []);
                $globalEntry = collect($globalMap)->firstWhere('key', $key);
                $globalToken = $globalEntry ? array_search($globalEntry, $globalMap, true) : null;
                if ($globalToken && $globalEntry['system'] === 'lander') {
                    return $globalToken;
                }
            }
            throw new InvalidArgumentException('No token found for key: ' . $key);
        } catch (AuthenticationException | InvalidArgumentException | RuntimeException $e) {
            Developer::error('Failed to retrieve token', [
                'error' => $e->getMessage(),
                'key' => $key,
                'trace' => $e->getTraceAsString(),
            ]);
            return '';
        }
    }
    /**
     * Generates a new token for a configuration key.
     *
     * @param string $key
     * @return array
     */
    public function generateNewTokenForKey(string $key): array
    {
        try {
            $this->validateKey($key);
            $user = $this->authUser();
            $sessionKey = $user ? self::AUTH_SESSION_PREFIX . $user->user_id : self::UNAUTH_SESSION_PREFIX . 'global';
            $config = collect($this->getSkeletonData($user !== null)['tokens'])->firstWhere('key', $key);
            if (!$config) {
                Developer::info('Configuration not found for key.', [
                    'user_id' => $user?->user_id ?? 'unauthenticated',
                    'key' => $key,
                ]);
                return $this->formatResponse(false, [], 'Configuration not found for key.');
            }
            if (!$user && $config['system'] !== 'lander') {
                Developer::info('Unauthenticated user attempted to generate non-lander token.', [
                    'key' => $key,
                    'system' => $config['system'],
                ]);
                return $this->formatResponse(false, [], 'Unauthenticated users can only generate lander tokens.');
            }
            $map = Session::get($sessionKey, []);
            $token = $this->generateUniqueToken(array_keys($map));
            $system = $config['system'];
            if ($system === 'open') {
                $system = $this->getUserSystem();
            }
            $map[$token] = [
                'key' => $config['key'],
                'module' => $config['module'],
                'system' => $system,
                'type' => $config['type'],
                'table' => $config['table'],
                'column' => $config['column'],
                'value' => $config['value'],
                'validate' => $config['validate'],
                'act' => $config['act'],
                'actions' => $config['actions'],
            ];
            $this->storeTokenMap($map, $user ? $user->user_id : 'global', $user !== null);
            // For authenticated users, also update global session for lander tokens
            if ($user && $system === 'lander') {
                $globalMap = Session::get(self::UNAUTH_SESSION_PREFIX . 'global', []);
                if (!collect($globalMap)->contains('key', $key)) {
                    $globalMap[$token] = $map[$token];
                    $this->storeTokenMap($globalMap, 'global', false);
                }
            }
            return $this->formatResponse(true, ['token' => $token], 'New token generated successfully.');
        } catch (AuthenticationException | InvalidArgumentException | RuntimeException $e) {
            Developer::error('Failed to generate token', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
            return $this->formatResponse(false, [], 'Failed to generate token: ' . $e->getMessage());
        }
    }
    /**
     * Regenerates a token for a configuration key.
     *
     * @param string $key
     * @return array
     */
    public function regenerate(string $key): array
    {
        try {
            $this->validateKey($key);
            $user = $this->authUser();
            $sessionKey = $user ? self::AUTH_SESSION_PREFIX . $user->user_id : self::UNAUTH_SESSION_PREFIX . 'global';
            $config = collect($this->getSkeletonData($user !== null)['tokens'])->firstWhere('key', $key);
            if (!$config) {
                return $this->formatResponse(false, [], 'Configuration not found for key.');
            }
            if (!$user && $config['system'] !== 'lander') {
                return $this->formatResponse(false, [], 'Unauthenticated users can only regenerate lander tokens.');
            }
            $map = Session::get($sessionKey, []);
            $entry = collect($map)->firstWhere('key', $key);
            if ($entry) {
                unset($map[array_search($entry, $map, true)]);
            }
            $token = $this->generateUniqueToken(array_keys($map));
            $system = $config['system'];
            if ($system === 'open') {
                $system = $this->getUserSystem();
            }
            $map[$token] = [
                'key' => $config['key'],
                'module' => $config['module'],
                'system' => $system,
                'type' => $config['type'],
                'table' => $config['table'],
                'column' => $config['column'],
                'value' => $config['value'],
                'validate' => $config['validate'],
                'act' => $config['act'],
                'actions' => $config['actions'],
            ];
            $this->storeTokenMap($map, $user ? $user->user_id : 'global', $user !== null);
            // For authenticated users, also update global session for lander tokens
            if ($user && $system === 'lander') {
                $globalMap = Session::get(self::UNAUTH_SESSION_PREFIX . 'global', []);
                $globalEntry = collect($globalMap)->firstWhere('key', $key);
                if ($globalEntry) {
                    unset($globalMap[array_search($globalEntry, $globalMap, true)]);
                }
                $globalMap[$token] = $map[$token];
                $this->storeTokenMap($globalMap, 'global', false);
            }
            return $this->formatResponse(true, ['token' => $token], 'Token regenerated successfully.');
        } catch (AuthenticationException | InvalidArgumentException | RuntimeException $e) {
            Developer::error('Failed to regenerate token', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
            return $this->formatResponse(false, [], 'Failed to regenerate token: ' . $e->getMessage());
        }
    }
    /**
     * Resolves a generated token to its configuration data.
     *
     * @param string $generatedToken
     * @return array
     */
    public function resolveToken(string $generatedToken): array
    {
        try {
            $tokenLength = Config::get('skeleton.token_length', 27);
            $token = substr($generatedToken, 0, $tokenLength);
            if (strlen($token) !== $tokenLength) {
                throw new InvalidArgumentException("Token must be exactly {$tokenLength} characters.");
            }
            $user = $this->authUser();
            $authSessionKey = $user ? self::AUTH_SESSION_PREFIX . $user->user_id : null;
            $globalSessionKey = self::UNAUTH_SESSION_PREFIX . 'global';
            // Check auth session first for authenticated users
            $map = [];
            if ($user && $authSessionKey) {
                $map = Session::get($authSessionKey, []);
                if (isset($map[$token])) {
                    $config = $map[$token];
                    $config['token'] = $token;
                    $tokenParts = explode('_', $generatedToken);
                    if (count($tokenParts) >= 5) {
                        $config['for'] = $tokenParts[4];
                        if (count($tokenParts) >= 6) {
                            $config['id'] = $tokenParts[5];
                            if (count($tokenParts) > 6) {
                                $config['param'] = implode('_', array_slice($tokenParts, 6));
                            }
                        }
                    }
                    return $config ?? [];
                }
            }
            // Fall back to global session
            $map = Session::get($globalSessionKey, []);
            if (!isset($map[$token])) {
                Developer::error('Token not found in session map', [
                    'token' => $token,
                    'auth_session_key' => $authSessionKey ?? 'none',
                    'global_session_key' => $globalSessionKey,
                    'map_keys' => array_keys($map),
                ]);
                return $this->formatResponse(false, [], 'No configuration found for token.');
            }
            $config = $map[$token];
            if (!$user && $config['system'] !== 'lander') {
                Developer::error('Unauthenticated user attempted to resolve non-lander token', [
                    'token' => $token,
                    'system' => $config['system'],
                ]);
                return $this->formatResponse(false, [], 'Unauthenticated users can only resolve lander tokens.');
            }
            $config['token'] = $token;
            $tokenParts = explode('_', $generatedToken);
            if (count($tokenParts) >= 5) {
                $config['for'] = $tokenParts[4];
                if (count($tokenParts) >= 6) {
                    $config['id'] = $tokenParts[5];
                    if (count($tokenParts) > 6) {
                        $config['param'] = implode('_', array_slice($tokenParts, 6));
                    }
                }
            }
            return $config ?? [];
        } catch (AuthenticationException | InvalidArgumentException | RuntimeException $e) {
            Developer::error('Failed to resolve token', [
                'token' => $generatedToken,
                'error' => $e->getMessage(),
            ]);
            return $this->formatResponse(false, [], 'Failed to resolve token: ' . $e->getMessage());
        }
    }
    /**
     * Generates a unique token with exactly three underscores and a wrapped character.
     *
     * @param array $usedTokens
     * @return string
     * @throws InvalidArgumentException|RuntimeException
     */
    protected function generateUniqueToken(array $usedTokens): string
    {
        $maxAttempts = Config::get('skeleton.max_token_attempts', 15);
        $tokenLength = Config::get('skeleton.token_length', 27);
        $allowedWrappedChars = ['v', 'e', 'd', 'a'];
        if ($tokenLength < 4) {
            throw new InvalidArgumentException('Token length must be at least 4 for three underscores.');
        }
        if ($maxAttempts < 1) {
            throw new InvalidArgumentException('Max token attempts must be at least 1.');
        }
        $attempt = 0;
        do {
            if ($attempt++ >= $maxAttempts) {
                throw new RuntimeException("Unable to generate unique token after {$maxAttempts} attempts.");
            }
            $baseLength = $tokenLength - 4;
            $token = Str::random($baseLength);
            $tokenArray = str_split($token);
            // Insert _X_ (two underscores and one wrapped character)
            $wrappedChar = $allowedWrappedChars[array_rand($allowedWrappedChars)];
            $insertPos = random_int(0, max(0, count($tokenArray) - 1));
            array_splice($tokenArray, $insertPos, 0, ['_', $wrappedChar, '_']);
            // Insert third underscore at a distinct position
            $availablePositions = array_keys($tokenArray);
            $thirdUnderscorePos = $availablePositions[array_rand($availablePositions)];
            while (in_array($thirdUnderscorePos, [$insertPos, $insertPos + 1, $insertPos + 2])) {
                $thirdUnderscorePos = $availablePositions[array_rand($availablePositions)];
            }
            array_splice($tokenArray, $thirdUnderscorePos, 0, '_');
            $token = implode('', $tokenArray);
            // Adjust length while preserving three underscores
            if (strlen($token) > $tokenLength) {
                $underscorePositions = [];
                $count = 0;
                foreach (str_split($token) as $i => $char) {
                    if ($char === '_' && $count < 3) {
                        $underscorePositions[] = $i;
                        $count++;
                    }
                }
                $newToken = '';
                $currentLength = 0;
                $underscoresAdded = 0;
                foreach (str_split($token) as $i => $char) {
                    if ($currentLength >= $tokenLength) {
                        break;
                    }
                    if ($char === '_' && !in_array($i, $underscorePositions, true) && $underscoresAdded >= 3) {
                        continue;
                    }
                    if ($char === '_') {
                        $underscoresAdded++;
                    }
                    $newToken .= $char;
                    $currentLength++;
                }
                $token = $newToken;
            }
            if (strlen($token) < $tokenLength) {
                $token .= Str::random($tokenLength - strlen($token));
            }
        } while (in_array($token, $usedTokens, true) || substr_count($token, '_') !== 3);
        return $token;
    }
    /**
     * Stores the token map in session.
     *
     * @param array $map
     * @param string $identifier
     * @param bool $isAuthenticated
     * @return void
     */
    protected function storeTokenMap(array $map, string $identifier, bool $isAuthenticated): void
    {
        try {
            $sessionKey = $isAuthenticated ? self::AUTH_SESSION_PREFIX . $identifier : self::UNAUTH_SESSION_PREFIX . $identifier;
            Session::put($sessionKey, $map);
        } catch (Throwable $e) {
            Developer::error('Failed to store token map', ['error' => $e->getMessage()]);
        }
    }
    // ----------------------------------- Cache-Related Functions -----------------------------------
    /**
     * Generates a cache or session key for a user-specific resource.
     *
     * @param string $type
     * @param User|null $user
     * @return string
     * @throws AuthenticationException
     */
    protected function generateKey(string $type): string
    {
        $userSet = $this->authUser();
        $identifier = $userSet ? "{$userSet->user_id}_{$userSet->business_id}" : 'unauthenticated';
        return "{$type}_{$identifier}";
    }
    /**
     * Invalidates user-specific cache and session data.
     *
     * @return void
     */
    public function clearUserCache(): void
    {
        try {
            $userSet = $this->authUser();
            $identifier = $userSet ? $userSet->user_id : 'global';
            $businessId = $userSet ? $userSet->business_id : 'unknown';
            $cacheKey = "navigation_data_" . $identifier;
            $navToken = Session::get('nav_token');
            Cache::forget($cacheKey);
            if ($navToken) {
                Cache::forget("nav_{$navToken}");
            }
            $sessionKey = $userSet ? self::AUTH_SESSION_PREFIX . $identifier : self::UNAUTH_SESSION_PREFIX . 'global';
            Session::forget($sessionKey);
            if ($userSet) {
                Session::forget("auth_user_data_{$userSet->user_id}");
                Session::forget("user_role_{$userSet->user_id}");
            }
            // Do not clear global session to preserve shared tokens
            Developer::notice('User cache invalidated', compact('identifier', 'businessId'));
        } catch (AuthenticationException | InvalidArgumentException | RuntimeException $e) {
            Developer::error('Failed to invalidate user cache', [
                'identifier' => $identifier ?? 'unknown',
                'business_id' => $businessId ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
        }
    }
    /**
     * Reloads all skeleton-related sessions, caches, and data for a user.
     *
     * @param User|null $user
     * @return array
     */
    public function reloadSkeleton(): array
    {
        try {
            // Step 1: Get authenticated user
            $userSet = $this->authUser();
            $identifier = $userSet ? $userSet->user_id : 'global';
            // Step 2: Clear user-specific cache
            $this->clearUserCache();
            foreach (Config::get('skeleton.allowed_systems', ['central', 'business', 'open', 'lander']) as $system) {
                $cacheKeys = [
                    "skeleton_global_data",
                    $userSet ? self::AUTH_SESSION_PREFIX . $identifier : self::UNAUTH_SESSION_PREFIX . 'global',
                ];
                foreach ($cacheKeys as $key) {
                    Cache::forget($key);
                }
            }
            Cache::forget('folder_paths_all');
            // Step 3: Reinitialize token map
            $initResult = $this->init();
            if (!$initResult['status']) {
                throw new RuntimeException('Failed to reinitialize token map: ' . $initResult['message']);
            }
            // Step 4: Refresh skeleton data
            $skeletonData = $this->getSkeletonData($userSet !== null);
            if (empty($skeletonData)) {
                throw new RuntimeException('Failed to refresh skeleton data.');
            }
            // Step 5: Refresh user data if needed
            if ($userSet) {
                $userRefreshed = $this->authUser();
                if (empty($userRefreshed)) {
                    throw new RuntimeException('Failed to refresh user data.');
                }
            }
            return $this->formatResponse(true, [], 'Skeleton data, caches, and sessions reloaded successfully.');
        } catch (AuthenticationException | InvalidArgumentException | RuntimeException $e) {
            return $this->formatResponse(false, [], 'Failed to reload skeleton: ' . $e->getMessage());
        }
    }
    // ----------------------------------- Skeleton Global Functions -----------------------------------
    /**
     * Fetches or builds cached global skeleton data, handling both authenticated and unauthenticated users.
     *
     * @param bool $isAuthenticated Whether the user is authenticated
     * @return array
     */
    public function getSkeletonData(bool $isAuthenticated = true): array
    {
        $cacheKey = $isAuthenticated ? 'skeleton_global_data' : 'skeleton_global_data_unauthenticated';
        return Cache::remember($cacheKey, now()->addHours(5), function () use ($isAuthenticated) {
            $data = ['modules' => [], 'sections' => [], 'items' => [], 'tokens' => []];
            try {
                if ($isAuthenticated) {
                    CentralDB::table('skeleton_modules as m')
                        ->leftJoin('skeleton_sections as s', function ($join) {
                            $join->on('m.module_id', '=', 's.module_id')
                                ->where('s.is_approved', 1)
                                ->whereNull('s.deleted_at');
                        })
                        ->leftJoin('skeleton_items as i', function ($join) {
                            $join->on('s.section_id', '=', 'i.section_id')
                                ->where('i.is_approved', 1)
                                ->whereNull('i.deleted_at');
                        })
                        ->where('m.is_approved', 1)
                        ->whereNull('m.deleted_at')
                        ->select([
                            'm.module_id',
                            'm.name as module_name',
                            'm.display as module_display',
                            'm.icon as module_icon',
                            'm.order as module_order',
                            'm.is_navigable as module_navigable',
                            's.section_id',
                            's.name as section_name',
                            's.display as section_display',
                            's.icon as section_icon',
                            's.order as section_order',
                            's.is_navigable as section_navigable',
                            'i.item_id',
                            'i.name as item_name',
                            'i.display as item_display',
                            'i.icon as item_icon',
                            'i.order as item_order',
                            'i.is_navigable as item_navigable',
                        ])
                        ->orderByRaw('COALESCE(m.order, 999999) ASC')
                        ->orderByRaw('COALESCE(s.order, 999999) ASC')
                        ->orderByRaw('COALESCE(i.order, 999999) ASC')
                        ->orderBy('m.order', 'asc')
                        ->orderBy('s.order', 'asc')
                        ->orderBy('i.order', 'asc')
                        ->chunk(100, function ($rows) use (&$data) {
                            $modules = [];
                            $sections = [];
                            $items = [];
                            foreach ($rows as $row) {
                                if (!isset($modules[$row->module_id])) {
                                    $modules[$row->module_id] = [
                                        'module_id' => $row->module_id,
                                        'name' => trim($row->module_name),
                                        'display' => trim($row->module_display),
                                        'icon' => trim($row->module_icon ?? ''),
                                        'order' => $row->module_order,
                                        'navigable' => $row->module_navigable,
                                    ];
                                }
                                if ($row->section_id) {
                                    if (!isset($sections[$row->section_id])) {
                                        $sections[$row->section_id] = [
                                            'section_id' => $row->section_id,
                                            'module_id' => $row->module_id,
                                            'name' => trim($row->section_name),
                                            'display' => trim($row->section_display),
                                            'icon' => trim($row->section_icon ?? ''),
                                            'order' => $row->section_order,
                                            'navigable' => $row->section_navigable,
                                        ];
                                    }
                                    if ($row->item_id) {
                                        $items[] = [
                                            'item_id' => $row->item_id,
                                            'section_id' => $row->section_id,
                                            'name' => trim($row->item_name),
                                            'display' => trim($row->item_display),
                                            'icon' => trim($row->item_icon ?? ''),
                                            'order' => $row->item_order,
                                            'navigable' => $row->item_navigable,
                                        ];
                                    }
                                }
                            }
                            $data['modules'] = array_values($modules);
                            $data['sections'] = array_values($sections);
                            $data['items'] = $items;
                        });
                }
                $allowedSystems = $isAuthenticated ? ['central', 'business', 'open', 'lander'] : ['lander'];
                CentralDB::table('skeleton_tokens')
                    ->whereNull('deleted_at')
                    ->whereIn('system', $allowedSystems)
                    ->orderBy('id', 'asc')
                    ->select(['key', 'module', 'system', 'type', 'table', 'column', 'value', 'validate', 'act', 'actions'])
                    ->chunk(100, function ($tokens) use (&$data) {
                        $data['tokens'] = array_merge($data['tokens'], array_map(
                            fn($token) => array_map(
                                fn($value) => is_string($value) ? trim($value) : $value,
                                (array) $token
                            ),
                            $tokens->toArray()
                        ));
                    });
                return $data;
            } catch (Exception $e) {
                Developer::error('Failed to retrieve skeleton data', ['error' => $e->getMessage()]);
                return [];
            }
        });
    }
    /**
     * Retrieves cached skeleton modules.
     *
     * @return array
     */
    public function getModules(): array
    {
        return $this->getSkeletonData(true)['modules'] ?? [];
    }
    /**
     * Retrieves cached skeleton sections.
     *
     * @return array
     */
    public function getSections(): array
    {
        return $this->getSkeletonData(true)['sections'] ?? [];
    }
    /**
     * Retrieves cached skeleton items.
     *
     * @return array
     */
    public function getItems(): array
    {
        return $this->getSkeletonData(true)['items'] ?? [];
    }
    /**
     * Retrieves cached skeleton tokens.
     *
     * @return array
     */
    public function getTokens(): array
    {
        return $this->getSkeletonData($this->authUser() !== null)['tokens'] ?? [];
    }
    /**
     * Retrieves cached skeleton routes.
     *
     * @return array
     */
    public function getRoutes(): array
    {
        $modules = $this->getModules();
        $sections = collect($this->getSections());
        $items = collect($this->getItems());
        $routes = [];
        foreach ($modules as $module) {
            $moduleSlug = Str::kebab($module['name'] ?? '');
            if (!$moduleSlug) {
                continue;
            }
            $routes[] = $moduleSlug;
            $routes[] = "t/{$moduleSlug}/{type}/{token}";
            foreach ($sections->where('module_id', $module['module_id']) as $section) {
                $sectionSlug = Str::kebab($section['name'] ?? '');
                if (!$sectionSlug) {
                    continue;
                }
                $sectionPath = "{$moduleSlug}/{$sectionSlug}";
                $routes[] = $sectionPath;
                foreach ($items->where('section_id', $section['section_id']) as $item) {
                    $itemSlug = Str::kebab($item['name'] ?? '');
                    if (!$itemSlug) {
                        continue;
                    }
                    $itemPath = "{$sectionPath}/{$itemSlug}";
                    $routes[] = $itemPath;
                }
            }
        }
        return $routes;
    }
    // ----------------------------------- Utility Functions -----------------------------------
    /**
     * Formats a standardized JSON response.
     *
     * @param bool $status
     * @param array $data
     * @param string $message
     * @return array
     */
    public function formatResponse(bool $status, array $data, string $message): array
    {
        return compact('status', 'data', 'message');
    }
    /**
     * Validates a configuration key.
     *
     * @param string $key
     * @throws InvalidArgumentException
     */
    protected function validateKey(string $key): void
    {
        if (empty($key) || !preg_match('/^[a-zA-Z0-9_-]+$/', $key)) {
            throw new InvalidArgumentException('Invalid configuration key.');
        }
    }
    /**
     * Validates a system identifier.
     *
     * @param string $system
     * @throws InvalidArgumentException
     */
    protected function validateSystem(string $system): void
    {
        $allowedSystems = Config::get('skeleton.allowed_systems', ['central', 'business', 'open', 'lander']);
        if (!in_array($system, $allowedSystems)) {
            throw new InvalidArgumentException('Invalid system provided.');
        }
    }
}
