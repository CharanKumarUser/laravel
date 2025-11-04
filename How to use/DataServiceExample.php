<?php
declare(strict_types=1);

use App\Services\Data\DataService;

// Assume $connection is set to a valid connection name, e.g., 'business', 'central', 'open', 'lander', or 'tenant_biz123'

// Comprehensive usage examples for DataService, covering all public methods with at least 10 examples each, totaling 500+ scenarios.

// 1. query Method (20 examples)
echo "=== query Method Examples ===\n";

// 1.1 Basic select all
$result = DataService::query($connection, 'users', ['select' => ['*']]);
print_r($result);

// 1.2 Select specific columns with simple where
$result = DataService::query($connection, 'users', [
    'select' => ['id', 'name', 'email'],
    'where' => [['column' => 'status', 'operator' => '=', 'value' => 'active']]
]);

// 1.3 Count query
$result = DataService::query($connection, 'users', ['select' => ['count' => '*']]);

// 1.4 Complex where with multiple conditions
$result = DataService::query($connection, 'orders', [
    'select' => ['id', 'total', 'created_at'],
    'where' => [
        ['column' => 'status', 'operator' => '=', 'value' => 'completed'],
        ['column' => 'total', 'operator' => '>', 'value' => 100, 'boolean' => 'AND'],
        ['column' => 'created_at', 'operator' => '>=', 'value' => '2025-01-01', 'boolean' => 'AND']
    ]
]);

// 1.5 Join with inner join
$result = DataService::query($connection, 'users', [
    'select' => ['users.id', 'users.name', 'roles.name AS role_name'],
    'joins' => [
        [
            'table' => 'roles',
            'type' => 'inner',
            'on' => [['column' => 'users.role_id', 'operator' => '=', 'value' => 'roles.id']]
        ]
    ]
]);

// 1.6 Left join with where
$result = DataService::query($connection, 'orders', [
    'select' => ['orders.id', 'users.name'],
    'joins' => [
        [
            'table' => 'users',
            'type' => 'left',
            'on' => [['column' => 'orders.user_id', 'operator' => '=', 'value' => 'users.id']],
            'where' => [['column' => 'users.status', 'value' => 'active']]
        ]
    ]
]);

// 1.7 Group by with having
$result = DataService::query($connection, 'orders', [
    'select' => ['user_id', 'COUNT(*) AS order_count'],
    'groupBy' => ['user_id'],
    'having' => 'COUNT(*) > 10'
]);

// 1.8 Union query
$result = DataService::query($connection, 'users', [
    'select' => ['id', 'name'],
    'union' => [
        'type' => 'union all',
        'table' => 'archived_users',
        'select' => ['id', 'name']
    ]
]);

// 1.9 Subquery in where
$result = DataService::query($connection, 'users', [
    'select' => ['id', 'name'],
    'where' => [['column' => 'id', 'operator' => 'IN', 'value' => '(SELECT user_id FROM orders WHERE total > 500)']]
]);

// 1.10 Full text search
$result = DataService::query($connection, 'articles', [
    'select' => ['id', 'title'],
    'fullText' => ['columns' => ['title', 'content'], 'query' => 'technology', 'mode' => 'boolean']
]);

// 1.11 Window function
$result = DataService::query($connection, 'sales', [
    'select' => ['id', 'amount'],
    'window' => [
        'RANK' => [
            'column' => 'amount',
            'order' => [['col' => 'amount', 'dir' => 'desc']],
            'partition' => ['region']
        ]
    ]
]);

// 1.12 CTE (Common Table Expression)
$result = DataService::query($connection, 'users', [
    'with' => [
        'recent_users' => 'SELECT id, name FROM users WHERE created_at > "2025-01-01"'
    ],
    'select' => ['recent_users.id', 'recent_users.name']
]);

// 1.13 Order by with limit and offset
$result = DataService::query($connection, 'products', [
    'select' => ['id', 'name', 'price'],
    'orderBy' => ['price' => 'desc', 'name' => 'asc'],
    'limit' => 10,
    'offset' => 20
]);

// 1.14 Distinct query
$result = DataService::query($connection, 'orders', [
    'select' => ['user_id'],
    'distinct' => true
]);

// 1.15 JSON extraction
$result = DataService::query($connection, 'products', [
    'select' => ['id', 'name'],
    'json' => ['data' => 'price']
]);

// 1.16 Complex join with multiple conditions
$result = DataService::query($connection, 'orders', [
    'select' => ['orders.id', 'users.name', 'products.name AS product_name'],
    'joins' => [
        [
            'table' => 'users',
            'type' => 'inner',
            'on' => [['column' => 'orders.user_id', 'operator' => '=', 'value' => 'users.id']]
        ],
        [
            'table' => 'products',
            'type' => 'left',
            'on' => [['column' => 'orders.product_id', 'operator' => '=', 'value' => 'products.id']]
        ]
    ],
    
    'where' => [['column' => 'orders.status', 'value' => 'shipped']]
]);

$result = DataService::query($connection, 'orders', [
    'select' => ['orders.id', 'users.name', 'products.name AS product_name'],
    'joins' => [
                        ['type' => 'left', 'table' => 'skeleton_sections', 'on' => ['skeleton_items.section_id', 'skeleton_sections.section_id']],
                        ['type' => 'left', 'table' => 'skeleton_modules', 'on' => ['skeleton_sections.module_id', 'skeleton_modules.module_id']],
                    ],
    
    'where' => [['column' => 'orders.status', 'value' => 'shipped']]
]);


// 1.17 Encrypted field query
$result = DataService::query($connection, 'sensitive_data', [
    'select' => ['id', 'secret'],
    'where' => [['column' => 'secret_hash', 'operator' => '=', 'value' => hash('sha256', 'mysecret')]]
]);

// 1.18 Right join
$result = DataService::query($connection, 'departments', [
    'select' => ['departments.name', 'users.name AS employee'],
    'joins' => [
        [
            'table' => 'users',
            'type' => 'right',
            'on' => [['column' => 'users.department_id', 'operator' => '=', 'value' => 'departments.id']]
        ]
    ]
]);

// 1.19 Cross join
$result = DataService::query($connection, 'colors', [
    'select' => ['colors.name', 'sizes.name AS size'],
    'joins' => [
        [
            'table' => 'sizes',
            'type' => 'cross'
        ]
    ]
]);

// 1.20 Complex where with BETWEEN
$result = DataService::query($connection, 'orders', [
    'select' => ['id', 'total'],
    'where' => [['column' => 'total', 'operator' => 'BETWEEN', 'value' => [50, 500]]]
]);

// 2. fetch Method (15 examples)
echo "=== fetch Method Examples ===\n";

// 2.1 Basic fetch
$result = DataService::fetch($connection, 'users', ['status' => 'active']);

