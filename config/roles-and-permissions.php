<?php

use App\Models\User;

return [
    'admin_email' => env('ADMIN_EMAIL', 'ayoubbezai05@gmail.com'),

    'guards' => [
        'web' => [
            User::ROLE_ADMIN => [
                'test-permission',
            ],
        ],

        'api' => [
            User::ROLE_ADMIN => [
                'test-permission',
            ],
        ],
    ],
];
