<?php

// Sample examples for Helper::modifyJson function
// All examples assume the Helper class is included and namespaced correctly
use App\Http\Helpers\Helper;

// Single Object JSON
$singleJson = '{
    "bank_name": "SBI",
    "account_number": "1234567890",
    "ifsc_code": "SBIN0001234",
    "branch": "Main Branch",
    "details": {"city": "Mumbai", "type": "Savings"},
    "modified": []
}';

// Array of Objects JSON
$arrayJson = '[
    {"bank_name": "SBI", "account_number": "1234567890", "ifsc_code": "SBIN0001234", "branch": "Main Branch", "details": {"city": "Mumbai", "type": "Savings"}, "modified": []},
    {"bank_name": "HDFC", "account_number": "9876543210", "ifsc_code": "HDFC0005678", "branch": "City Branch", "details": {"city": "Delhi", "type": "Current"}, "modified": []},
    {"bank_name": "ICICI", "account_number": "4567891234", "ifsc_code": "ICIC0009012", "branch": "Downtown", "details": {"city": "Bangalore", "type": "Savings"}, "modified": []}
]';

// Array with Modified History JSON
$arrayWithModified = '[
    {
        "bank_name": "SBI",
        "account_number": "1234567890",
        "ifsc_code": "SBIN0001234",
        "branch": "Main Branch",
        "modified": [
            {"1": {"old_key": "name", "new_key": "bank_name", "value": "SBI"}}
        ]
    },
    {
        "bank_name": "HDFC",
        "account_number": "9876543210",
        "ifsc_code": "HDFC0005678",
        "branch": "City Branch",
        "modified": [
            {"1": {"old_key": "name", "new_key": "bank_name", "value": "HDFC"}}
        ]
    }
]';

/* 1. add Operation: Adds key-value pairs only if the key does not exist */

// Single Object
$result = Helper::modifyJson($singleJson, ['country' => 'India', 'details.balance' => 50000], 'add');
echo $result;
// Output: {
//     "bank_name": "SBI",
//     "account_number": "1234567890",
//     "ifsc_code": "SBIN0001234",
//     "branch": "Main Branch",
//     "details": {"city": "Mumbai", "type": "Savings", "balance": 50000},
//     "modified": [],
//     "country": "India"
// }

// Array (With Identifier)
$result = Helper::modifyJson($arrayJson, ['country' => 'India', 'details.balance' => 50000], 'add', 'account_number', '1234567890');
echo $result;
// Output: [
//     {
//         "bank_name": "SBI",
//         "account_number": "1234567890",
//         "ifsc_code": "SBIN0001234",
//         "branch": "Main Branch",
//         "details": {"city": "Mumbai", "type": "Savings", "balance": 50000},
//         "modified": [],
//         "country": "India"
//     },
//     {
//         "bank_name": "HDFC",
//         "account_number": "9876543210",
//         "ifsc_code": "HDFC0005678",
//         "branch": "City Branch",
//         "details": {"city": "Delhi", "type": "Current"},
//         "modified": []
//     },
//     {
//         "bank_name": "ICICI",
//         "account_number": "4567891234",
//         "ifsc_code": "ICIC0009012",
//         "branch": "Downtown",
//         "details": {"city": "Bangalore", "type": "Savings"},
//         "modified": []
//     }
// ]

// Array (Without Identifier)
$result = Helper::modifyJson($arrayJson, ['country' => 'India', 'details.balance' => 50000], 'add');
echo $result;
// Output: [
//     {
//         "bank_name": "SBI",
//         "account_number": "1234567890",
//         "ifsc_code": "SBIN0001234",
//         "branch": "Main Branch",
//         "details": {"city": "Mumbai", "type": "Savings", "balance": 50000},
//         "modified": [],
//         "country": "India"
//     },
//     {
//         "bank_name": "HDFC",
//         "account_number": "9876543210",
//         "ifsc_code": "HDFC0005678",
//         "branch": "City Branch",
//         "details": {"city": "Delhi", "type": "Current", "balance": 50000},
//         "modified": [],
//         "country": "India"
//     },
//     {
//         "bank_name": "ICICI",
//         "account_number": "4567891234",
//         "ifsc_code": "ICIC0009012",
//         "branch": "Downtown",
//         "details": {"city": "Bangalore", "type": "Savings", "balance": 50000},
//         "modified": [],
//         "country": "India"
//     }
// ]