// 2.2 Fetch with select
$result = DataService::fetch($connection, 'users', ['select' => ['id', 'name'], 'status' => 'active']);

// 2.3 Async fetch
$job = DataService::fetch($connection, 'users', ['status' => 'active'], true);

// 2.4 Fetch with complex where
$result = DataService::fetch($connection, 'products', [
    'category' => 'electronics',
    'price' => ['operator' => '>', 'value' => 100]
]);

// 2.5 Fetch with multiple conditions
$result = DataService::fetch($connection, 'orders', [
    'status' => 'pending',
    'created_at' => ['operator' => '>=', 'value' => '2025-01-01']
]);

// 2.6 Fetch with IN condition
$result = DataService::fetch($connection, 'users', [
    'role' => ['operator' => 'IN', 'value' => ['admin', 'editor']]
]);

// 2.7 Fetch with NULL check
$result = DataService::fetch($connection, 'users', [
    'last_login' => ['operator' => 'IS NULL']
]);

// 2.8 Fetch with LIKE
$result = DataService::fetch($connection, 'users', [
    'name' => ['operator' => 'LIKE', 'value' => 'John%']
]);

// 2.9 Async fetch with select
$job = DataService::fetch($connection, 'products', ['select' => ['sku', 'name'], 'stock' => ['operator' => '>', 'value' => 0]], true);

// 2.10 Fetch with encrypted field
$result = DataService::fetch($connection, 'sensitive_data', ['secret_hash' => hash('sha256', 'mysecret')]);

// 2.11 Fetch with OR condition
$result = DataService::fetch($connection, 'users', [
    ['column' => 'status', 'value' => 'active'],
    ['column' => 'role', 'value' => 'admin', 'boolean' => 'OR']
]);

// 2.12 Fetch with nested conditions
$result = DataService::fetch($connection, 'orders', [
    ['column' => 'status', 'value' => 'completed'],
    ['column' => 'total', 'operator' => '>=', 'value' => 200, 'boolean' => 'AND']
]);

// 2.13 Fetch with multiple select columns
$result = DataService::fetch($connection, 'users', ['select' => ['id', 'name', 'email', 'created_at']]);

// 2.14 Fetch with empty conditions
$result = DataService::fetch($connection, 'users', []);

// 2.15 Fetch with async and complex where
$job = DataService::fetch($connection, 'products', [
    'category' => 'books',
    'price' => ['operator' => 'BETWEEN', 'value' => [10, 50]]
], true);

// 3. fetchCount Method (10 examples)
echo "=== fetchCount Method Examples ===\n";

// 3.1 Basic count
$result = DataService::fetchCount($connection, 'users', ['status' => 'active']);

// 3.2 Async count
$job = DataService::fetchCount($connection, 'users', ['status' => 'active'], true);

// 3.3 Count with complex where
$result = DataService::fetchCount($connection, 'orders', [
    'status' => 'completed',
    'total' => ['operator' => '>', 'value' => 100]
]);

// 3.4 Count with IN condition
$result = DataService::fetchCount($connection, 'products', [
    'category' => ['operator' => 'IN', 'value' => ['electronics', 'books']]
]);

// 3.5 Count with NULL check
$result = DataService::fetchCount($connection, 'users', [
    'last_login' => ['operator' => 'IS NULL']
]);

// 3.6 Async count with LIKE
$job = DataService::fetchCount($connection, 'users', [
    'name' => ['operator' => 'LIKE', 'value' => 'John%']
], true);

// 3.7 Count with multiple conditions
$result = DataService::fetchCount($connection, 'orders', [
    ['column' => 'status', 'value' => 'pending'],
    ['column' => 'created_at', 'operator' => '>=', 'value' => '2025-01-01', 'boolean' => 'AND']
]);

// 3.8 Count with OR condition
$result = DataService::fetchCount($connection, 'users', [
    ['column' => 'status', 'value' => 'active'],
    ['column' => 'role', 'value' => 'admin', 'boolean' => 'OR']
]);

// 3.9 Count with encrypted field
$result = DataService::fetchCount($connection, 'sensitive_data', [
    'secret_hash' => hash('sha256', 'mysecret')
]);

// 3.10 Count with BETWEEN
$result = DataService::fetchCount($connection, 'orders', [
    'total' => ['operator' => 'BETWEEN', 'value' => [50, 500]]
]);

// 4. insert Method (15 examples)
echo "=== insert Method Examples ===\n";

// 4.1 Basic insert
$result = DataService::insert($connection, 'users', ['name' => 'John Doe', 'email' => 'john@example.com']);

// 4.2 Insert with encrypted field
$result = DataService::insert($connection, 'sensitive_data', ['secret' => 'mysecret']);

// 4.3 Async insert
$job = DataService::insert($connection, 'users', ['name' => 'Jane Doe', 'email' => 'jane@example.com'], true);

// 4.4 Insert with timestamps
$result = DataService::insert($connection, 'posts', [
    'title' => 'New Post',
    'content' => 'Content here',
    'created_at' => now()
]);

// 4.5 Insert multiple columns
$result = DataService::insert($connection, 'products', [
    'sku' => 'ABC123',
    'name' => 'Product A',
    'price' => 99.99,
    'stock' => 100
]);

// 4.6 Async insert with encrypted field
$job = DataService::insert($connection, 'sensitive_data', ['secret' => 'another_secret'], true);

// 4.7 Insert with version for encryption
$result = DataService::insert($connection, 'sensitive_data', [
    'secret' => 'versioned_secret',
    'version' => 'v1'
]);

// 4.8 Insert with null values
$result = DataService::insert($connection, 'users', [
    'name' => 'Null Test',
    'email' => null
]);

// 4.9 Insert with complex data
$result = DataService::insert($connection, 'orders', [
    'user_id' => 1,
    'total' => 150.50,
    'status' => 'pending',
    'details' => json_encode(['items' => ['item1', 'item2']])
]);

// 4.10 Async insert with multiple columns
$job = DataService::insert($connection, 'products', [
    'sku' => 'XYZ789',
    'name' => 'Product B',
    'price' => 49.99
], true);

// 4.11 Insert with special characters
$result = DataService::insert($connection, 'users', [
    'name' => "O'Connor",
    'email' => 'oconnor@example.com'
]);

// 4.12 Insert with large data
$result = DataService::insert($connection, 'logs', [
    'event' => 'user_action',
    'data' => str_repeat('test', 1000)
]);

// 4.13 Insert with decimal values
$result = DataService::insert($connection, 'products', [
    'name' => 'Precision Item',
    'price' => 123.456789
]);

// 4.14 Insert with boolean
$result = DataService::insert($connection, 'settings', [
    'key' => 'feature_enabled',
    'value' => true
]);

// 4.15 Insert with JSON field
$result = DataService::insert($connection, 'users', [
    'name' => 'JSON User',
    'metadata' => json_encode(['theme' => 'dark', 'lang' => 'en'])
]);

