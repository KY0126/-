<?php
// api/config.php
return [
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'dbname' => 'workshop',
        'user' => 'root',
        'pass' => 'password',
        'charset' => 'utf8mb4',
    ],
    'timezone' => 'Asia/Taipei',
    'mail' => [
        'enabled' => true,
        'smtp' => true,
        'host' => 'smtp.example.com',
        'port' => 587,
        'username' => 'smtp-user',
        'password' => 'smtp-pass',
        'secure' => 'tls',
        'from_email' => 'noreply@example.com',
        'from_name' => '研習活動通知'
    ],
];