/* 2. update Operation: Updates or adds key-value pairs, overwriting existing keys */

// Single Object
$result = Helper::modifyJson($singleJson, ['branch' => 'New Branch', 'details.city' => 'Pune'], 'update');
echo $result;
// Output: {
//     "bank_name": "SBI",
//     "account_number": "1234567890",
//     "ifsc_code": "SBIN0001234",
//     "branch": "New Branch",
//     "details": {"city": "Pune", "type": "Savings"},
//     "modified": []
// }

// Array (With Identifier)
$result = Helper::modifyJson($arrayJson, ['branch' => 'New Branch', 'details.city' => 'Pune'], 'update', 'account_number', '1234567890');
echo $result;
// Output: [
//     {
//         "bank_name": "SBI",
//         "account_number": "1234567890",
//         "ifsc_code": "SBIN0001234",
//         "branch": "New Branch",
//         "details": {"city": "Pune", "type": "Savings"},
//         "modified": []
//     },
//     {
//         "bank_name": "HDFC",
//         "account_number": "9876543210",
//         "ifsc_code": "HDFC0005678",
//         "branch": "City Branch",
//         "details": {"city": "Delhi", "type": "Current"},
//         "modified": []
//     },
//     {
//         "bank_name": "ICICI",
//         "account_number": "4567891234",
//         "ifsc_code": "ICIC0009012",
//         "branch": "Downtown",
//         "details": {"city": "Bangalore", "type": "Savings"},
//         "modified": []
//     }
// ]

// Array (Without Identifier)
$result = Helper::modifyJson($arrayJson, ['branch' => 'New Branch', 'details.city' => 'Pune'], 'update');
echo $result;
// Output: [
//     {
//         "bank_name": "SBI",
//         "account_number": "1234567890",
//         "ifsc_code": "SBIN0001234",
//         "branch": "New Branch",
//         "details": {"city": "Pune", "type": "Savings"},
//         "modified": []
//     },
//     {
//         "bank_name": "HDFC",
//         "account_number": "9876543210",
//         "ifsc_code": "HDFC0005678",
//         "branch": "New Branch",
//         "details": {"city": "Pune", "type": "Current"},
//         "modified": []
//     },
//     {
//         "bank_name": "ICICI",
//         "account_number": "4567891234",
//         "ifsc_code": "ICIC0009012",
//         "branch": "New Branch",
//         "details": {"city": "Pune", "type": "Savings"},
//         "modified": []
//     }
// ]

/* 3. value Operation: Updates values only for existing keys */

// Single Object
$result = Helper::modifyJson($singleJson, ['branch' => 'Updated Branch', 'details.city' => 'Chennai'], 'value');
echo $result;
// Output: {
//     "bank_name": "SBI",
//     "account_number": "1234567890",
//     "ifsc_code": "SBIN0001234",
//     "branch": "Updated Branch",
//     "details": {"city": "Chennai", "type": "Savings"},
//     "modified": []
// }

// Array (With Identifier)
$result = Helper::modifyJson($arrayJson, ['branch' => 'Updated Branch', 'details.city' => 'Chennai'], 'value', 'account_number', '1234567890');
echo $result;
// Output: [
//     {
//         "bank_name": "SBI",
//         "account_number": "1234567890",
//         "ifsc_code": "SBIN0001234",
//         "branch": "Updated Branch",
//         "details": {"city": "Chennai", "type": "Savings"},
//         "modified": []
//     },
//     {
//         "bank_name": "HDFC",
//         "account_number": "9876543210",
//         "ifsc_code": "HDFC0005678",
//         "branch": "City Branch",
//         "details": {"city": "Delhi", "type": "Current"},
//         "modified": []
//     },
//     {
//         "bank_name": "ICICI",
//         "account_number": "4567891234",
//         "ifsc_code": "ICIC0009012",
//         "branch": "Downtown",
//         "details": {"city": "Bangalore", "type": "Savings"},
//         "modified": []
//     }
// ]