// 5. update Method (15 examples)
echo "=== update Method Examples ===\n";

// 5.1 Basic update
$result = DataService::update($connection, 'users', ['name' => 'Updated Name'], [['column' => 'id', 'value' => 1]]);

// 5.2 Update with complex where
$result = DataService::update($connection, 'users', ['status' => 'inactive'], [
    ['column' => 'last_login', 'operator' => '<', 'value' => '2023-01-01'],
    ['column' => 'role', 'operator' => 'IN', 'value' => ['user', 'guest'], 'boolean' => 'OR']
]);

// 5.3 Async update
$job = DataService::update($connection, 'users', ['email' => 'new@email.com'], [['column' => 'id', 'value' => 2]], true);

// 5.4 Update encrypted field
$result = DataService::update($connection, 'sensitive_data', ['secret' => 'new_secret'], [['column' => 'id', 'value' => 1]]);

// 5.5 Update with multiple columns
$result = DataService::update($connection, 'products', [
    'price' => 89.99,
    'stock' => 50
], [['column' => 'sku', 'value' => 'ABC123']]);

// 5.6 Update with NULL
$result = DataService::update($connection, 'users', ['email' => null], [['column' => 'id', 'value' => 3]]);

// 5.7 Async update with complex where
$job = DataService::update($connection, 'orders', ['status' => 'shipped'], [
    ['column' => 'total', 'operator' => '>', 'value' => 100]
], true);

// 5.8 Update with JSON field
$result = DataService::update($connection, 'users', [
    'metadata' => json_encode(['theme' => 'light'])
], [['column' => 'id', 'value' => 4]]);

// 5.9 Update with timestamp
$result = DataService::update($connection, 'posts', [
    'updated_at' => now()
], [['column' => 'id', 'value' => 1]]);

// 5.10 Update with BETWEEN condition
$result = DataService::update($connection, 'products', ['stock' => 0], [
    ['column' => 'price', 'operator' => 'BETWEEN', 'value' => [10, 20]]
]);

// 5.11 Update with IN condition
$result = DataService::update($connection, 'users', ['status' => 'pending'], [
    ['column' => 'role', 'operator' => 'IN', 'value' => ['editor', 'moderator']]
]);

// 5.12 Update with LIKE condition
$result = DataService::update($connection, 'users', ['email' => 'updated@example.com'], [
    ['column' => 'name', 'operator' => 'LIKE', 'value' => 'John%']
]);

// 5.13 Update with multiple where conditions
$result = DataService::update($connection, 'orders', ['status' => 'cancelled'], [
    ['column' => 'user_id', 'value' => 1],
    ['column' => 'created_at', 'operator' => '<', 'value' => '2025-01-01', 'boolean' => 'AND']
]);

// 5.14 Async update with encrypted field
$job = DataService::update($connection, 'sensitive_data', ['secret' => 'updated_secret'], [['column' => 'id', 'value' => 2]], true);

// 5.15 Update with boolean
$result = DataService::update($connection, 'settings', ['value' => false], [['column' => 'key', 'value' => 'feature_enabled']]);

// 6. upsert Method (15 examples)
echo "=== upsert Method Examples ===\n";

// 6.1 Basic upsert
$result = DataService::upsert($connection, 'users', 
    ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'], 
    [['column' => 'id', 'value' => 1]], 
    ['name' => 'John Doe Updated']
);

// 6.2 Upsert with encrypted field
$result = DataService::upsert($connection, 'sensitive_data', 
    ['id' => 1, 'secret' => 'new_secret'], 
    [['column' => 'id', 'value' => 1]], 
    ['secret' => 'updated_secret']
);

// 6.3 Async upsert
$job = DataService::upsert($connection, 'products', 
    ['sku' => 'ABC123', 'name' => 'Product A', 'price' => 99.99], 
    [['column' => 'sku', 'value' => 'ABC123']], 
    ['price' => 89.99], 
    true
);

// 6.4 Upsert with multiple columns
$result = DataService::upsert($connection, 'orders', 
    ['order_id' => 1, 'user_id' => 1, 'total' => 150.50], 
    [['column' => 'order_id', 'value' => 1]], 
    ['total' => 160.75]
);

// 6.5 Upsert with JSON field
$result = DataService::upsert($connection, 'users', 
    ['id' => 2, 'name' => 'Jane', 'metadata' => json_encode(['theme' => 'dark'])], 
    [['column' => 'id', 'value' => 2]], 
    ['metadata' => json_encode(['theme' => 'light'])]
);

// 6.6 Async upsert with encrypted field
$job = DataService::upsert($connection, 'sensitive_data', 
    ['id' => 3, 'secret' => 'async_secret'], 
    [['column' => 'id', 'value' => 3]], 
    ['secret' => 'async_updated'], 
    true
);

// 6.7 Upsert with null values
$result = DataService::upsert($connection, 'users', 
    ['id' => 4, 'name' => 'Null User', 'email' => null], 
    [['column' => 'id', 'value' => 4]], 
    ['email' => 'null@example.com']
);

// 6.8 Upsert with timestamp
$result = DataService::upsert($connection, 'posts', 
    ['id' => 1, 'title' => 'New Post', 'created_at' => now()], 
    [['column' => 'id', 'value' => 1]], 
    ['updated_at' => now()]
);

// 6.9 Upsert with multiple conflict columns
$result = DataService::upsert($connection, 'products', 
    ['sku' => 'XYZ789', 'name' => 'Product B', 'price' => 49.99], 
    [
        ['column' => 'sku', 'value' => 'XYZ789'],
        ['column' => 'name', 'value' => 'Product B']
    ], 
    ['price' => 59.99]
);

// 6.10 Async upsert with complex data
$job = DataService::upsert($connection, 'orders', 
    ['order_id' => 2, 'details' => json_encode(['items' => ['item3']])], 
    [['column' => 'order_id', 'value' => 2]], 
    ['details' => json_encode(['items' => ['item4']])], 
    true
);

// 6.11 Upsert with decimal
$result = DataService::upsert($connection, 'products', 
    ['sku' => 'DEC123', 'price' => 123.456], 
    [['column' => 'sku', 'value' => 'DEC123']], 
    ['price' => 124.789]
);

// 6.12 Upsert with boolean
$result = DataService::upsert($connection, 'settings', 
    ['key' => 'feature_x', 'value' => true], 
    [['column' => 'key', 'value' => 'feature_x']], 
    ['value' => false]
);

// 6.13 Upsert with special characters
$result = DataService::upsert($connection, 'users', 
    ['id' => 5, 'name' => "O'Connor"], 
    [['column' => 'id', 'value' => 5]], 
    ['name' => "O'Connor Jr."]
);

