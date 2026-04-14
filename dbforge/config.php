<?php
/**
 * DBForge Configuration
 * 
 * Edit these settings to match your XAMPP / MySQL setup.
 * Default XAMPP credentials: root / (empty password)
 */

return [
    // ── Database Connection ────────────────────────────────
    'db' => [
        'host'     => '127.0.0.1',
        'port'     => 3306,
        'username' => 'root',
        'password' => '',
        'charset'  => 'utf8mb4',
    ],

    // ── Application Settings ───────────────────────────────
    'app' => [
        'name'          => 'DBForge',
        'version'       => '1.0.0',
        'default_theme' => 'light-clean',
        'rows_per_page' => 50,
        'max_query_history' => 50,
        'enable_export' => true,
    ],

    // ── Security ───────────────────────────────────────────
    'security' => [
        // Set to true and configure credentials if you want login
        'require_auth' => false,
        'username'     => 'admin',
        'password'     => 'admin', // Change this!
    ],
];