// Array (Without Identifier)
$result = Helper::modifyJson($arrayJson, ['branch' => 'Updated Branch', 'details.city' => 'Chennai'], 'value');
echo $result;
// Output: [
//     {
//         "bank_name": "SBI",
//         "account_number": "1234567890",
//         "ifsc_code": "SBIN0001234",
//         "branch": "Updated Branch",
//         "details": {"city": "Chennai", "type": "Savings"},
//         "modified": []
//     },
//     {
//         "bank_name": "HDFC",
//         "account_number": "9876543210",
//         "ifsc_code": "HDFC0005678",
//         "branch": "Updated Branch",
//         "details": {"city": "Chennai", "type": "Current"},
//         "modified": []
//     },
//     {
//         "bank_name": "ICICI",
//         "account_number": "4567891234",
//         "ifsc_code": "ICIC0009012",
//         "branch": "Updated Branch",
//         "details": {"city": "Chennai", "type": "Savings"},
//         "modified": []
//     }
// ]

/* 4. rename_keys Operation: Renames keys, logging changes in 'modified' array */

// Single Object
$result = Helper::modifyJson($singleJson, ['bank_name' => 'bank'], 'rename_keys');
echo $result;
// Output: {
//     "bank": "SBI",
//     "account_number": "1234567890",
//     "ifsc_code": "SBIN0001234",
//     "branch": "Main Branch",
//     "details": {"city": "Mumbai", "type": "Savings"},
//     "modified": [{"1": {"old_key": "bank_name", "new_key": "bank", "value": "SBI"}}]
// }

// Array (With Identifier)
$result = Helper::modifyJson($arrayJson, ['bank_name' => 'bank'], 'rename_keys', 'account_number', '1234567890');
echo $result;
// Output: [
//     {
//         "bank": "SBI",
//         "account_number": "1234567890",
//         "ifsc_code": "SBIN0001234",
//         "branch": "Main Branch",
//         "details": {"city": "Mumbai", "type": "Savings"},
//         "modified": [{"1": {"old_key": "bank_name", "new_key": "bank", "value": "SBI"}}]
//     },
//     {
//         "bank_name": "HDFC",
//         "account_number": "9876543210",
//         "ifsc_code": "HDFC0005678",
//         "branch": "City Branch",
//         "details": {"city": "Delhi", "type": "Current"},
//         "modified": []
//     },
//     {
//         "bank_name": "ICICI",
//         "account_number": "4567891234",
//         "ifsc_code": "ICIC0009012",
//         "branch": "Downtown",
//         "details": {"city": "Bangalore", "type": "Savings"},
//         "modified": []
//     }
// ]

// Array (Without Identifier)
$result = Helper::modifyJson($arrayJson, ['bank_name' => 'bank'], 'rename_keys');
echo $result;
// Output: [
//     {
//         "bank": "SBI",
//         "account_number": "1234567890",
//         "ifsc_code": "SBIN0001234",
//         "branch": "Main Branch",
//         "details": {"city": "Mumbai", "type": "Savings"},
//         "modified": [{"1": {"old_key": "bank_name", "new_key": "bank", "value": "SBI"}}]
//     },
//     {
//         "bank": "HDFC",
//         "account_number": "9876543210",
//         "ifsc_code": "HDFC0005678",
//         "branch": "City Branch",
//         "details": {"city": "Delhi", "type": "Current"},
//         "modified": [{"1": {"old_key": "bank_name", "new_key": "bank", "value": "HDFC"}}]
//     },
//     {
//         "bank": "ICICI",
//         "account_number": "4567891234",
//         "ifsc_code": "ICIC0009012",
//         "branch": "Downtown",
//         "details": {"city": "Bangalore", "type": "Savings"},
//         "modified": [{"1": {"old_key": "bank_name", "new_key": "bank", "value": "ICICI"}}]
//     }
// ]

