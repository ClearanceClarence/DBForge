<?php
/**
 * ╔═══════════════════════════════════════════════════╗
 * ║  DBForge — Database Management Tool               ║
 * ║  A lightweight PhpMyAdmin alternative              ║
 * ╚═══════════════════════════════════════════════════╝
 * 
 * Drop this folder into your XAMPP htdocs directory
 * and navigate to http://localhost/dbforge/
 */

// ── Error Handling ─────────────────────────────────────
error_reporting(E_ALL);
ini_set('display_errors', '0');

set_exception_handler(function (Throwable $e) {
    http_response_code(500);
    echo '<div style="font-family:monospace;background:#1a0808;color:#f06060;padding:20px;margin:20px;border-radius:8px;border:1px solid #3a1a1a;">';
    echo '<h2 style="margin:0 0 10px;">DBForge — Fatal Error</h2>';
    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<pre style="color:#888;font-size:12px;margin-top:10px;">' . htmlspecialchars($e->getFile()) . ':' . $e->getLine() . '</pre>';
    echo '</div>';
    exit;
});

// ── Bootstrap ──────────────────────────────────────────
$config = require __DIR__ . '/config.php';
require __DIR__ . '/includes/Database.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/icons.php';

// ── Theme System ───────────────────────────────────────
$themeData = dbforge_load_themes(__DIR__ . '/themes', $config['app']['default_theme']);
$themes      = $themeData['list'];
$activeTheme = $themeData['active'];

// ── Database Connection ────────────────────────────────
$dbInstance = Database::getInstance($config['db']);

try {
    $dbInstance->connect();
    $databases = $dbInstance->getDatabases();
    $serverVersion = $dbInstance->getPdo()->getAttribute(PDO::ATTR_SERVER_VERSION);
    $connected = true;
} catch (PDOException $e) {
    $connected = false;
    $databases = [];
    $serverVersion = '?';
    $connectionError = $e->getMessage();
}

// ── Routing ────────────────────────────────────────────
$currentDb    = input('db');
$currentTable = input('table');
$activeTab    = input('tab', 'browse');
$action       = input('action');

// App info for templates
$appName    = $config['app']['name'];
$appVersion = $config['app']['version'];
$serverHost = $config['db']['host'];

// ── Handle Actions (exports, drop, truncate) ──────────
if ($action && $connected) {
    switch ($action) {
        case 'export_sql':
            if ($currentDb && $currentTable) {
                $sql = $dbInstance->exportTable($currentDb, $currentTable);
                header('Content-Type: application/sql');
                header("Content-Disposition: attachment; filename=\"{$currentTable}.sql\"");
                echo $sql;
                exit;
            }
            break;

        case 'export_csv':
            if ($currentDb && $currentTable) {
                $csv = $dbInstance->exportTableCsv($currentDb, $currentTable);
                header('Content-Type: text/csv');
                header("Content-Disposition: attachment; filename=\"{$currentTable}.csv\"");
                echo $csv;
                exit;
            }
            break;

        case 'export_db':
            if ($currentDb) {
                header('Content-Type: application/sql');
                header("Content-Disposition: attachment; filename=\"{$currentDb}.sql\"");
                $tables = $dbInstance->getTables($currentDb);
                echo "-- DBForge Full Database Export\n";
                echo "-- Database: {$currentDb}\n";
                echo "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
                echo "CREATE DATABASE IF NOT EXISTS `{$currentDb}`;\nUSE `{$currentDb}`;\n\n";
                foreach ($tables as $tbl) {
                    echo $dbInstance->exportTable($currentDb, $tbl['Name']);
                    echo "\n\n";
                }
                exit;
            }
            break;

        case 'truncate':
            if ($currentDb && $currentTable) {
                try {
                    $dbInstance->truncateTable($currentDb, $currentTable);
                    header("Location: ?db=" . urlencode($currentDb) . "&table=" . urlencode($currentTable) . "&tab=browse&msg=truncated");
                    exit;
                } catch (Exception $e) {
                    $actionError = $e->getMessage();
                }
            }
            break;

        case 'drop':
            if ($currentDb && $currentTable) {
                try {
                    $dbInstance->dropTable($currentDb, $currentTable);
                    header("Location: ?db=" . urlencode($currentDb) . "&tab=browse&msg=dropped");
                    exit;
                } catch (Exception $e) {
                    $actionError = $e->getMessage();
                }
            }
            break;
    }
}

// ── Determine Content Template ─────────────────────────
if (!$connected) {
    $contentTemplate = __DIR__ . '/templates/connection_error.php';
} elseif ($activeTab === 'server') {
    $contentTemplate = __DIR__ . '/templates/server_info.php';
} elseif ($activeTab === 'sql') {
    $contentTemplate = __DIR__ . '/templates/sql.php';
} elseif ($activeTab === 'structure' && $currentTable) {
    $contentTemplate = __DIR__ . '/templates/structure.php';
} elseif ($activeTab === 'info' && $currentTable) {
    $contentTemplate = __DIR__ . '/templates/info.php';
} elseif ($activeTab === 'export' && $currentTable) {
    $contentTemplate = __DIR__ . '/templates/export.php';
} else {
    $contentTemplate = __DIR__ . '/templates/browse.php';
}

// ── Render Layout ──────────────────────────────────────
include __DIR__ . '/templates/layout.php';
