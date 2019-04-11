<?php

return [
    'default' => 'mongodb',

    'connections' => [
        'mongodb' => [
            'driver' => 'mongodb',
            'host' => env('DB_MONGO_HOST'),
            'port' => env('DB_MONGO_PORT'),
            'database' => env('DB_MONGO_DATABASE'),
            'username' => env('DB_MONGO_USERNAME'),
            'password' => env('DB_MONGO_PASSWORD'),

            'options' => array(
                'db' => 'admin'
            )
        ]
    ]
];