/* 5. rename_key Operation: Renames keys without modifying 'modified' array if new key exists */

// Single Object
$result = Helper::modifyJson($singleJson, ['bank_name' => 'bank'], 'rename_key');
echo $result;
// Output: {
//     "bank": "SBI",
//     "account_number": "1234567890",
//     "ifsc_code": "SBIN0001234",
//     "branch": "Main Branch",
//     "details": {"city": "Mumbai", "type": "Savings"},
//     "modified": [{"1": {"old_key": "bank_name", "new_key": "bank", "value": "SBI"}}]
// }

// Array (With Identifier)
$result = Helper::modifyJson($arrayJson, ['bank_name' => 'bank'], 'rename_key', 'account_number', '1234567890');
echo $result;
// Output: [
//     {
//         "bank": "SBI",
//         "account_number": "1234567890",
//         "ifsc_code": "SBIN0001234",
//         "branch": "Main Branch",
//         "details": {"city": "Mumbai", "type": "Savings"},
//         "modified": [{"1": {"old_key": "bank_name", "new_key": "bank", "value": "SBI"}}]
//     },
//     {
//         "bank_name": "HDFC",
//         "account_number": "9876543210",
//         "ifsc_code": "HDFC0005678",
//         "branch": "City Branch",
//         "details": {"city": "Delhi", "type": "Current"},
//         "modified": []
//     },
//     {
//         "bank_name": "ICICI",
//         "account_number": "4567891234",
//         "ifsc_code": "ICIC0009012",
//         "branch": "Downtown",
//         "details": {"city": "Bangalore", "type": "Savings"},
//         "modified": []
//     }
// ]

// Array (Without Identifier)
$result = Helper::modifyJson($arrayJson, ['bank_name' => 'bank'], 'rename_key');
echo $result;
// Output: [
//     {
//         "bank": "SBI",
//         "account_number": "1234567890",
//         "ifsc_code": "SBIN0001234",
//         "branch": "Main Branch",
//         "details": {"city": "Mumbai", "type": "Savings"},
//         "modified": [{"1": {"old_key": "bank_name", "new_key": "bank", "value": "SBI"}}]
//     },
//     {
//         "bank": "HDFC",
//         "account_number": "9876543210",
//         "ifsc_code": "HDFC0005678",
//         "branch": "City Branch",
//         "details": {"city": "Delhi", "type": "Current"},
//         "modified": [{"1": {"old_key": "bank_name", "new_key": "bank", "value": "HDFC"}}]
//     },
//     {
//         "bank": "ICICI",
//         "account_number": "4567891234",
//         "ifsc_code": "ICIC0009012",
//         "branch": "Downtown",
//         "details": {"city": "Bangalore", "type": "Savings"},
//         "modified": [{"1": {"old_key": "bank_name", "new_key": "bank", "value": "ICICI"}}]
//     }
// ]

/* 6. rename_key_changes Operation: Renames keys, storing history in 'modified' array on conflict */

// Single Object (Conflict Case)
$result = Helper::modifyJson($singleJson, ['bank_name' => 'branch'], 'rename_key_changes');
echo $result;
// Output: {
//     "account_number": "1234567890",
//     "ifsc_code": "SBIN0001234",
//     "branch": "Main Branch",
//     "details": {"city": "Mumbai", "type": "Savings"},
//     "modified": [{"1": {"old_key": "bank_name", "new_key": "branch", "value": "SBI"}}]
// }

// Array (With Identifier, Conflict Case)
$result = Helper::modifyJson($arrayJson, ['bank_name' => 'branch'], 'rename_key_changes', 'account_number', '1234567890');
echo $result;
// Output: [
//     {
//         "account_number": "1234567890",
//         "ifsc_code": "SBIN0001234",
//         "branch": "Main Branch",
//         "details": {"city": "Mumbai", "type": "Savings"},
//         "modified": [{"1": {"old_key": "bank_name", "new_key": "branch", "value": "SBI"}}]
//     },
//     {
//         "bank_name": "HDFC",
//         "account_number": "9876543210",
//         "ifsc_code": "HDFC0005678",
//         "branch": "City Branch",
//         "details": {"city": "Delhi", "type": "Current"},
//         "modified": []
//     },
//     {
//         "bank_name": "ICICI",
//         "account_number": "4567891234",
//         "ifsc_code": "ICIC0009012",
//         "branch": "Downtown",
//         "details": {"city": "Bangalore", "type": "Savings"},
//         "modified": []
//     }
// ]