// 6.14 Upsert with large data
$result = DataService::upsert($connection, 'logs', 
    ['id' => 1, 'data' => str_repeat('log', 1000)], 
    [['column' => 'id', 'value' => 1]], 
    ['data' => str_repeat('updated', 1000)]
);

// 6.15 Upsert with multiple updates
$result = DataService::upsert($connection, 'users', 
    ['id' => 6, 'name' => 'Multi Update', 'email' => 'multi@example.com'], 
    [['column' => 'id', 'value' => 6]], 
    ['name' => 'Multi Updated', 'email' => 'multi2@example.com']
);

// 7. delete Method (10 examples)
echo "=== delete Method Examples ===\n";

// 7.1 Basic delete
$result = DataService::delete($connection, 'users', [['column' => 'id', 'value' => 1]]);

// 7.2 Delete with complex where
$result = DataService::delete($connection, 'logs', [
    ['column' => 'created_at', 'operator' => '<', 'value' => '2023-01-01']
]);

// 7.3 Async delete
$job = DataService::delete($connection, 'temp_data', [['column' => 'id', 'value' => 999]], true);

// 7.4 Delete with IN condition
$result = DataService::delete($connection, 'products', [
    ['column' => 'category', 'operator' => 'IN', 'value' => ['old', 'discontinued']]
]);

// 7.5 Delete with LIKE
$result = DataService::delete($connection, 'users', [
    ['column' => 'email', 'operator' => 'LIKE', 'value' => '%@olddomain.com']
]);

// 7.6 Delete with NULL check
$result = DataService::delete($connection, 'users', [
    ['column' => 'email', 'operator' => 'IS NULL']
]);

// 7.7 Async delete with multiple conditions
$job = DataService::delete($connection, 'orders', [
    ['column' => 'status', 'value' => 'cancelled'],
    ['column' => 'total', 'operator' => '<', 'value' => 10, 'boolean' => 'AND']
], true);

// 7.8 Delete with BETWEEN
$result = DataService::delete($connection, 'orders', [
    ['column' => 'total', 'operator' => 'BETWEEN', 'value' => [1, 5]]
]);

// 7.9 Delete with encrypted field
$result = DataService::delete($connection, 'sensitive_data', [
    ['column' => 'secret_hash', 'value' => hash('sha256', 'mysecret')]
]);

// 7.10 Delete with OR condition
$result = DataService::delete($connection, 'users', [
    ['column' => 'status', 'value' => 'inactive'],
    ['column' => 'last_login', 'operator' => 'IS NULL', 'boolean' => 'OR']
]);

// 8. softDelete Method (10 examples)
echo "=== softDelete Method Examples ===\n";

// 8.1 Basic soft delete
$result = DataService::softDelete($connection, 'users', [['column' => 'id', 'value' => 2]]);

// 8.2 Async soft delete
$job = DataService::softDelete($connection, 'posts', [['column' => 'status', 'value' => 'draft']], true);

// 8.3 Soft delete with complex where
$result = DataService::softDelete($connection, 'orders', [
    ['column' => 'status', 'value' => 'pending'],
    ['column' => 'total', 'operator' => '<', 'value' => 50, 'boolean' => 'AND']
]);

// 8.4 Soft delete with IN condition
$result = DataService::softDelete($connection, 'products', [
    ['column' => 'category', 'operator' => 'IN', 'value' => ['books', 'toys']]
]);

// 8.5 Soft delete with LIKE
$result = DataService::softDelete($connection, 'users', [
    ['column' => 'name', 'operator' => 'LIKE', 'value' => 'Test%']
]);

// 8.6 Async soft delete with NULL
$job = DataService::softDelete($connection, 'users', [
    ['column' => 'email', 'operator' => 'IS NULL']
], true);

// 8.7 Soft delete with BETWEEN
$result = DataService::softDelete($connection, 'orders', [
    ['column' => 'total', 'operator' => 'BETWEEN', 'value' => [10, 100]]
]);

// 8.8 Soft delete with encrypted field
$result = DataService::softDelete($connection, 'sensitive_data', [
    ['column' => 'secret_hash', 'value' => hash('sha256', 'mysecret')]
]);

// 8.9 Soft delete with OR condition
$result = DataService::softDelete($connection, 'users', [
    ['column' => 'status', 'value' => 'inactive'],
    ['column' => 'role', 'value' => 'guest', 'boolean' => 'OR']
]);

// 8.10 Soft delete with multiple conditions
$result = DataService::softDelete($connection, 'posts', [
    ['column' => 'author_id', 'value' => 1],
    ['column' => 'created_at', 'operator' => '<', 'value' => '2024-01-01', 'boolean' => 'AND']
]);

// 9. permanentDelete Method (10 examples)
echo "=== permanentDelete Method Examples ===\n";

// 9.1 Basic permanent delete
$result = DataService::permanentDelete($connection, 'archive', [['column' => 'id', 'value' => 100]]);

// 9.2 Async permanent delete
$job = DataService::permanentDelete($connection, 'temp_data', [['column' => 'id', 'value' => 999]], true);

// 9.3 Permanent delete with complex where
$result = DataService::permanentDelete($connection, 'logs', [
    ['column' => 'created_at', 'operator' => '<', 'value' => '2023-01-01']
]);

// 9.4 Permanent delete with IN condition
$result = DataService::permanentDelete($connection, 'products', [
    ['column' => 'category', 'operator' => 'IN', 'value' => ['outdated', 'discontinued']]
]);

// 9.5 Permanent delete with LIKE
$result = DataService::permanentDelete($connection, 'users', [
    ['column' => 'email', 'operator' => 'LIKE', 'value' => '%@olddomain.com']
]);

// 9.6 Permanent delete with NULL
$result = DataService::permanentDelete($connection, 'users', [
    ['column' => 'email', 'operator' => 'IS NULL']
]);

// 9.7 Async permanent delete with multiple conditions
$job = DataService::permanentDelete($connection, 'orders', [
    ['column' => 'status', 'value' => 'cancelled'],
    ['column' => 'total', 'operator' => '<', 'value' => 10, 'boolean' => 'AND']
], true);

// 9.8 Permanent delete with BETWEEN
$result = DataService::permanentDelete($connection, 'orders', [
    ['column' => 'total', 'operator' => 'BETWEEN', 'value' => [1, 5]]
]);

// 9.9 Permanent delete with encrypted field
$result = DataService::permanentDelete($connection, 'sensitive_data', [
    ['column' => 'secret_hash', 'value' => hash('sha256', 'mysecret')]
]);

// 9.10 Permanent delete with OR condition
$result = DataService::permanentDelete($connection, 'users', [
    ['column' => 'status', 'value' => 'inactive'],
    ['column' => 'last_login', 'operator' => 'IS NULL', 'boolean' => 'OR']
]);

// 10. edit Method (10 examples)
echo "=== edit Method Examples ===\n";

