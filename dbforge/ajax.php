<?php
/**
 * DBForge — AJAX Endpoint
 * Returns autocomplete data for the SQL editor
 */

header('Content-Type: application/json');

$config = require __DIR__ . '/config.php';
require __DIR__ . '/includes/Database.php';
require __DIR__ . '/includes/helpers.php';

$action = input('action');
$db = input('db');

try {
    $dbInstance = Database::getInstance($config['db']);
    $dbInstance->connect();
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

switch ($action) {

    // ── Get all autocomplete data for current database ──
    case 'autocomplete':
        $result = [
            'databases' => [],
            'tables'    => [],
            'columns'   => [],
            'keywords'  => [],
        ];

        // All database names
        $result['databases'] = $dbInstance->getDatabases();

        // Tables + columns for current database
        if ($db) {
            try {
                $tables = $dbInstance->getTables($db);
                foreach ($tables as $tbl) {
                    $tblName = $tbl['Name'];
                    $result['tables'][] = [
                        'name'   => $tblName,
                        'rows'   => (int) ($tbl['Rows'] ?? 0),
                        'engine' => $tbl['Engine'] ?? '',
                    ];

                    // Columns for each table
                    try {
                        $cols = $dbInstance->getColumns($db, $tblName);
                        foreach ($cols as $col) {
                            $result['columns'][] = [
                                'name'    => $col['Field'],
                                'table'   => $tblName,
                                'type'    => $col['Type'],
                                'key'     => $col['Key'] ?? '',
                                'null'    => $col['Null'] ?? '',
                            ];
                        }
                    } catch (Exception $e) {
                        // Skip tables we can't read columns from
                    }
                }
            } catch (Exception $e) {
                // Skip if db can't be read
            }
        }

        echo json_encode($result);
        break;

    // ── Update a single cell value ──
    case 'update_cell':
        $table  = $_POST['table'] ?? '';
        $column = $_POST['column'] ?? '';
        $value  = $_POST['value'] ?? null;
        $isNull = ($_POST['is_null'] ?? '0') === '1';
        $pkCol  = $_POST['pk_col'] ?? '';
        $pkVal  = $_POST['pk_val'] ?? '';

        if (!$db || !$table || !$column || !$pkCol) {
            echo json_encode(['error' => 'Missing required fields']);
            break;
        }

        try {
            $pdo = $dbInstance->connect($db);
            $colSafe = '`' . str_replace('`', '``', $column) . '`';
            $tblSafe = '`' . str_replace('`', '``', $table) . '`';
            $pkSafe  = '`' . str_replace('`', '``', $pkCol) . '`';

            if ($isNull) {
                $sql = "UPDATE {$tblSafe} SET {$colSafe} = NULL WHERE {$pkSafe} = :pk LIMIT 1";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['pk' => $pkVal]);
            } else {
                $sql = "UPDATE {$tblSafe} SET {$colSafe} = :val WHERE {$pkSafe} = :pk LIMIT 1";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['val' => $value, 'pk' => $pkVal]);
            }

            echo json_encode([
                'success'  => true,
                'affected' => $stmt->rowCount(),
            ]);
        } catch (PDOException $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    // ── Delete a row ──
    case 'delete_row':
        $table = $_POST['table'] ?? '';
        $pkCol = $_POST['pk_col'] ?? '';
        $pkVal = $_POST['pk_val'] ?? '';

        if (!$db || !$table || !$pkCol) {
            echo json_encode(['error' => 'Missing required fields']);
            break;
        }

        try {
            $pdo = $dbInstance->connect($db);
            $tblSafe = '`' . str_replace('`', '``', $table) . '`';
            $pkSafe  = '`' . str_replace('`', '``', $pkCol) . '`';
            $stmt = $pdo->prepare("DELETE FROM {$tblSafe} WHERE {$pkSafe} = :pk LIMIT 1");
            $stmt->execute(['pk' => $pkVal]);
            echo json_encode(['success' => true, 'affected' => $stmt->rowCount()]);
        } catch (PDOException $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['error' => 'Unknown action']);
        break;
}