// Array (Without Identifier)
$result = Helper::modifyJson($arrayJson, ['bank_name' => 'bank'], 'rename_key_changes');
echo $result;
// Output: [
//     {
//         "bank": "SBI",
//         "account_number": "1234567890",
//         "ifsc_code": "SBIN0001234",
//         "branch": "Main Branch",
//         "details": {"city": "Mumbai", "type": "Savings"},
//         "modified": [{"1": {"old_key": "bank_name", "new_key": "bank", "value": "SBI"}}]
//     },
//     {
//         "bank": "HDFC",
//         "account_number": "9876543210",
//         "ifsc_code": "HDFC0005678",
//         "branch": "City Branch",
//         "details": {"city": "Delhi", "type": "Current"},
//         "modified": [{"1": {"old_key": "bank_name", "new_key": "bank", "value": "HDFC"}}]
//     },
//     {
//         "bank": "ICICI",
//         "account_number": "4567891234",
//         "ifsc_code": "ICIC0009012",
//         "branch": "Downtown",
//         "details": {"city": "Bangalore", "type": "Savings"},
//         "modified": [{"1": {"old_key": "bank_name", "new_key": "bank", "value": "ICICI"}}]
//     }
// ]

/* 7. sort Operation: Sorts keys in the order specified in $changes */

// Single Object
$result = Helper::modifyJson($singleJson, ['account_number', 'bank_name'], 'sort');
echo $result;
// Output: {
//     "account_number": "1234567890",
//     "bank_name": "SBI",
//     "ifsc_code": "SBIN0001234",
//     "branch": "Main Branch",
//     "details": {"city": "Mumbai", "type": "Savings"},
//     "modified": []
// }

// Array (With Identifier)
$result = Helper::modifyJson($arrayJson, ['account_number', 'bank_name'], 'sort', 'account_number', '1234567890');
echo $result;
// Output: [
//     {
//         "account_number": "1234567890",
//         "bank_name": "SBI",
//         "ifsc_code": "SBIN0001234",
//         "branch": "Main Branch",
//         "details": {"city": "Mumbai", "type": "Savings"},
//         "modified": []
//     },
//     {
//         "bank_name": "HDFC",
//         "account_number": "9876543210",
//         "ifsc_code": "HDFC0005678",
//         "branch": "City Branch",
//         "details": {"city": "Delhi", "type": "Current"},
//         "modified": []
//     },
//     {
//         "bank_name": "ICICI",
//         "account_number": "4567891234",
//         "ifsc_code": "ICIC0009012",
//         "branch": "Downtown",
//         "details": {"city": "Bangalore", "type": "Savings"},
//         "modified": []
//     }
// ]

// Array (Without Identifier)
$result = Helper::modifyJson($arrayJson, ['account_number', 'bank_name'], 'sort');
echo $result;
// Output: [
//     {
//         "account_number": "1234567890",
//         "bank_name": "SBI",
//         "ifsc_code": "SBIN0001234",
//         "branch": "Main Branch",
//         "details": {"city": "Mumbai", "type": "Savings"},
//         "modified": []
//     },
//     {
//         "account_number": "9876543210",
//         "bank_name": "HDFC",
//         "ifsc_code": "HDFC0005678",
//         "branch": "City Branch",
//         "details": {"city": "Delhi", "type": "Current"},
//         "modified": []
//     },
//     {
//         "account_number": "4567891234",
//         "bank_name": "ICICI",
//         "ifsc_code": "ICIC0009012",
//         "branch": "Downtown",
//         "details": {"city": "Bangalore", "type": "Savings"},
//         "modified": []
//     }
// ]

/* 8. delete Operation: Deletes specified keys */

