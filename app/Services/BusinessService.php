<?php

namespace App\Services;

use App\Facades\{CentralDB, Database, Random};
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class BusinessService
{
    /**
     * Setup business database and create user with permissions.
     *
     * @param string $businessId
     * @return array
     * @throws Exception
     */
    public function setupBusinessDatabase(string $businessId): array
    {
        $business = CentralDB::table('businesses')
            ->where('business_id', $businessId)
            ->first();

        if (!$business) {
            throw new Exception("Business not found with ID: $businessId");
        }

        // STEP 3: Create business database
        $dbName = $this->generateDatabaseName($businessId);
        DB::statement("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        // STEP 4: Get schema definitions
        $schemas = CentralDB::table('business_schemas')
            ->where('operation', 'create')
            ->whereNull('deleted_at')
            ->orderBy('execution_order')
            ->get();

        $createdTables = [];

        try {
            foreach ($schemas as $schema) {
                $tableName = $schema->table;

                // Skip table creation if it already exists
                if ($this->tableExists($dbName, $tableName)) {
                    continue;
                }

                $query = $this->sanitizeSchema($schema->schema, $dbName);
                DB::unprepared($query); // Use DB not CentralDB, since it's targeting $dbName
                $createdTables[] = $tableName;
            }

            // STEP 5: Setup user and permissions
            $userResult = $this->setupBusinessUserAndPermissions($businessId);

            return [
                'status' => true,
                'message' => "Database, user, and permissions setup completed successfully",
                'central_user_id' => $userResult['central_user_id'] ?? null,
                'business_user_id' => $userResult['business_user_id'] ?? null
            ];

        } catch (Exception $e) {
            foreach (array_reverse($createdTables) as $table) {
                try {
                    DB::statement("DROP TABLE IF EXISTS `$dbName`.`$table`");
                } catch (\Throwable $dropEx) {
                    // Ignore drop errors during rollback
                }
            }

            throw new Exception("Setup failed: " . $e->getMessage());
        }
    }

    /**
     * Generate database name for a business.
     *
     * @param string $businessId
     * @return string
     */
    private function generateDatabaseName(string $businessId): string
    {
        return 'got' . $businessId;
    }

    /**
     * Sanitize schema SQL for execution.
     *
     * @param string $schema
     * @param string $dbName
     * @return string
     */
    private function sanitizeSchema(string $schema, string $dbName): string
    {
        $schema = trim($schema);
        $schema = preg_replace('/^[\'"]|[\'"]$/', '', $schema);
        $schema = str_replace(['\\r\\n', '\r\n', '\n'], "\n", $schema);

        $schema = preg_replace(
            '/CREATE\s+TABLE\s+`?(\w+)`?/i',
            "CREATE TABLE `$dbName`.`$1`",
            $schema
        );

        return $schema;
    }

    /**
     * Check if a table exists in the specified database.
     *
     * @param string $dbName
     * @param string $table
     * @return bool
     */
    protected function tableExists(string $dbName, string $table): bool
    {
        $result = DB::selectOne(
            "SELECT COUNT(*) as count 
            FROM information_schema.tables 
            WHERE table_schema = ? AND table_name = ?",
            [$dbName, $table]
        );

        return isset($result->count) && $result->count > 0;
    }

    /**
     * Fetch business details from CentralDB.
     *
     * @param string $businessId
     * @return object
     * @throws Exception
     */
    public function fetchBusinessDetails(string $businessId): object
    {
        $business = CentralDB::table('businesses')
            ->where('business_id', $businessId)
            ->first();

        if (!$business) {
            throw new Exception("Business not found with ID: $businessId");
        }

        return $business;
    }

    /**
     * Create a system record in CentralDB's business_systems table.
     *
     * @param string $businessId
     * @param object $business
     * @return bool
     * @throws Exception
     */
    public function createSystemRecord(string $businessId, object $business): bool
    {
        try {
            $dbName = $this->generateDatabaseName($businessId);
            $systemExists = CentralDB::table('business_systems')
                ->where('business_id', $businessId)
                ->where('system', 'business')
                ->exists();

            if ($systemExists) {
                return true;
            }

            CentralDB::table('business_systems')->insert([
                'business_id' => $businessId,
                'system' => 'business',
                'name' => $business->name,
                'database' => $dbName,
                'is_active' => 1,
                'created_by' => $business->created_by ?? 'system',
                'updated_by' => $business->updated_by ?? 'system',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return true;
        } catch (Exception $e) {
            throw new Exception("Failed to create system record: " . $e->getMessage());
        }
    }

    /**
     * Create a user in CentralDB's users table.
     *
     * @param string $businessId
     * @param object $business
     * @return string
     * @throws Exception
     */
    public function createCentralDBUser(string $businessId, object $business): string
    {
        try {
            $userId = Random::uniqueId('USR', 6);
            $userExists = CentralDB::table('users')
                ->where('business_id', $businessId)
                ->where('email', $business->email)
                ->exists();

            if ($userExists) {
                return CentralDB::table('users')
                    ->where('business_id', $businessId)
                    ->where('email', $business->email)
                    ->value('user_id');
            }

            CentralDB::table('users')->insert([
                'user_id' => $userId,
                'business_id' => $businessId,
                'username' => $business->email,
                'email' => $business->email,
                'password' => Hash::make('default_password'), // Replace with secure password generation
                'first_name' => $business->admin_first_name,
                'last_name' => $business->admin_last_name,
                'account_status' => 'active',
                'created_by' => $business->created_by ?? 'system',
                'updated_by' => $business->updated_by ?? 'system',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $userId;
        } catch (Exception $e) {
            throw new Exception("Failed to create CentralDB user: " . $e->getMessage());
        }
    }

    /**
     * Create a user in the business database's users table and assign admin role.
     *
     * @param string $businessId
     * @param object $business
     * @return array
     * @throws Exception
     */
    public function createBusinessDBAdmin(string $businessId, object $business, $userId): array
    {
        try {
            $businessDbConnection = Database::getConnection('business', $businessId);
            $userExists = $businessDbConnection->table('users')
                ->where('business_id', $businessId)
                ->where('email', $business->email)
                ->exists();

            if ($userExists) {
                $existingUserId = $businessDbConnection->table('users')
                    ->where('business_id', $businessId)
                    ->where('email', $business->email)
                    ->value('user_id');
                $roleId = $this->createAndAssignAdminRole($businessId, $existingUserId, $business);

                return [
                    'user_id' => $existingUserId,
                    'role_id' => $roleId
                ];
            }

            $businessDbConnection->table('users')->insert([
                'user_id' => $userId,
                'business_id' => $businessId,
                'username' => $business->email,
                'email' => $business->email,
                'password' => Hash::make('default_password'), // Replace with secure password generation
                'first_name' => $business->admin_first_name,
                'last_name' => $business->admin_last_name,
                'account_status' => 'active',
                'created_by' => $business->created_by ?? 'system',
                'updated_by' => $business->updated_by ?? 'system',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $roleId = $this->createAndAssignAdminRole($businessId, $userId, $business);

            return [
                'user_id' => $userId,
                'role_id' => $roleId
            ];
        } catch (Exception $e) {
            throw new Exception("Failed to create business DB user: " . $e->getMessage());
        }
    }

    /**
     * Create admin role and assign it to the user in the business database.
     *
     * @param string $businessId
     * @param string $userId
     * @param object $business
     * @return string
     * @throws Exception
     */
    protected function createAndAssignAdminRole(string $businessId, string $userId, object $business): string
    {
        try {
            $businessDbConnection = Database::getConnection('business', $businessId);

            // Verify user exists in the users table
            $userExists = $businessDbConnection->table('users')
                ->where('user_id', $userId)
                ->exists();

            if (!$userExists) {
                throw new Exception("User with ID $userId does not exist in the business database.");
            }   

            // Start a transaction
            return $businessDbConnection->transaction(function () use ($businessDbConnection, $businessId, $userId, $business) {
                // Check if admin role exists, create if not
                $role = $businessDbConnection->table('roles')
                    ->where('name', 'admin')
                    ->first();

                $roleId = null;
                if (!$role) {
                    $roleId = Random::uniqueId('ROL', 6);
                    $businessDbConnection->table('roles')->insert([
                        'role_id' => $roleId,
                        'name' => 'admin',
                        'description' => 'System administrator role with full access',
                        'parent_role_id' => null,
                        'is_system_role' => 0,
                        'is_active' => 1,
                        'created_by' => $business->created_by ?? 'system',
                        'updated_by' => $business->updated_by ?? 'system',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    $roleId = $role->role_id;
                }

                // Assign admin role to user if not already assigned
                $roleAssignmentExists = $businessDbConnection->table('user_roles')
                    ->where('user_id', $userId)
                    ->where('role_id', $roleId)
                    ->exists();

                if (!$roleAssignmentExists) {
                    $businessDbConnection->table('user_roles')->insert([
                        'user_id' => $userId,
                        'role_id' => $roleId,
                        'valid_from' => now(),
                        'valid_until' => null,
                        'notes' => 'Assigned admin role during business setup',
                        'is_active' => 1,
                        'scope_id' => $businessId,
                        'created_by' => $business->created_by ?? 'system',
                        'updated_by' => $business->updated_by ?? 'system',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                return $roleId;
            });
        } catch (Exception $e) {
            throw new Exception("Failed to create and assign admin role: " . $e->getMessage());
        }
    }

    /**
     * Load and assign permissions based on business plan modules.
     *
     * @param string $businessId
     * @param string $userId
     * @param string $roleId
     * @return bool
     * @throws Exception
     */
    public function loadAndAssignPermissions(string $businessId, string $userId, string $roleId): bool
    {
        try {
            $businessDbConnection = Database::getConnection('business', $businessId);

            // Fetch active subscription
            $subscription = CentralDB::table('business_subscriptions')
                ->where('business_id', $businessId)
                ->where('status', 'active')
                ->first();

            if (!$subscription) {
                return true; // Continue without permissions if no subscription
            }

            // Fetch plan details
            $plan = CentralDB::table('business_plans')
                ->where('plan_id', $subscription->plan_id)
                ->first();

            if (!$plan || empty($plan->module_pricing_ids)) {
                return true; // Continue without permissions if no plan or modules
            }

            // Get module IDs and dependent module IDs
            $modulePricingIds = explode(',', $plan->module_pricing_ids);
            $moduleIds = [];

            foreach ($modulePricingIds as $modulePriceId) {
                $modulePricing = CentralDB::table('business_module_pricing')
                    ->where('module_price_id', $modulePriceId)
                    ->first();

                if ($modulePricing) {
                    $moduleIds[] = $modulePricing->module_id;
                    if (!empty($modulePricing->dependent_module_ids)) {
                        $moduleIds = array_merge($moduleIds, explode(',', $modulePricing->dependent_module_ids));
                    }
                }
            }

            // Get unique module IDs
            $moduleIds = array_unique($moduleIds);

            // Fetch module names from skeleton_modules
            $moduleNames = CentralDB::table('skeleton_modules')
                ->whereIn('module_id', $moduleIds)
                ->pluck('name')
                ->toArray();

            if (empty($moduleNames)) {
                return true; // Continue without permissions if no module names found
            }

            // Build LIKE patterns for permissions
            $permissionPatterns = [];
            $actions = ['create', 'view', 'edit', 'delete', 'import', 'export'];
            foreach ($moduleNames as $moduleName) {
                foreach ($actions as $action) {
                    $permissionPatterns[] = "$action:$moduleName::%";
                }
            }

            // Fetch permissions from CentralDB matching the module name patterns
            if (!empty($permissionPatterns)) {
                $permissions = CentralDB::table('permissions')
                    ->where(function ($query) use ($permissionPatterns) {
                        foreach ($permissionPatterns as $pattern) {
                            $query->orWhere('name', 'LIKE', $pattern);
                        }
                    })
                    ->where('is_approved', 1)
                    ->whereNull('deleted_at')
                    ->get();

                foreach ($permissions as $permission) {
                    // Check if permission exists in business DB
                    $permissionExistsInBusiness = $businessDbConnection->table('permissions')
                        ->where('permission_id', $permission->permission_id)
                        ->exists();

                    // If permission doesn't exist in business DB, create it
                    if (!$permissionExistsInBusiness) {
                        $businessDbConnection->table('permissions')->insert([
                            'permission_id' => $permission->permission_id,
                            'name' => $permission->name,
                            'description' => $permission->description,
                            'is_approved' => $permission->is_approved,
                            'is_skeleton' => $permission->is_skeleton,
                            'created_by' => $permission->created_by ?? 'system',
                            'updated_by' => $permission->updated_by ?? 'system',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    // Check if permission is already assigned to the user
                    $permissionAssigned = $businessDbConnection->table('user_permissions')
                        ->where('user_id', $userId)
                        ->where('permission_id', $permission->permission_id)
                        ->exists();

                    if (!$permissionAssigned) {
                        $businessDbConnection->table('user_permissions')->insert([
                            'user_id' => $userId,
                            'permission_id' => $permission->permission_id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    // Check if permission is already assigned to the admin role
                    $rolePermissionAssigned = $businessDbConnection->table('role_permissions')
                        ->where('role_id', $roleId)
                        ->where('permission_id', $permission->permission_id)
                        ->where('scope_id', $businessId)
                        ->exists();

                    if (!$rolePermissionAssigned) {
                        $businessDbConnection->table('role_permissions')->insert([
                            'role_id' => $roleId,
                            'permission_id' => $permission->permission_id,
                            'scope_id' => $businessId,
                            'valid_from' => now(),
                            'valid_until' => null,
                            'is_active' => 1,
                            'created_by' => 'system',
                            'updated_by' => 'system',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }

            return true;
        } catch (Exception $e) {
            throw new Exception("Failed to load and assign permissions: " . $e->getMessage());
        }
    }

    /**
     * Orchestrate the setup of business user and permissions.
     *
     * @param string $businessId
     * @return array
     * @throws Exception
     */
    public function setupBusinessUserAndPermissions(string $businessId): array
    {
        try {
            $business = $this->fetchBusinessDetails($businessId);
            $this->createSystemRecord($businessId, $business);
            $centralUserId = $this->createCentralDBUser($businessId, $business);
            $adminResult = $this->createBusinessDBAdmin($businessId, $business, $centralUserId);
            $businessUserId = $adminResult['user_id'];
            $roleId = $adminResult['role_id'];
            $this->loadAndAssignPermissions($businessId, $businessUserId, $roleId);

            return [
                'status' => true,
                'message' => "User and permissions setup completed successfully for business ID: $businessId",
                'central_user_id' => $centralUserId,
                'business_user_id' => $businessUserId,
                'role_id' => $roleId
            ];
        } catch (Exception $e) {
            throw new Exception("User and permissions setup failed: " . $e->getMessage());
        }
    }
}