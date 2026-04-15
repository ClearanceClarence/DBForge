<?php
/**
 * DBForge — AJAX Endpoint
 * Secured with auth, CSRF, read-only mode, and query logging.
 */

header('Content-Type: application/json');

if (!file_exists(__DIR__ . '/config.php')) {
    echo json_encode(['error' => 'Not installed. Please run the installer.']);
    exit;
}

$config = require __DIR__ . '/config.php';
require __DIR__ . '/includes/Database.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/icons.php';
require __DIR__ . '/includes/Auth.php';

// ── Security checks ────────────────────────────────────
$auth = new Auth($config['security']);
$auth->startSession();

// Check auth
if ($auth->isAuthRequired() && !$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated. Please log in.']);
    exit;
}

// Check IP whitelist
if (!$auth->isIpAllowed()) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied.']);
    exit;
}

// CSRF check on POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($auth->csrfEnabled() && !$auth->validateCsrf()) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid security token. Please reload the page.']);
        exit;
    }
}

// ── Database connection ────────────────────────────────
$action = $_REQUEST['action'] ?? '';
$db = $_REQUEST['db'] ?? '';

// Block hidden databases
if ($db && $auth->isDatabaseHidden($db)) {
    echo json_encode(['error' => 'Access denied.']);
    exit;
}