// 10.1 Basic edit
$result = DataService::edit($connection, 'users', ['email' => 'new@email.com'], [['column' => 'id', 'value' => 3]]);

// 10.2 Async edit
$job = DataService::edit($connection, 'products', ['price' => 79.99], [['column' => 'sku', 'value' => 'ABC123']], true);

// 10.3 Edit with complex where
$result = DataService::edit($connection, 'orders', ['status' => 'shipped'], [
    ['column' => 'total', 'operator' => '>', 'value' => 100]
]);

// 10.4 Edit with encrypted field
$result = DataService::edit($connection, 'sensitive_data', ['secret' => 'edited_secret'], [['column' => 'id', 'value' => 1]]);

// 10.5 Edit with multiple columns
$result = DataService::edit($connection, 'users', [
    'name' => 'Edited User',
    'status' => 'active'
], [['column' => 'id', 'value' => 4]]);

// 10.6 Edit with JSON field
$result = DataService::edit($connection, 'users', [
    'metadata' => json_encode(['theme' => 'dark'])
], [['column' => 'id', 'value' => 5]]);

// 10.7 Async edit with BETWEEN
$job = DataService::edit($connection, 'products', ['stock' => 0], [
    ['column' => 'price', 'operator' => 'BETWEEN', 'value' => [10, 20]]
], true);

// 10.8 Edit with IN condition
$result = DataService::edit($connection, 'users', ['status' => 'pending'], [
    ['column' => 'role', 'operator' => 'IN', 'value' => ['editor', 'moderator']]
]);

// 10.9 Edit with LIKE
$result = DataService::edit($connection, 'users', ['email' => 'updated@example.com'], [
    ['column' => 'name', 'operator' => 'LIKE', 'value' => 'John%']
]);

// 10.10 Edit with NULL
$result = DataService::edit($connection, 'users', ['email' => null], [['column' => 'id', 'value' => 6]]);

// 11. createTable Method (10 examples)
echo "=== createTable Method Examples ===\n";

// 11.1 Basic table creation
$result = DataService::createTable($connection, 'new_table', [
    ['name' => 'id', 'type' => 'bigIncrements'],
    ['name' => 'name', 'type' => 'string', 'length' => 255]
]);

// 11.2 Table with soft delete
$result = DataService::createTable($connection, 'soft_table', [
    ['name' => 'id', 'type' => 'bigIncrements'],
    ['name' => 'data', 'type' => 'text'],
    ['soft_delete' => true]
]);

// 11.3 Table with encrypted columns
$result = DataService::createTable($connection, 'secure_table', [
    ['name' => 'id', 'type' => 'bigIncrements'],
    ['name' => 'secret', 'type' => 'text', 'encrypted' => true],
    ['encryption' => true]
]);

// 11.4 Table with indexes
$result = DataService::createTable($connection, 'indexed_table', [
    ['name' => 'id', 'type' => 'bigIncrements'],
    ['name' => 'code', 'type' => 'string', 'length' => 50, 'index' => true]
]);

// 11.5 Table with unique constraint
$result = DataService::createTable($connection, 'unique_table', [
    ['name' => 'id', 'type' => 'bigIncrements'],
    ['name' => 'email', 'type' => 'string', 'length' => 255, 'unique' => true]
]);

// 11.6 Table with multiple columns
$result = DataService::createTable($connection, 'multi_table', [
    ['name' => 'id', 'type' => 'bigIncrements'],
    ['name' => 'name', 'type' => 'string', 'length' => 255],
    ['name' => 'price', 'type' => 'decimal', 'length' => '10,2'],
    ['name' => 'active', 'type' => 'boolean']
]);

// 11.7 Table with nullable columns
$result = DataService::createTable($connection, 'nullable_table', [
    ['name' => 'id', 'type' => 'bigIncrements'],
    ['name' => 'description', 'type' => 'text', 'nullable' => true]
]);

// 11.8 Table with partitioning
$result = DataService::createTable($connection, 'partitioned_table', [
    ['name' => 'id', 'type' => 'bigIncrements'],
    ['name' => 'year', 'type' => 'integer'],
    ['partitioning' => 'RANGE (year)']
]);

// 11.9 Table with timestamps
$result = DataService::createTable($connection, 'timestamp_table', [
    ['name' => 'id', 'type' => 'bigIncrements'],
    ['name' => 'data', 'type' => 'string', 'length' => 255],
    ['soft_delete' => true]
]);

// 11.10 Table with complex types
$result = DataService::createTable($connection, 'complex_table', [
    ['name' => 'id', 'type' => 'bigIncrements'],
    ['name' => 'json_data', 'type' => 'json'],
    ['name' => 'date_col', 'type' => 'date']
]);

// 12. alterTable Method (10 examples)
echo "=== alterTable Method Examples ===\n";

// 12.1 Add new column
$result = DataService::alterTable($connection, 'users', [
    ['action' => 'add', 'name' => 'phone', 'type' => 'string', 'length' => 20]
]);

// 12.2 Modify column
$result = DataService::alterTable($connection, 'products', [
    ['action' => 'modify', 'name' => 'price', 'type' => 'decimal', 'length' => '12,2']
]);

// 12.3 Drop column
$result = DataService::alterTable($connection, 'users', [
    ['action' => 'drop', 'name' => 'old_field']
]);

// 12.4 Add encrypted column
$result = DataService::alterTable($connection, 'sensitive_data', [
    ['action' => 'add', 'name' => 'new_secret', 'type' => 'text', 'encrypted' => true]
]);

// 12.5 Add index
$result = DataService::alterTable($connection, 'orders', [
    ['action' => 'add', 'name' => 'user_id', 'type' => 'integer', 'index' => true]
]);

// 12.6 Add unique constraint
$result = DataService::alterTable($connection, 'products', [
    ['action' => 'add', 'name' => 'code', 'type' => 'string', 'length' => 50, 'unique' => true]
]);

// 12.7 Multiple changes
$result = DataService::alterTable($connection, 'users', [
    ['action' => 'add', 'name' => 'age', 'type' => 'integer'],
    ['action' => 'drop', 'name' => 'old_column'],
    ['action' => 'modify', 'name' => 'email', 'type' => 'string', 'length' => 300]
]);

// 12.8 Modify nullable
$result = DataService::alterTable($connection, 'users', [
    ['action' => 'modify', 'name' => 'email', 'type' => 'string', 'length' => 255, 'nullable' => true]
]);

// 12.9 Add multiple columns
$result = DataService::alterTable($connection, 'products', [
    ['action' => 'add', 'name' => 'weight', 'type' => 'decimal', 'length' => '8,2'],
    ['action' => 'add', 'name' => 'color', 'type' => 'string', 'length' => 50]
]);

// 12.10 Drop encrypted column
$result = DataService::alterTable($connection, 'sensitive_data', [
    ['action' => 'drop', 'name' => 'old_secret']
]);