// Single Object
$result = Helper::modifyJson($singleJson, ['branch', 'details.city'], 'delete');
echo $result;
// Output: {
//     "bank_name": "SBI",
//     "account_number": "1234567890",
//     "ifsc_code": "SBIN0001234",
//     "details": {"type": "Savings"},
//     "modified": []
// }

// Array (With Identifier)
$result = Helper::modifyJson($arrayJson, ['branch', 'details.city'], 'delete', 'account_number', '1234567890');
echo $result;
// Output: [
//     {
//         "bank_name": "SBI",
//         "account_number": "1234567890",
//         "ifsc_code": "SBIN0001234",
//         "details": {"type": "Savings"},
//         "modified": []
//     },
//     {
//         "bank_name": "HDFC",
//         "account_number": "9876543210",
//         "ifsc_code": "HDFC0005678",
//         "branch": "City Branch",
//         "details": {"city": "Delhi", "type": "Current"},
//         "modified": []
//     },
//     {
//         "bank_name": "ICICI",
//         "account_number": "4567891234",
//         "ifsc_code": "ICIC0009012",
//         "branch": "Downtown",
//         "details": {"city": "Bangalore", "type": "Savings"},
//         "modified": []
//     }
// ]

// Array (Without Identifier)
$result = Helper::modifyJson($arrayJson, ['branch', 'details.city'], 'delete');
echo $result;
// Output: [
//     {
//         "bank_name": "SBI",
//         "account_number": "1234567890",
//         "ifsc_code": "SBIN0001234",
//         "details": {"type": "Savings"},
//         "modified": []
//     },
//     {
//         "bank_name": "HDFC",
//         "account_number": "9876543210",
//         "ifsc_code": "HDFC0005678",
//         "details": {"type": "Current"},
//         "modified": []
//     },
//     {
//         "bank_name": "ICICI",
//         "account_number": "4567891234",
//         "ifsc_code": "ICIC0009012",
//         "details": {"type": "Savings"},
//         "modified": []
//     }
// ]

/* 9. replace_all Operation: Replaces entire object with new data */

// Single Object
$result = Helper::modifyJson($singleJson, ['new_key' => 'new_value'], 'replace_all');
echo $result;
// Output: {"new_key": "new_value"}

// Array (With Identifier)
$result = Helper::modifyJson($arrayJson, ['new_key' => 'new_value'], 'replace_all', 'account_number', '1234567890');
echo $result;
// Output: [
//     {"new_key": "new_value"},
//     {
//         "bank_name": "HDFC",
//         "account_number": "9876543210",
//         "ifsc_code": "HDFC0005678",
//         "branch": "City Branch",
//         "details": {"city": "Delhi", "type": "Current"},
//         "modified": []
//     },
//     {
//         "bank_name": "ICICI",
//         "account_number": "4567891234",
//         "ifsc_code": "ICIC0009012",
//         "branch": "Downtown",
//         "details": {"city": "Bangalore", "type": "Savings"},
//         "modified": []
//     }
// ]

// Array (Without Identifier)
$result = Helper::modifyJson($arrayJson, ['new_key' => 'new_value'], 'replace_all');
echo $result;
// Output: [
//     {"new_key": "new_value"},
//     {"new_key": "new_value"},
//     {"new_key": "new_value"}
// ]

/* 10. clear Operation: Clears all data in the object */

// Single Object
$result = Helper::modifyJson($singleJson, [], 'clear');
echo $result;
// Output: {}

// Array (With Identifier)
$result = Helper::modifyJson($arrayJson, [], 'clear', 'account_number', '1234567890');
echo $result;
// Output: [
//     {},
//     {
//         "bank_name": "HDFC",
//         "account_number": "9876543210",
//         "ifsc_code": "HDFC0005678",
//         "branch": "City Branch",
//         "details": {"city": "Delhi", "type": "Current"},
//         "modified": []
//     },
//     {
//         "bank_name": "ICICI",
//         "account_number": "4567891234",
//         "ifsc_code": "ICIC0009012",
//         "branch": "Downtown",
//         "details": {"city": "Bangalore", "type": "Savings"},
//         "modified": []
//     }
// ]