try {
    $dbInstance = Database::getInstance($config['db']);
    $dbInstance->connect();
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

// ── Route actions ──────────────────────────────────────
switch ($action) {

    // ── Autocomplete data (GET, read-only safe) ──
    case 'autocomplete':
        $result = [
            'databases' => [],
            'tables'    => [],
            'columns'   => [],
        ];

        $result['databases'] = $auth->filterDatabases($dbInstance->getDatabases());

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
                    } catch (Exception $e) {}
                }
            } catch (Exception $e) {}
        }

        echo json_encode($result);
        break;

    // ── Update a single cell (POST, write) ──
    case 'update_cell':
        if ($auth->isReadOnly()) {
            echo json_encode(['error' => 'Write operations are disabled in read-only mode.']);
            break;
        }

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

            $start = microtime(true);

            if ($isNull) {
                $sql = "UPDATE {$tblSafe} SET {$colSafe} = NULL WHERE {$pkSafe} = :pk LIMIT 1";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['pk' => $pkVal]);
            } else {
                $sql = "UPDATE {$tblSafe} SET {$colSafe} = :val WHERE {$pkSafe} = :pk LIMIT 1";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['val' => $value, 'pk' => $pkVal]);
            }

            $elapsed = microtime(true) - $start;
            $auth->logQuery($db, "UPDATE {$table} SET {$column} = " . ($isNull ? 'NULL' : "'{$value}'") . " WHERE {$pkCol} = {$pkVal}", $elapsed);

            echo json_encode([
                'success'  => true,
                'affected' => $stmt->rowCount(),
            ]);
        } catch (PDOException $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    // ── Delete a row (POST, write) ──
    case 'delete_row':
        if ($auth->isReadOnly()) {
            echo json_encode(['error' => 'Write operations are disabled in read-only mode.']);
            break;
        }

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

            $start = microtime(true);
            $stmt = $pdo->prepare("DELETE FROM {$tblSafe} WHERE {$pkSafe} = :pk LIMIT 1");
            $stmt->execute(['pk' => $pkVal]);
            $elapsed = microtime(true) - $start;

            $auth->logQuery($db, "DELETE FROM {$table} WHERE {$pkCol} = {$pkVal}", $elapsed);

            echo json_encode(['success' => true, 'affected' => $stmt->rowCount()]);
        } catch (PDOException $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    // ── Delete multiple rows (POST, write) ──
    case 'bulk_delete':
        if ($auth->isReadOnly()) {
            echo json_encode(['error' => 'Write operations are disabled in read-only mode.']);
            break;
        }

        $table = $_POST['table'] ?? '';
        $pkCol = $_POST['pk_col'] ?? '';
        $pkVals = json_decode($_POST['pk_vals'] ?? '[]', true);

        if (!$db || !$table || !$pkCol || !is_array($pkVals) || empty($pkVals)) {
            echo json_encode(['error' => 'Missing required fields']);
            break;
        }

        try {
            $pdo = $dbInstance->connect($db);
            $tblSafe = '`' . str_replace('`', '``', $table) . '`';
            $pkSafe  = '`' . str_replace('`', '``', $pkCol) . '`';

            $placeholders = implode(',', array_fill(0, count($pkVals), '?'));
            $start = microtime(true);
            $stmt = $pdo->prepare("DELETE FROM {$tblSafe} WHERE {$pkSafe} IN ({$placeholders})");
            $stmt->execute(array_values($pkVals));
            $elapsed = microtime(true) - $start;

            $auth->logQuery($db, "DELETE FROM {$table} WHERE {$pkCol} IN (" . implode(',', $pkVals) . ")", $elapsed);

            echo json_encode(['success' => true, 'affected' => $stmt->rowCount()]);
        } catch (PDOException $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    // ── Alter a column (POST, write) ──
    case 'alter_column':
        if ($auth->isReadOnly()) {
            echo json_encode(['error' => 'Write operations are disabled in read-only mode.']);
            break;
        }

        $table    = $_POST['table'] ?? '';
        $origName = $_POST['original_name'] ?? '';
        $newName  = $_POST['new_name'] ?? '';
        $newType  = $_POST['new_type'] ?? '';
        $nullable = ($_POST['nullable'] ?? '0') === '1';
        $defNull  = ($_POST['default_null'] ?? '0') === '1';
        $defVal   = $_POST['default_value'] ?? '';
        $extra    = $_POST['extra'] ?? '';
        $comment  = $_POST['comment'] ?? '';

        if (!$db || !$table || !$origName || !$newName || !$newType) {
            echo json_encode(['error' => 'Missing required fields']);
            break;
        }

        try {
            $pdo = $dbInstance->connect($db);
            $esc = function($s) { return '`' . str_replace('`', '``', $s) . '`'; };

            $sql = "ALTER TABLE {$esc($table)} CHANGE {$esc($origName)} {$esc($newName)} {$newType}";
            $sql .= $nullable ? ' NULL' : ' NOT NULL';
            if ($defNull) {
                $sql .= ' DEFAULT NULL';
            } elseif ($defVal !== '') {
                if (strtoupper($defVal) === 'CURRENT_TIMESTAMP') {
                    $sql .= ' DEFAULT CURRENT_TIMESTAMP';
                } else {
                    $sql .= ' DEFAULT ' . $pdo->quote($defVal);
                }
            }
            if ($extra) {
                $sql .= ' ' . $extra;
            }
            if ($comment !== '') {
                $sql .= ' COMMENT ' . $pdo->quote($comment);
            }

            $start = microtime(true);
            $pdo->exec($sql);
            $elapsed = microtime(true) - $start;
            $auth->logQuery($db, $sql, $elapsed);

            echo json_encode(['success' => true, 'sql' => $sql]);
        } catch (PDOException $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    // ── Drop a column (POST, write) ──
    case 'drop_column':
        if ($auth->isReadOnly()) {
            echo json_encode(['error' => 'Write operations are disabled in read-only mode.']);
            break;
        }

        $table  = $_POST['table'] ?? '';
        $column = $_POST['column'] ?? '';

        if (!$db || !$table || !$column) {
            echo json_encode(['error' => 'Missing required fields']);
            break;
        }

        try {
            $pdo = $dbInstance->connect($db);
            $esc = function($s) { return '`' . str_replace('`', '``', $s) . '`'; };
            $sql = "ALTER TABLE {$esc($table)} DROP COLUMN {$esc($column)}";

            $start = microtime(true);
            $pdo->exec($sql);
            $elapsed = microtime(true) - $start;
            $auth->logQuery($db, $sql, $elapsed);

            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    // ── Add a column (POST, write) ──
    case 'add_column':
        if ($auth->isReadOnly()) {
            echo json_encode(['error' => 'Write operations are disabled in read-only mode.']);
            break;
        }

        $table    = $_POST['table'] ?? '';
        $name     = $_POST['name'] ?? '';
        $type     = $_POST['type'] ?? '';
        $nullable = ($_POST['nullable'] ?? '0') === '1';
        $defNull  = ($_POST['default_null'] ?? '0') === '1';
        $defVal   = $_POST['default_value'] ?? '';
        $comment  = $_POST['comment'] ?? '';
        $after    = $_POST['after'] ?? '';

        if (!$db || !$table || !$name || !$type) {
            echo json_encode(['error' => 'Missing required fields']);
            break;
        }

        try {
            $pdo = $dbInstance->connect($db);
            $esc = function($s) { return '`' . str_replace('`', '``', $s) . '`'; };

            $sql = "ALTER TABLE {$esc($table)} ADD COLUMN {$esc($name)} {$type}";
            $sql .= $nullable ? ' NULL' : ' NOT NULL';
            if ($defNull) {
                $sql .= ' DEFAULT NULL';
            } elseif ($defVal !== '') {
                if (strtoupper($defVal) === 'CURRENT_TIMESTAMP') {
                    $sql .= ' DEFAULT CURRENT_TIMESTAMP';
                } else {
                    $sql .= ' DEFAULT ' . $pdo->quote($defVal);
                }
            }
            if ($comment !== '') {
                $sql .= ' COMMENT ' . $pdo->quote($comment);
            }
            if ($after === 'FIRST') {
                $sql .= ' FIRST';
            } elseif ($after !== '') {
                $sql .= " AFTER {$esc($after)}";
            }

            $start = microtime(true);
            $pdo->exec($sql);
            $elapsed = microtime(true) - $start;
            $auth->logQuery($db, $sql, $elapsed);

            echo json_encode(['success' => true, 'sql' => $sql]);
        } catch (PDOException $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    // ── Set AUTO_INCREMENT (POST, write) ──
    case 'set_auto_increment':
        if ($auth->isReadOnly()) {
            echo json_encode(['error' => 'Write operations are disabled in read-only mode.']);
            break;
        }

        $table = $_POST['table'] ?? '';
        $value = (int)($_POST['value'] ?? 0);

        if (!$db || !$table) {
            echo json_encode(['error' => 'Missing required fields']);
            break;
        }

        try {
            $pdo = $dbInstance->connect($db);
            $esc = function($s) { return '`' . str_replace('`', '``', $s) . '`'; };

            if ($value === 0) {
                // Reset: find max PK value + 1
                // Get the auto_increment column name
                $stmt = $pdo->query("SHOW COLUMNS FROM {$esc($table)} WHERE Extra LIKE '%auto_increment%'");
                $aiCol = $stmt->fetch();
                if (!$aiCol) {
                    echo json_encode(['error' => 'No AUTO_INCREMENT column found']);
                    break;
                }
                $colName = $aiCol['Field'];
                $maxStmt = $pdo->query("SELECT COALESCE(MAX({$esc($colName)}), 0) + 1 AS next_val FROM {$esc($table)}");
                $value = (int)$maxStmt->fetchColumn();
            }

            $sql = "ALTER TABLE {$esc($table)} AUTO_INCREMENT = {$value}";
            $start = microtime(true);
            $pdo->exec($sql);
            $elapsed = microtime(true) - $start;
            $auth->logQuery($db, $sql, $elapsed);

            echo json_encode(['success' => true, 'new_value' => $value]);
        } catch (PDOException $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    // ── ER diagram data (GET) ──
    case 'er_data':
        if (!$db) {
            echo json_encode(['error' => 'No database selected.']);
            break;
        }
        try {
            $tables = $dbInstance->getTables($db);
            $erTables = [];
            $erRelations = [];

            foreach ($tables as $t) {
                $name = $t['Name'];
                $cols = $dbInstance->getColumns($db, $name);
                $columns = [];
                foreach ($cols as $c) {
                    $columns[] = [
                        'name'  => $c['Field'],
                        'type'  => $c['Type'],
                        'key'   => $c['Key'],
                        'null'  => $c['Null'],
                        'extra' => $c['Extra'],
                    ];
                }
                $erTables[] = [
                    'name'    => $name,
                    'columns' => $columns,
                    'rows'    => (int)($t['Rows'] ?? 0),
                    'engine'  => $t['Engine'] ?? '',
                ];

                // FK relationships
                try {
                    $fks = $dbInstance->getForeignKeys($db, $name);
                    foreach ($fks as $fk) {
                        $erRelations[] = [
                            'from_table'  => $name,
                            'from_col'    => $fk['COLUMN_NAME'],
                            'to_table'    => $fk['REFERENCED_TABLE_NAME'],
                            'to_col'      => $fk['REFERENCED_COLUMN_NAME'],
                            'constraint'  => $fk['CONSTRAINT_NAME'] ?? '',
                            'on_delete'   => $fk['DELETE_RULE'] ?? '',
                            'on_update'   => $fk['UPDATE_RULE'] ?? '',
                        ];
                    }
                } catch (Exception $e) {}
            }

            echo json_encode(['tables' => $erTables, 'relations' => $erRelations]);
        } catch (PDOException $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    // ── Rename a table (POST, write) ──
    case 'rename_table':
        if ($auth->isReadOnly()) {
            echo json_encode(['error' => 'Write operations are disabled in read-only mode.']);
            break;
        }
        $oldName = trim($_POST['old_name'] ?? '');
        $newName = trim($_POST['new_name'] ?? '');
        if (!$db || !$oldName || !$newName) {
            echo json_encode(['error' => 'Database, old name, and new name are required.']);
            break;
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $newName)) {
            echo json_encode(['error' => 'Table name can only contain letters, numbers, and underscores.']);
            break;
        }
        try {
            $dbInstance->renameTable($db, $oldName, $newName);
            $auth->logActivity("Renamed table: {$db}.{$oldName} → {$newName}");
            echo json_encode(['success' => true, 'new_name' => $newName]);
        } catch (PDOException $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    // ── Copy a table (POST, write) ──
    case 'copy_table':
        if ($auth->isReadOnly()) {
            echo json_encode(['error' => 'Write operations are disabled in read-only mode.']);
            break;
        }
        $source = trim($_POST['source'] ?? '');
        $dest = trim($_POST['destination'] ?? '');
        $withData = isset($_POST['with_data']);
        if (!$db || !$source || !$dest) {
            echo json_encode(['error' => 'Database, source table, and destination name are required.']);
            break;
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $dest)) {
            echo json_encode(['error' => 'Table name can only contain letters, numbers, and underscores.']);
            break;
        }
        try {
            $dbInstance->copyTable($db, $source, $dest, $withData);
            $label = $withData ? 'with data' : 'structure only';
            $auth->logActivity("Copied table: {$db}.{$source} → {$dest} ({$label})");
            echo json_encode(['success' => true, 'destination' => $dest]);
        } catch (PDOException $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    // ── Drop a table (POST, write) ──
    case 'drop_table':
        if ($auth->isReadOnly()) {
            echo json_encode(['error' => 'Write operations are disabled in read-only mode.']);
            break;
        }
        $tableName = trim($_POST['name'] ?? '');
        if (!$db || !$tableName) {
            echo json_encode(['error' => 'Database and table name are required.']);
            break;
        }
        try {
            $dbInstance->dropTable($db, $tableName);
            $auth->logActivity("Dropped table: {$db}.{$tableName}");
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    // ── Search across tables (GET) ──
    case 'search_tables':
        $searchTerm = trim($_GET['term'] ?? '');
        if (!$db) {
            echo json_encode(['error' => 'No database selected.']);
            break;
        }
        if (strlen($searchTerm) < 1) {
            echo json_encode(['error' => 'Search term is required.']);
            break;
        }

        try {
            $maxPerTable = min(10, max(1, (int)($_GET['limit'] ?? 5)));
            $results = $dbInstance->searchAcrossTables($db, $searchTerm, $maxPerTable);
            echo json_encode($results);
        } catch (PDOException $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    // ── Clear query history (POST) ──
    case 'clear_history':
        $_SESSION['dbforge_query_history'] = [];
        echo json_encode(['success' => true]);
        break;

    // ── Delete single history item (POST) ──
    case 'delete_history_item':
        $idx = (int)($_POST['idx'] ?? -1);
        if (isset($_SESSION['dbforge_query_history'][$idx])) {
            array_splice($_SESSION['dbforge_query_history'], $idx, 1);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Invalid index.']);
        }
        break;

    // ── Insert a row (POST, write) ──
    case 'insert_row':
        if ($auth->isReadOnly()) {
            echo json_encode(['error' => 'Write operations are disabled in read-only mode.']);
            break;
        }

        $table = $_POST['table'] ?? '';
        $data = json_decode($_POST['data'] ?? '{}', true);

        if (!$db || !$table || !is_array($data) || empty($data)) {
            echo json_encode(['error' => 'Missing required fields.']);
            break;
        }

        try {
            $pdo = $dbInstance->connect($db);
            $esc = function($s) { return '`' . str_replace('`', '``', $s) . '`'; };

            $cols = [];
            $placeholders = [];
            $values = [];
            foreach ($data as $col => $val) {
                $cols[] = $esc($col);
                $placeholders[] = '?';
                $values[] = $val; // null values are handled by PDO
            }

            $sql = "INSERT INTO {$esc($table)} (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $placeholders) . ")";
            $start = microtime(true);
            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);
            $elapsed = microtime(true) - $start;

            $insertId = $pdo->lastInsertId();
            $auth->logQuery($db, "INSERT INTO {$table} (" . implode(', ', array_keys($data)) . ")", $elapsed);

            echo json_encode([
                'success'   => true,
                'insert_id' => $insertId ?: null,
            ]);
        } catch (PDOException $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    // ── Execute arbitrary SQL (POST, write) ──
    case 'execute_sql':
        if ($auth->isReadOnly()) {
            echo json_encode(['error' => 'Write operations are disabled in read-only mode.']);
            break;
        }

        $sql = trim($_POST['sql'] ?? '');
        if (!$sql) {
            echo json_encode(['error' => 'No SQL provided.']);
            break;
        }

        $targetDb = $db ?: null;

        try {
            $pdo = $dbInstance->connect($targetDb);
            $start = microtime(true);
            $affected = $pdo->exec($sql);
            $elapsed = microtime(true) - $start;
            $auth->logQuery($targetDb ?? '-', $sql, $elapsed);

            echo json_encode([
                'success'  => true,
                'affected' => $affected !== false ? $affected : 0,
                'time'     => $elapsed,
            ]);
        } catch (PDOException $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    // ── Create a database (POST, write) ──
    case 'create_database':
        if ($auth->isReadOnly()) {
            echo json_encode(['error' => 'Write operations are disabled in read-only mode.']);
            break;
        }

        $name      = trim($_POST['name'] ?? '');
        $charset   = $_POST['charset'] ?? 'utf8mb4';
        $collation = $_POST['collation'] ?? 'utf8mb4_general_ci';

        if (!$name) {
            echo json_encode(['error' => 'Database name is required.']);
            break;
        }
        if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $name)) {
            echo json_encode(['error' => 'Database name can only contain letters, numbers, underscores, and hyphens.']);
            break;
        }

        try {
            $dbInstance->createDatabase($name, $charset, $collation);
            $auth->logActivity("Created database: {$name} ({$charset}/{$collation})");
            echo json_encode(['success' => true, 'name' => $name]);
        } catch (PDOException $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    // ── Drop a database (POST, write) ──
    case 'drop_database':
        if ($auth->isReadOnly()) {
            echo json_encode(['error' => 'Write operations are disabled in read-only mode.']);
            break;
        }

        $name = trim($_POST['name'] ?? '');
        if (!$name) {
            echo json_encode(['error' => 'Database name is required.']);
            break;
        }

        // Block dropping system databases
        $protected = ['mysql', 'information_schema', 'performance_schema', 'sys'];
        if (in_array(strtolower($name), $protected)) {
            echo json_encode(['error' => 'Cannot drop system database: ' . $name]);
            break;
        }

        try {
            $dbInstance->dropDatabase($name);
            $auth->logActivity("Dropped database: {$name}");
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['error' => 'Unknown action']);
        break;
}