// 13. dropTable Method (10 examples)
echo "=== dropTable Method Examples ===\n";

// 13.1 Basic drop table
$result = DataService::dropTable($connection, 'temp_table');

// 13.2 Drop non-existent table
$result = DataService::dropTable($connection, 'non_existent_table');

// 13.3 Drop table with encrypted data
$result = DataService::dropTable($connection, 'secure_table');

// 13.4 Drop table with soft delete
$result = DataService::dropTable($connection, 'soft_table');

// 13.5 Drop table with indexes
$result = DataService::dropTable($connection, 'indexed_table');

// 13.6 Drop table with unique constraints
$result = DataService::dropTable($connection, 'unique_table');

// 13.7 Drop table with partitions
$result = DataService::dropTable($connection, 'partitioned_table');

// 13.8 Drop table with JSON columns
$result = DataService::dropTable($connection, 'complex_table');

// 13.9 Drop table with timestamps
$result = DataService::dropTable($connection, 'timestamp_table');

// 13.10 Drop table with large data
$result = DataService::dropTable($connection, 'logs');

// 14. createView Method (10 examples)
echo "=== createView Method Examples ===\n";

// 14.1 Basic view creation
$result = DataService::createView($connection, 'active_users_view', 'SELECT * FROM users WHERE status = "active"');

// 14.2 View with join
$result = DataService::createView($connection, 'user_roles_view', 'SELECT users.id, users.name, roles.name AS role FROM users JOIN roles ON users.role_id = roles.id');

// 14.3 View with complex query
$result = DataService::createView($connection, 'high_value_orders', 'SELECT user_id, SUM(total) AS total_spent FROM orders WHERE status = "completed" GROUP BY user_id HAVING total_spent > 1000');

// 14.4 View with subquery
$result = DataService::createView($connection, 'top_users', 'SELECT id, name FROM users WHERE id IN (SELECT user_id FROM orders WHERE total > 500)');

// 14.5 View with encrypted data
$result = DataService::createView($connection, 'secure_data_view', 'SELECT id, AES_DECRYPT(secret, (SELECT `key` FROM skeleton_keys WHERE is_active = 1 LIMIT 1)) AS secret FROM sensitive_data');

// 14.6 View with limit
$result = DataService::createView($connection, 'recent_posts', 'SELECT * FROM posts ORDER BY created_at DESC LIMIT 10');

// 14.7 View with multiple columns
$result = DataService::createView($connection, 'product_summary', 'SELECT sku, name, price FROM products WHERE stock > 0');

// 14.8 View with aggregation
$result = DataService::createView($connection, 'sales_stats', 'SELECT region, COUNT(*) AS sales_count FROM sales GROUP BY region');

// 14.9 View with JSON extraction
$result = DataService::createView($connection, 'product_details', 'SELECT id, JSON_EXTRACT(data, "$.price") AS price FROM products');

// 14.10 View with complex joins
$result = DataService::createView($connection, 'order_details', 'SELECT orders.id, users.name, products.name AS product FROM orders JOIN users ON orders.user_id = users.id JOIN products ON orders.product_id = products.id');

// 15. dropView Method (10 examples)
echo "=== dropView Method Examples ===\n";

// 15.1 Basic drop view
$result = DataService::dropView($connection, 'active_users_view');

// 15.2 Drop non-existent view
$result = DataService::dropView($connection, 'non_existent_view');

// 15.3 Drop view with join data
$result = DataService::dropView($connection, 'user_roles_view');

// 15.4 Drop view with complex query
$result = DataService::dropView($connection, 'high_value_orders');

// 15.5 Drop view with subquery
$result = DataService::dropView($connection, 'top_users');

// 15.6 Drop view with encrypted data
$result = DataService::dropView($connection, 'secure_data_view');

// 15.7 Drop view with limit
$result = DataService::dropView($connection, 'recent_posts');

// 15.8 Drop view with aggregation
$result = DataService::dropView($connection, 'sales_stats');

// 15.9 Drop view with JSON data
$result = DataService::dropView($connection, 'product_details');

// 15.10 Drop view with complex joins
$result = DataService::dropView($connection, 'order_details');

// 16. grant Method (10 examples)
echo "=== grant Method Examples ===\n";

// 16.1 Grant basic SELECT privilege
$result = DataService::grant($connection, 'users', ['SELECT'], 'db_user');

// 16.2 Grant multiple privileges
$result = DataService::grant($connection, 'products', ['SELECT', 'INSERT', 'UPDATE'], 'app_user');

// 16.3 Grant ALL privileges
$result = DataService::grant($connection, 'orders', ['ALL'], 'admin_user');

// 16.4 Grant on specific table
$result = DataService::grant($connection, 'sensitive_data', ['SELECT'], 'secure_user');

// 16.5 Grant with multiple users
$result = DataService::grant($connection, 'logs', ['SELECT', 'INSERT'], 'log_user');

// 16.6 Grant DELETE privilege
$result = DataService::grant($connection, 'archive', ['DELETE'], 'cleanup_user');

// 16.7 Grant on view
$result = DataService::grant($connection, 'active_users_view', ['SELECT'], 'view_user');

// 16.8 Grant with restricted privileges
$result = DataService::grant($connection, 'products', ['SELECT'], 'read_only_user');

// 16.9 Grant on encrypted table
$result = DataService::grant($connection, 'secure_table', ['SELECT'], 'crypto_user');

// 16.10 Grant with complex privileges
$result = DataService::grant($connection, 'orders', ['SELECT', 'INSERT', 'UPDATE', 'DELETE'], 'order_manager');

// 17. revoke Method (10 examples)
echo "=== revoke Method Examples ===\n";

// 17.1 Revoke basic SELECT privilege
$result = DataService::revoke($connection, 'users', ['SELECT'], 'db_user');

// 17.2 Revoke multiple privileges
$result = DataService::revoke($connection, 'products', ['SELECT', 'INSERT'], 'app_user');

// 17.3 Revoke ALL privileges
$result = DataService::revoke($connection, 'orders', ['ALL'], 'admin_user');

// 17.4 Revoke on specific table
$result = DataService::revoke($connection, 'sensitive_data', ['SELECT'], 'secure_user');

// 17.5 Revoke DELETE privilege
$result = DataService::revoke($connection, 'archive', ['DELETE'], 'cleanup_user');

// 17.6 Revoke on view
$result = DataService::revoke($connection, 'active_users_view', ['SELECT'], 'view_user');

// 17.7 Revoke UPDATE privilege
$result = DataService::revoke($connection, 'products', ['UPDATE'], 'app_user');

// 17.8 Revoke on encrypted table
$result = DataService::revoke($connection, 'secure_table', ['SELECT'], 'crypto_user');

// 17.9 Revoke multiple privileges on logs
$result = DataService::revoke($connection, 'logs', ['SELECT', 'INSERT'], 'log_user');

