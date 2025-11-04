<?php

return [
    'defaults' => [
        'page' => 'A4',
        'orientation' => 'P', // P or L
        'margins' => [
            'top' => 15,
            'right' => 12,
            'bottom' => 15,
            'left' => 12,
            'header' => 8,
            'footer' => 8,
        ],
        'font' => [
            'family' => 'dejavusans',
            'size_body' => 9,
            'size_title' => 12,
        ],
        'header' => [
            'enabled' => true,
            'title' => 'Leave Reports',
            'logo_url' => null, // override to null from code to omit entirely
            'date_format' => 'Y-m-d',
            'border' => 0, // 0 none, 1 box line
        ],
        'footer' => [
            'enabled' => true,
            'company' => env('APP_NAME', 'Company'),
            'show_page_x_of_y' => true,
            'show_generated_by' => true,
            'border' => 0,
        ],
        'page_border' => 'box', // none|box|top|bottom
    ],
];


