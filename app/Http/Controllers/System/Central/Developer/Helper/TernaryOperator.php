<?php

require_once 'TableHelper.php'; // Assume TableHelper.php is in the same directory

use App\Http\Helpers\TableHelper;

/**
 * Sample script demonstrating all possible IF/ELSEIF/ELSE condition cases in TableHelper's custom transformations.
 */
class TableHelperCustomSample
{
    public static function run()
    {
        // Mock data simulating DataService output
        $mockData = [
            ['id' => 1, 'name' => 'John Doe', 'approval' => 1, 'navigable' => 1, 'system' => 'central', 'score' => 85, 'role' => 'admin', 'status' => 'active', 'views' => 100],
            ['id' => 2, 'name' => 'Jane Smith', 'approval' => 0, 'navigable' => 0, 'system' => 'business', 'score' => 65, 'role' => 'user', 'status' => 'inactive', 'views' => 50],
            ['id' => 3, 'name' => 'Bob Johnson', 'approval' => 1, 'navigable' => 1, 'system' => 'open', 'score' => 95, 'role' => 'manager', 'status' => 'active', 'views' => 200],
            ['id' => 4, 'name' => 'Alice Brown', 'approval' => 0, 'navigable' => 0, 'system' => 'legal', 'score' => 45, 'role' => 'guest', 'status' => 'pending', 'views' => 10],
            ['id' => 5, 'name' => 'Eve Wilson', 'approval' => null, 'navigable' => null, 'system' => 'unknown', 'score' => 0, 'role' => '', 'status' => null, 'views' => 0],
        ];

        // Column definitions
        $columns = [
            'id' => ['users.id', true],
            'name' => ['users.name', true],
            'approval' => ['users.approval', true],
            'navigable' => ['users.navigable', true],
            'system' => ['users.system', true],
            'score' => ['users.score', true],
            'role' => ['users.role', true],
            'status' => ['users.status', true],
            'views' => ['users.views', true],
        ];

        // Custom transformations covering all possible IF/ELSEIF/ELSE cases
        $custom = [
            // Case 1: Simple IF (true value only, no false value)
            [
                'type' => 'modify',
                'column' => 'approval',
                'view' => '::IF(approval = 1, <span class="badge bg-success">Approved</span>)::',
                'renderHtml' => true
            ],
            // Case 2: IF with true and false values
            [
                'type' => 'modify',
                'column' => 'navigable',
                'view' => '::IF(navigable = 1, <span class="badge bg-success">Yes</span>, <span class="badge bg-danger">No</span>)::',
                'renderHtml' => true
            ],
            // Case 3: IF/ELSEIF with multiple conditions
            [
                'type' => 'modify',
                'column' => 'system',
                'view' => '::IF(system = \'central\', <span class="badge bg-info">Central</span>)::ELSEIF(system = \'business\', <span class="badge bg-success">Business</span>)::ELSEIF(system = \'open\', <span class="badge bg-warning">Open</span>)::',
                'renderHtml' => true
            ],
            // Case 4: IF/ELSEIF/ELSE with default case
            [
                'type' => 'modify',
                'column' => 'role',
                'view' => '::IF(role = \'admin\', <span class="badge bg-primary">Admin</span>)::ELSEIF(role = \'manager\', <span class="badge bg-info">Manager</span>)::ELSE(<span class="badge bg-secondary">Other</span>)::',
                'renderHtml' => true
            ],
            // Case 5: IF with logical AND condition
            [
                'type' => 'addon',
                'column' => 'status_summary',
                'view' => '::IF(approval = 1 AND navigable = 1, <span class="badge bg-primary">Active</span>, <span class="badge bg-warning">Inactive</span>)::',
                'renderHtml' => true
            ],
            // Case 6: IF with logical OR condition
            [
                'type' => 'addon',
                'column' => 'high_score',
                'view' => '::IF(score > 80 OR role = \'admin\', <span class="badge bg-success">High</span>, <span class="badge bg-danger">Low</span>)::',
                'renderHtml' => true
            ],
            // Case 7: IF with IN condition
            [
                'type' => 'modify',
                'column' => 'score',
                'view' => '::IF(score IN [80, 85, 90, 95], <span class="badge bg-success">Passing</span>, <span class="badge bg-danger">Failing</span>)::',
                'renderHtml' => true
            ],
            // Case 8: IF with comparison operators (>, <, !=)
            [
                'type' => 'addon',
                'column' => 'view_count',
                'view' => '::IF(views > 100, <span class="badge bg-success">Popular</span>)::ELSEIF(views < 50, <span class="badge bg-danger">Low Views</span>)::ELSEIF(views != 50, <span class="badge bg-warning">Moderate</span>)::ELSE(<span class="badge bg-info">Average</span>)::',
                'renderHtml' => true
            ],
            // Case 9: IF with LIKE condition
            [
                'type' => 'modify',
                'column' => 'name',
                'view' => '::IF(name LIKE \'John%\', <span class="badge bg-primary">John</span>, <span class="badge bg-secondary">Other Name</span>)::',
                'renderHtml' => true
            ],
            // Case 10: IF with nested conditions (parenthesized)
            [
                'type' => 'addon',
                'column' => 'complex_status',
                'view' => '::IF((approval = 1 AND score > 80) OR role = \'admin\', <span class="badge bg-success">Priority</span>, <span class="badge bg-warning">Standard</span>)::',
                'renderHtml' => true
            ],
            // Case 11: IF with invalid column (to trigger warning)
            [
                'type' => 'addon',
                'column' => 'invalid_column',
                'view' => '::IF(invalid_col = 1, <span class="badge bg-danger">Invalid</span>, <span class="badge bg-secondary">Valid</span>)::',
                'renderHtml' => true
            ],
            // Case 12: IF with placeholder replacement
            [
                'type' => 'addon',
                'column' => 'name_display',
                'view' => '::IF(approval = 1, User: ::name::, Guest: ::name::)::',
                'renderHtml' => false // Escaped output
            ],
            // Case 13: IF with empty condition result (null value handling)
            [
                'type' => 'modify',
                'column' => 'status',
                'view' => '::IF(status = \'active\', <span class="badge bg-success">Active</span>)::ELSEIF(status = \'inactive\', <span class="badge bg-warning">Inactive</span>)::ELSE(<span class="badge bg-secondary">Unknown</span>)::',
                'renderHtml' => true
            ],
            // Case 14: IF with multiple conditions and placeholders
            [
                'type' => 'addon',
                'column' => 'detailed_summary',
                'view' => '::IF(score > 90 AND role = \'manager\', <span class="badge bg-primary">Top Manager: ::name::</span>)::ELSEIF(score > 80, <span class="badge bg-success">High Score: ::name::</span>)::ELSE(<span class="badge bg-info">Standard: ::name::</span>)::',
                'renderHtml' => true
            ],
        ];

        // Request settings (minimal, as only processData is used)
        $reqSet = [
            'table' => 'users',
            'token' => 'abc_def_ghi_jkl',
            'act' => 'id',
            'actions' => 'c' // Include checkboxes for demonstration
        ];

        // Process data with custom transformations
        echo "=== Demonstrating All Possible Custom IF/ELSEIF/ELSE Cases ===\n";
        $processedData = TableHelper::processData($mockData, $columns, $custom, $reqSet);
        echo json_encode($processedData, JSON_PRETTY_PRINT) . "\n";
    }
}

// Run the sample
TableHelperCustomSample::run();

?>