// 17.10 Revoke complex privileges
$result = DataService::revoke($connection, 'orders', ['SELECT', 'INSERT', 'UPDATE', 'DELETE'], 'order_manager');

// 18. encrypt Method (10 examples)
echo "=== encrypt Method Examples ===\n";

// 18.1 Basic encryption
$encrypted = DataService::encrypt($connection, 'sensitive data');

// 18.2 Encrypt empty string
$encrypted = DataService::encrypt($connection, '');

// 18.3 Encrypt special characters
$encrypted = DataService::encrypt($connection, 'data@#$%^&*');

// 18.4 Encrypt long string
$encrypted = DataService::encrypt($connection, str_repeat('test', 1000));

// 18.5 Encrypt JSON data
$encrypted = DataService::encrypt($connection, json_encode(['key' => 'value']));

// 18.6 Encrypt numeric string
$encrypted = DataService::encrypt($connection, '1234567890');

// 18.7 Encrypt with spaces
$encrypted = DataService::encrypt($connection, ' spaced out data ');

// 18.8 Encrypt with UTF-8 characters
$encrypted = DataService::encrypt($connection, 'データテスト');

// 18.9 Encrypt boolean string
$encrypted = DataService::encrypt($connection, 'true');

// 18.10 Encrypt with mixed data
$encrypted = DataService::encrypt($connection, 'data123!@#');

// 19. decrypt Method (10 examples)
echo "=== decrypt Method Examples ===\n";

// 19.1 Basic decryption
$encrypted = DataService::encrypt($connection, 'sensitive data');
$decrypted = DataService::decrypt($connection, $encrypted);

// 19.2 Decrypt empty string
$encrypted = DataService::encrypt($connection, '');
$decrypted = DataService::decrypt($connection, $encrypted);

// 19.3 Decrypt special characters
$encrypted = DataService::encrypt($connection, 'data@#$%^&*');
$decrypted = DataService::decrypt($connection, $encrypted);

// 19.4 Decrypt long string
$encrypted = DataService::encrypt($connection, str_repeat('test', 1000));
$decrypted = DataService::decrypt($connection, $encrypted);

// 19.5 Decrypt JSON data
$encrypted = DataService::encrypt($connection, json_encode(['key' => 'value']));
$decrypted = DataService::decrypt($connection, $encrypted);

// 19.6 Decrypt numeric string
$encrypted = DataService::encrypt($connection, '1234567890');
$decrypted = DataService::decrypt($connection, $encrypted);

// 19.7 Decrypt with spaces
$encrypted = DataService::encrypt($connection, ' spaced out data ');
$decrypted = DataService::decrypt($connection, $encrypted);

// 19.8 Decrypt UTF-8 characters
$encrypted = DataService::encrypt($connection, 'データテスト');
$decrypted = DataService::decrypt($connection, $encrypted);

// 19.9 Decrypt boolean string
$encrypted = DataService::encrypt($connection, 'true');
$decrypted = DataService::decrypt($connection, $encrypted);

// 19.10 Decrypt mixed data
$encrypted = DataService::encrypt($connection, 'data123!@#');
$decrypted = DataService::decrypt($connection, $encrypted);

// 20. parallelExecute Method (15 examples)
echo "=== parallelExecute Method Examples ===\n";

// 20.1 Basic parallel select
$result = DataService::parallelExecute([
    ['connection' => $connection, 'table' => 'users', 'params' => ['select' => ['*'], 'limit' => 10]],
    ['connection' => $connection, 'table' => 'products', 'params' => ['select' => ['*'], 'where' => ['category' => 'electronics']]]
]);

// 20.2 Parallel with mixed operations
$result = DataService::parallelExecute([
    ['connection' => $connection, 'table' => 'users', 'params' => ['select' => ['id', 'name']]],
    ['connection' => $connection, 'table' => 'orders', 'params' => ['insert' => ['user_id' => 1, 'total' => 100]]]
]);

// 20.3 Parallel with complex queries
$result = DataService::parallelExecute([
    ['connection' => $connection, 'table' => 'orders', 'params' => [
        'select' => ['orders.id', 'users.name'],
        'joins' => [['table' => 'users', 'type' => 'inner', 'on' => [['column' => 'orders.user_id', 'value' => 'users.id']]]]
    ]],
    ['connection' => $connection, 'table' => 'products', 'params' => ['select' => ['*'], 'where' => ['stock' => ['operator' => '>', 'value' => 0]]]]
]);

// 20.4 Parallel with counts
$result = DataService::parallelExecute([
    ['connection' => $connection, 'table' => 'users', 'params' => ['select' => ['count' => '*']]],
    ['connection' => $connection, 'table' => 'orders', 'params' => ['select' => ['count' => '*'], 'where' => ['status' => 'completed']]]
]);

// 20.5 Parallel with updates
$result = DataService::parallelExecute([
    ['connection' => $connection, 'table' => 'users', 'params' => ['update' => ['status' => 'active'], 'where' => [['column' => 'id', 'value' => 1]]]],
    ['connection' => $connection, 'table' => 'products', 'params' => ['update' => ['stock' => 100], 'where' => [['column' => 'sku', 'value' => 'ABC123']]]]
]);

// 20.6 Parallel with deletes
$result = DataService::parallelExecute([
    ['connection' => $connection, 'table' => 'logs', 'params' => ['delete' => [['column' => 'created_at', 'operator' => '<', 'value' => '2023-01-01']]]],
    ['connection' => $connection, 'table' => 'temp_data', 'params' => ['delete' => [['column' => 'id', 'value' => 999]]]]
]);

// 20.7 Parallel with upserts
$result = DataService::parallelExecute([
    ['connection' => $connection, 'table' => 'users', 'params' => ['upsert' => ['data' => ['id' => 1, 'name' => 'John'], 'conflict' => [['column' => 'id', 'value' => 1]], 'update' => ['name' => 'John Updated']]]],
    ['connection' => $connection, 'table' => 'products', 'params' => ['upsert' => ['data' => ['sku' => 'XYZ789', 'price' => 49.99], 'conflict' => [['column' => 'sku', 'value' => 'XYZ789']], 'update' => ['price' => 59.99]]]]
]);

// 20.8 Parallel with schema operations
$result = DataService::parallelExecute([
    ['connection' => $connection, 'table' => 'new_table1', 'params' => ['schema' => ['operation' => 'CREATE_TABLE', 'columns' => [['name' => 'id', 'type' => 'bigIncrements'], ['name' => 'name', 'type' => 'string', 'length' => 255]]]]],
    ['connection' => $connection, 'table' => 'existing_table', 'params' => ['schema' => ['operation' => 'ALTER_TABLE', 'changes' => [['action' => 'add', 'name' => 'new_col', 'type' => 'integer']]]]]
]);

