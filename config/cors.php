<?php

return [

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [env('FRONTEND_URL'), 'http://localhost:5173', 'http://localhost:3000/', 'https://gccc-ib-frontend-app.vercel.app', 'https://gcccibadan.org', 'https://www.gcccibadan.org'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