// Array (Without Identifier)
$result = Helper::modifyJson($arrayJson, [], 'clear');
echo $result;
// Output: [{}, {}, {}]

/* 11. delete_modified_entry Operation: Deletes specified entries from 'modified' array by serial number */

// Single Object
$result = Helper::modifyJson($arrayWithModified[0], ['1'], 'delete_modified_entry');
echo $result;
// Output: {
//     "bank_name": "SBI",
//     "account_number": "1234567890",
//     "ifsc_code": "SBIN0001234",
//     "branch": "Main Branch",
//     "modified": []
// }

// Array (With Identifier)
$result = Helper::modifyJson($arrayWithModified, ['1'], 'delete_modified_entry', 'account_number', '1234567890');
echo $result;
// Output: [
//     {
//         "bank_name": "SBI",
//         "account_number": "1234567890",
//         "ifsc_code": "SBIN0001234",
//         "branch": "Main Branch",
//         "modified": []
//     },
//     {
//         "bank_name": "HDFC",
//         "account_number": "9876543210",
//         "ifsc_code": "HDFC0005678",
//         "branch": "City Branch",
//         "modified": [
//             {"1": {"old_key": "name", "new_key": "bank_name", "value": "HDFC"}}
//         ]
//     }
// ]

// Array (Without Identifier)
$result = Helper::modifyJson($arrayWithModified, ['1'], 'delete_modified_entry');
echo $result;
// Output: [
//     {
//         "bank_name": "SBI",
//         "account_number": "1234567890",
//         "ifsc_code": "SBIN0001234",
//         "branch": "Main Branch",
//         "modified": []
//     },
//     {
//         "bank_name": "HDFC",
//         "account_number": "9876543210",
//         "ifsc_code": "HDFC0005678",
//         "branch": "City Branch",
//         "modified": []
//     }
// ]

/* 12. delete_modified Operation: Deletes the entire 'modified' array */

// Single Object
$result = Helper::modifyJson($arrayWithModified[0], [], 'delete_modified');
echo $result;
// Output: {
//     "bank_name": "SBI",
//     "account_number": "1234567890",
//     "ifsc_code": "SBIN0001234",
//     "branch": "Main Branch"
// }

// Array (With Identifier)
$result = Helper::modifyJson($arrayWithModified, [], 'delete_modified', 'account_number', '1234567890');
echo $result;
// Output: [
//     {
//         "bank_name": "SBI",
//         "account_number": "1234567890",
//         "ifsc_code": "SBIN0001234",
//         "branch": "Main Branch"
//     },
//     {
//         "bank_name": "HDFC",
//         "account_number": "9876543210",
//         "ifsc_code": "HDFC0005678",
//         "branch": "City Branch",
//         "modified": [
//             {"1": {"old_key": "name", "new_key": "bank_name", "value": "HDFC"}}
//         ]
//     }
// ]

// Array (Without Identifier)
$result = Helper::modifyJson($arrayWithModified, [], 'delete_modified');
echo $result;
// Output: [
//     {
//         "bank_name": "SBI",
//         "account_number": "1234567890",
//         "ifsc_code": "SBIN0001234",
//         "branch": "Main Branch"
//     },
//     {
//         "bank_name": "HDFC",
//         "account_number": "9876543210",
//         "ifsc_code": "HDFC0005678",
//         "branch": "City Branch"
//     }
// ]

/* 13. Invalid JSON */
$result = Helper::modifyJson('{invalid}', ['branch' => 'New Branch'], 'update');
echo $result;
// Output: {invalid}

/* 14. Invalid Operation */
$result = Helper::modifyJson($singleJson, ['branch' => 'New Branch'], 'invalid_op');
echo $result;
// Output: {
//     "bank_name": "SBI",
//     "account_number": "1234567890",
//     "ifsc_code": "SBIN0001234",
//     "branch": "Main Branch",
//     "details": {"city": "Mumbai", "type": "Savings"},
//     "modified": []
// }

?>