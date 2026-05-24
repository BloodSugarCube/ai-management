<?php

return [
    'username' => env('ADMIN_USERNAME', 'admin'),
    'password' => env('ADMIN_PASSWORD'),
    'password_hash' => env('ADMIN_PASSWORD_HASH'),
    'session_key' => 'admin_authenticated',
];