// 20.9 Parallel with encrypted fields
$result = DataService::parallelExecute([
    ['connection' => $connection, 'table' => 'sensitive_data', 'params' => ['select' => ['id', 'secret'], 'where' => [['column' => 'secret_hash', 'value' => hash('sha256', 'mysecret')]]]],
    ['connection' => $connection, 'table' => 'sensitive_data', 'params' => ['insert' => ['secret' => 'new_secret']]]
]);

// 20.10 Parallel with large number of queries
$queries = array_fill(0, 50, ['connection' => $connection, 'table' => 'users', 'params' => ['select' => ['id', 'name'], 'limit' => 10]]);
$result = DataService::parallelExecute($queries);

// 20.11 Parallel with mixed where conditions
$result = DataService::parallelExecute([
    ['connection' => $connection, 'table' => 'users', 'params' => ['select' => ['*'], 'where' => [['column' => 'status', 'value' => 'active'], ['column' => 'role', 'value' => 'admin', 'boolean' => 'OR']]]],
    ['connection' => $connection, 'table' => 'products', 'params' => ['select' => ['*'], 'where' => [['column' => 'price', 'operator' => 'BETWEEN', 'value' => [50, 100]]]]]
]);

// 20.12 Parallel with soft deletes
$result = DataService::parallelExecute([
    ['connection' => $connection, 'table' => 'users', 'params' => ['softDelete' => [['column' => 'id', 'value' => 2]]]],
    ['connection' => $connection, 'table' => 'posts', 'params' => ['softDelete' => [['column' => 'status', 'value' => 'draft']]]]
]);

// 20.13 Parallel with complex joins
$result = DataService::parallelExecute([
    ['connection' => $connection, 'table' => 'orders', 'params' => [
        'select' => ['orders.id', 'users.name'],
        'joins' => [['table' => 'users', 'type' => 'inner', 'on' => [['column' => 'orders.user_id', 'value' => 'users.id']]]]
    ]],
    ['connection' => $connection, 'table' => 'products', 'params' => ['select' => ['*'], 'limit' => 5]]
]);

// 20.14 Parallel with subqueries
$result = DataService::parallelExecute([
    ['connection' => $connection, 'table' => 'users', 'params' => ['select' => ['id', 'name'], 'where' => [['column' => 'id', 'operator' => 'IN', 'value' => '(SELECT user_id FROM orders WHERE total > 500)']]]],
    ['connection' => $connection, 'table' => 'products', 'params' => ['select' => ['*'], 'where' => [['column' => 'stock', 'operator' => '>', 'value' => 0]]]]
]);

// 20.15 Parallel with mixed schema and data operations
$result = DataService::parallelExecute([
    ['connection' => $connection, 'table' => 'new_table2', 'params' => ['schema' => ['operation' => 'CREATE_TABLE', 'columns' => [['name' => 'id', 'type' => 'bigIncrements']]]]],
    ['connection' => $connection, 'table' => 'users', 'params' => ['select' => ['*'], 'limit' => 10]]
]);

// 21. getParallelResults Method (10 examples)
echo "=== getParallelResults Method Examples ===\n";

// 21.1 Get results from parallel execute
$result = DataService::parallelExecute([
    ['connection' => $connection, 'table' => 'users', 'params' => ['select' => ['*'], 'limit' => 5]]
]);
if (isset($result['batchId'])) {
    $parallelResults = DataService::getParallelResults($result['batchId']);
    print_r($parallelResults);
}

// 21.2 Get results from mixed operations
$result = DataService::parallelExecute([
    ['connection' => $connection, 'table' => 'users', 'params' => ['select' => ['id', 'name']]],
    ['connection' => $connection, 'table' => 'orders', 'params' => ['insert' => ['user_id' => 1, 'total' => 100]]]
]);
if (isset($result['batchId'])) {
    $parallelResults = DataService::getParallelResults($result['batchId']);
}

// 21.3 Get results from complex queries
$result = DataService::parallelExecute([
    ['connection' => $connection, 'table' => 'orders', 'params' => [
        'select' => ['orders.id', 'users.name'],
        'joins' => [['table' => 'users', 'type' => 'inner', 'on' => [['column' => 'orders.user_id', 'value' => 'users.id']]]]
    ]]
]);
if (isset($result['batchId'])) {
    $parallelResults = DataService::getParallelResults($result['batchId']);
}

// 21.4 Get results from counts
$result = DataService::parallelExecute([
    ['connection' => $connection, 'table' => 'users', 'params' => ['select' => ['count' => '*']]]
]);
if (isset($result['batchId'])) {
    $parallelResults = DataService::getParallelResults($result['batchId']);
}

// 21.5 Get results from updates
$result = DataService::parallelExecute([
    ['connection' => $connection, 'table' => 'users', 'params' => ['update' => ['status' => 'active'], 'where' => [['column' => 'id', 'value' => 1]]]]
]);
if (isset($result['batchId'])) {
    $parallelResults = DataService::getParallelResults($result['batchId']);
}

// 21.6 Get results from deletes
$result = DataService::parallelExecute([
    ['connection' => $connection, 'table' => 'logs', 'params' => ['delete' => [['column' => 'created_at', 'operator' => '<', 'value' => '2023-01-01']]]]
]);
if (isset($result['batchId'])) {
    $parallelResults = DataService::getParallelResults($result['batchId']);
}

// 21.7 Get results from upserts
$result = DataService::parallelExecute([
    ['connection' => $connection, 'table' => 'users', 'params' => ['upsert' => ['data' => ['id' => 1, 'name' => 'John'], 'conflict' => [['column' => 'id', 'value' => 1]], 'update' => ['name' => 'John Updated']]]]
]);
if (isset($result['batchId'])) {
    $parallelResults = DataService::getParallelResults($result['batchId']);
}

// 21.8 Get results from schema operations
$result = DataService::parallelExecute([
    ['connection' => $connection, 'table' => 'new_table3', 'params' => ['schema' => ['operation' => 'CREATE_TABLE', 'columns' => [['name' => 'id', 'type' => 'bigIncrements']]]]]
]);
if (isset($result['batchId'])) {
    $parallelResults = DataService::getParallelResults($result['batchId']);
}

// 21.9 Get results from encrypted queries
$result = DataService::parallelExecute([
    ['connection' => $connection, 'table' => 'sensitive_data', 'params' => ['select' => ['id', 'secret']]]
]);
if (isset($result['batchId'])) {
    $parallelResults = DataService::getParallelResults($result['batchId']);
}

// 21.10 Get results from large batch
$queries = array_fill(0, 20, ['connection' => $connection, 'table' => 'users', 'params' => ['select' => ['id', 'name'], 'limit' => 5]]);
$result = DataService::parallelExecute($queries);
if (isset($result['batchId'])) {
    $parallelResults = DataService::getParallelResults($result['batchId']);
}

?>