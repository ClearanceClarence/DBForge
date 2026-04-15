<?php
/**
 * DBForge - Database Connection Manager
 */

class Database
{
    private static ?Database $instance = null;
    private ?PDO $pdo = null;
    private array $config;

    private function __construct(array $config)
    {
        $this->config = $config;
    }

    public static function getInstance(?array $config = null): self
    {
        if (self::$instance === null) {
            if ($config === null) {
                throw new RuntimeException('Database config required on first call');
            }
            self::$instance = new self($config);
        }
        return self::$instance;
    }

    public function connect(?string $database = null): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;charset=%s',
            $this->config['host'],
            $this->config['port'],
            $this->config['charset']
        );

        if ($database) {
            $dsn .= ";dbname={$database}";
        }

        $this->pdo = new PDO($dsn, $this->config['username'], $this->config['password'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_FOUND_ROWS   => true,
        ]);

        return $this->pdo;
    }

    public function getPdo(): ?PDO
    {
        return $this->pdo;
    }

    /**
     * Get all databases the user can access
     */
    public function getDatabases(): array
    {
        $pdo = $this->connect();
        $stmt = $pdo->query('SHOW DATABASES');
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Get all tables in a database
     */
    public function getTables(string $database): array
    {
        $pdo = $this->connect($database);
        $stmt = $pdo->query('SHOW TABLE STATUS');
        return $stmt->fetchAll();
    }

    /**
     * Get exact row count for a table (InnoDB estimates are unreliable)
     */
    public function getExactRowCount(string $database, string $table): int
    {
        $pdo = $this->connect($database);
        $stmt = $pdo->query('SELECT COUNT(*) FROM `' . $this->escapeIdentifier($table) . '`');
        return (int) $stmt->fetchColumn();
    }

    /**
     * Get column info for a table
     */
    public function getColumns(string $database, string $table): array
    {
        $pdo = $this->connect($database);
        $stmt = $pdo->prepare('SHOW FULL COLUMNS FROM `' . $this->escapeIdentifier($table) . '`');
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get indexes for a table
     */
    public function getIndexes(string $database, string $table): array
    {
        $pdo = $this->connect($database);
        $stmt = $pdo->prepare('SHOW INDEX FROM `' . $this->escapeIdentifier($table) . '`');
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get foreign key relationships for a table
     */
    public function getForeignKeys(string $database, string $table): array
    {
        $pdo = $this->connect($database);
        $stmt = $pdo->prepare("
            SELECT
                kcu.CONSTRAINT_NAME,
                kcu.COLUMN_NAME,
                kcu.REFERENCED_TABLE_SCHEMA,
                kcu.REFERENCED_TABLE_NAME,
                kcu.REFERENCED_COLUMN_NAME,
                rc.UPDATE_RULE,
                rc.DELETE_RULE
            FROM information_schema.KEY_COLUMN_USAGE kcu
            JOIN information_schema.REFERENTIAL_CONSTRAINTS rc
                ON rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
                AND rc.CONSTRAINT_SCHEMA = kcu.TABLE_SCHEMA
            WHERE kcu.TABLE_SCHEMA = :db
                AND kcu.TABLE_NAME = :tbl
                AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
            ORDER BY kcu.CONSTRAINT_NAME, kcu.ORDINAL_POSITION
        ");
        $stmt->execute(['db' => $database, 'tbl' => $table]);
        return $stmt->fetchAll();
    }

    /**
     * Get tables that reference this table (reverse FK lookup)
     */
    public function getReferencedBy(string $database, string $table): array
    {
        $pdo = $this->connect($database);
        $stmt = $pdo->prepare("
            SELECT
                kcu.TABLE_NAME AS referencing_table,
                kcu.COLUMN_NAME AS referencing_column,
                kcu.REFERENCED_COLUMN_NAME AS local_column,
                kcu.CONSTRAINT_NAME,
                rc.UPDATE_RULE,
                rc.DELETE_RULE
            FROM information_schema.KEY_COLUMN_USAGE kcu
            JOIN information_schema.REFERENTIAL_CONSTRAINTS rc
                ON rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
                AND rc.CONSTRAINT_SCHEMA = kcu.TABLE_SCHEMA
            WHERE kcu.REFERENCED_TABLE_SCHEMA = :db
                AND kcu.REFERENCED_TABLE_NAME = :tbl
            ORDER BY kcu.TABLE_NAME, kcu.ORDINAL_POSITION
        ");
        $stmt->execute(['db' => $database, 'tbl' => $table]);
        return $stmt->fetchAll();
    }

    /**
     * Get create statement
     */
    public function getCreateStatement(string $database, string $table): string
    {
        $pdo = $this->connect($database);
        $stmt = $pdo->query('SHOW CREATE TABLE `' . $this->escapeIdentifier($table) . '`');
        $row = $stmt->fetch();
        return $row['Create Table'] ?? $row['Create View'] ?? '';
    }

    /**
     * Browse rows with pagination
     */
    public function browseTable(string $database, string $table, int $page = 1, int $perPage = 50, ?string $orderBy = null, string $orderDir = 'ASC', ?string $search = null): array
    {
        $pdo = $this->connect($database);
        $tableSafe = '`' . $this->escapeIdentifier($table) . '`';

        // Count total
        $countSql = "SELECT COUNT(*) FROM {$tableSafe}";
        $params = [];

        // Search filter
        $whereClauses = [];
        if ($search) {
            $columns = $this->getColumns($database, $table);
            foreach ($columns as $col) {
                $whereClauses[] = '`' . $this->escapeIdentifier($col['Field']) . '` LIKE :search_' . $col['Field'];
                $params['search_' . $col['Field']] = "%{$search}%";
            }
        }

        if (!empty($whereClauses)) {
            $where = ' WHERE ' . implode(' OR ', $whereClauses);
            $countSql .= $where;
        }

        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // Fetch rows
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT * FROM {$tableSafe}";
        if (!empty($whereClauses)) {
            $sql .= $where;
        }
        if ($orderBy) {
            $sql .= ' ORDER BY `' . $this->escapeIdentifier($orderBy) . '` ' . ($orderDir === 'DESC' ? 'DESC' : 'ASC');
        }
        $sql .= " LIMIT {$perPage} OFFSET {$offset}";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        return [
            'rows'       => $rows,
            'total'      => $total,
            'page'       => $page,
            'per_page'   => $perPage,
            'total_pages' => (int) ceil($total / $perPage),
        ];
    }

    /**
     * Execute a raw SQL query
     */
    public function executeQuery(string $database, string $sql): array
    {
        $pdo = $this->connect($database);
        $start = microtime(true);

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $elapsed = microtime(true) - $start;

            $isSelect = stripos(trim($sql), 'SELECT') === 0
                || stripos(trim($sql), 'SHOW') === 0
                || stripos(trim($sql), 'DESCRIBE') === 0
                || stripos(trim($sql), 'DESC ') === 0
                || stripos(trim($sql), 'EXPLAIN') === 0;

            if ($isSelect) {
                $rows = $stmt->fetchAll();
                $columns = [];
                if (!empty($rows)) {
                    $columns = array_keys($rows[0]);
                } else {
                    // Try to get column names from metadata
                    $colCount = $stmt->columnCount();
                    for ($i = 0; $i < $colCount; $i++) {
                        $meta = $stmt->getColumnMeta($i);
                        $columns[] = $meta['name'];
                    }
                }
                return [
                    'success'  => true,
                    'type'     => 'select',
                    'columns'  => $columns,
                    'rows'     => $rows,
                    'count'    => count($rows),
                    'time'     => round($elapsed, 4),
                ];
            } else {
                return [
                    'success'  => true,
                    'type'     => 'modify',
                    'affected' => $stmt->rowCount(),
                    'time'     => round($elapsed, 4),
                ];
            }
        } catch (PDOException $e) {
            $elapsed = microtime(true) - $start;
            return [
                'success' => false,
                'error'   => $e->getMessage(),
                'code'    => $e->getCode(),
                'time'    => round($elapsed, 4),
            ];
        }
    }

    /**
     * Get server variables
     */
    public function getServerInfo(): array
    {
        $pdo = $this->connect();
        $version = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);

        $vars = [];
        $stmt = $pdo->query("SHOW VARIABLES WHERE Variable_name IN ('version','version_comment','hostname','port','datadir','character_set_server','collation_server','max_connections','innodb_buffer_pool_size','uptime')");
        foreach ($stmt->fetchAll() as $row) {
            $vars[$row['Variable_name']] = $row['Value'];
        }

        // Get global status
        $status = [];
        $stmt = $pdo->query("SHOW GLOBAL STATUS WHERE Variable_name IN ('Uptime','Threads_connected','Questions','Bytes_received','Bytes_sent')");
        foreach ($stmt->fetchAll() as $row) {
            $status[$row['Variable_name']] = $row['Value'];
        }

        return [
            'version'   => $version,
            'variables' => $vars,
            'status'    => $status,
        ];
    }

    /**
     * Export table as SQL
     */
    public function exportTable(string $database, string $table): string
    {
        $pdo = $this->connect($database);
        $tableSafe = '`' . $this->escapeIdentifier($table) . '`';

        $output = "-- DBForge SQL Export\n";
        $output .= "-- Database: {$database}\n";
        $output .= "-- Table: {$table}\n";
        $output .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";

        // Create statement
        $output .= $this->getCreateStatement($database, $table) . ";\n\n";

        // Data
        $stmt = $pdo->query("SELECT * FROM {$tableSafe}");
        $rows = $stmt->fetchAll();

        if (!empty($rows)) {
            $columns = array_keys($rows[0]);
            $colList = implode('`, `', $columns);

            foreach ($rows as $row) {
                $values = array_map(function ($val) use ($pdo) {
                    if ($val === null) return 'NULL';
                    return $pdo->quote($val);
                }, array_values($row));
                $output .= "INSERT INTO {$tableSafe} (`{$colList}`) VALUES (" . implode(', ', $values) . ");\n";
            }
        }

        return $output;
    }

    /**
     * Export table as CSV
     */
    public function exportTableCsv(string $database, string $table): string
    {
        $pdo = $this->connect($database);
        $tableSafe = '`' . $this->escapeIdentifier($table) . '`';

        $stmt = $pdo->query("SELECT * FROM {$tableSafe}");
        $rows = $stmt->fetchAll();

        $output = fopen('php://temp', 'r+');
        if (!empty($rows)) {
            fputcsv($output, array_keys($rows[0]));
            foreach ($rows as $row) {
                fputcsv($output, array_values($row));
            }
        }
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        return $csv;
    }

    /**
     * Drop a table
     */
    public function dropTable(string $database, string $table): bool
    {
        $pdo = $this->connect($database);
        $pdo->exec('DROP TABLE `' . $this->escapeIdentifier($table) . '`');
        return true;
    }

    /**
     * Truncate a table
     */
    public function truncateTable(string $database, string $table): bool
    {
        $pdo = $this->connect($database);
        $pdo->exec('TRUNCATE TABLE `' . $this->escapeIdentifier($table) . '`');
        return true;
    }

    /**
     * Create a new database
     */
    public function createDatabase(string $name, string $charset = 'utf8mb4', string $collation = 'utf8mb4_general_ci'): bool
    {
        $pdo = $this->connect();
        $sql = 'CREATE DATABASE `' . $this->escapeIdentifier($name) . '`'
            . ' CHARACTER SET ' . preg_replace('/[^a-zA-Z0-9_]/', '', $charset)
            . ' COLLATE ' . preg_replace('/[^a-zA-Z0-9_]/', '', $collation);
        $pdo->exec($sql);
        return true;
    }

    /**
     * Drop a database
     */
    public function dropDatabase(string $name): bool
    {
        $pdo = $this->connect();
        $pdo->exec('DROP DATABASE `' . $this->escapeIdentifier($name) . '`');
        return true;
    }

    private function escapeIdentifier(string $identifier): string
    {
        return str_replace('`', '``', $identifier);
    }

    /**
     * Execute a SQL dump — splits by statement delimiter and runs each one.
     * Returns array of results per statement.
     */
    public function executeSqlDump(?string $database, string $sql): array
    {
        $pdo = $this->connect($database);
        $results = [];
        $statements = $this->splitSqlStatements($sql);

        foreach ($statements as $i => $stmt) {
            $stmt = trim($stmt);
            if (empty($stmt)) continue;

            $start = microtime(true);
            try {
                $affected = $pdo->exec($stmt);
                $elapsed = microtime(true) - $start;
                $results[] = [
                    'success' => true,
                    'sql'     => mb_substr($stmt, 0, 120) . (mb_strlen($stmt) > 120 ? '…' : ''),
                    'rows'    => $affected !== false ? $affected : 0,
                    'time'    => $elapsed,
                ];
            } catch (\PDOException $e) {
                $elapsed = microtime(true) - $start;
                $results[] = [
                    'success' => false,
                    'sql'     => mb_substr($stmt, 0, 120) . (mb_strlen($stmt) > 120 ? '…' : ''),
                    'error'   => $e->getMessage(),
                    'time'    => $elapsed,
                ];
            }
        }

        return $results;
    }

    /**
     * Split SQL dump into individual statements, respecting strings and delimiters.
     */
    private function splitSqlStatements(string $sql): array
    {
        $statements = [];
        $current = '';
        $len = strlen($sql);
        $i = 0;
        $inSingleQuote = false;
        $inDoubleQuote = false;

        while ($i < $len) {
            $ch = $sql[$i];

            // Skip escaped characters inside strings
            if ($ch === '\\' && ($inSingleQuote || $inDoubleQuote)) {
                $current .= $ch . ($sql[$i + 1] ?? '');
                $i += 2;
                continue;
            }

            // Track string state
            if ($ch === "'" && !$inDoubleQuote) {
                $inSingleQuote = !$inSingleQuote;
            } elseif ($ch === '"' && !$inSingleQuote) {
                $inDoubleQuote = !$inDoubleQuote;
            }

            // Statement delimiter (only outside strings)
            if ($ch === ';' && !$inSingleQuote && !$inDoubleQuote) {
                $trimmed = trim($current);
                if (!empty($trimmed)) {
                    $statements[] = $trimmed;
                }
                $current = '';
                $i++;
                continue;
            }

            // Skip single-line comments
            if (!$inSingleQuote && !$inDoubleQuote && $ch === '-' && ($sql[$i + 1] ?? '') === '-') {
                $eol = strpos($sql, "\n", $i);
                $i = $eol === false ? $len : $eol + 1;
                continue;
            }

            // Skip block comments
            if (!$inSingleQuote && !$inDoubleQuote && $ch === '/' && ($sql[$i + 1] ?? '') === '*') {
                $end = strpos($sql, '*/', $i + 2);
                $i = $end === false ? $len : $end + 2;
                continue;
            }

            $current .= $ch;
            $i++;
        }

        $trimmed = trim($current);
        if (!empty($trimmed)) {
            $statements[] = $trimmed;
        }

        return $statements;
    }

    /**
     * Import CSV data into a table.
     * Returns [inserted, skipped, errors].
     */
    public function importCsv(string $database, string $table, string $csvContent, array $options = []): array
    {
        $delimiter  = $options['delimiter'] ?? ',';
        $enclosure  = $options['enclosure'] ?? '"';
        $hasHeader  = $options['has_header'] ?? true;
        $skipErrors = $options['skip_errors'] ?? true;

        $pdo = $this->connect($database);
        $lines = str_getcsv_rows($csvContent, $delimiter, $enclosure);

        if (empty($lines)) {
            return ['inserted' => 0, 'skipped' => 0, 'errors' => ['File is empty or unreadable.']];
        }

        // Get column names
        $columns = [];
        $startRow = 0;
        if ($hasHeader) {
            $columns = $lines[0];
            $startRow = 1;
        } else {
            // Use table column names
            $tableCols = $this->getColumns($database, $table);
            $columns = array_map(fn($c) => $c['Field'], $tableCols);
            // Limit to number of CSV columns
            $columns = array_slice($columns, 0, count($lines[0] ?? []));
        }

        if (empty($columns)) {
            return ['inserted' => 0, 'skipped' => 0, 'errors' => ['No columns detected.']];
        }

        // Build prepared statement
        $escapedCols = array_map(fn($c) => '`' . $this->escapeIdentifier(trim($c)) . '`', $columns);
        $placeholders = implode(',', array_fill(0, count($columns), '?'));
        $sql = "INSERT INTO `{$this->escapeIdentifier($table)}` (" . implode(',', $escapedCols) . ") VALUES ({$placeholders})";
        $stmt = $pdo->prepare($sql);

        $inserted = 0;
        $skipped = 0;
        $errors = [];

        for ($i = $startRow; $i < count($lines); $i++) {
            $row = $lines[$i];
            if (empty($row) || (count($row) === 1 && trim($row[0]) === '')) continue;

            // Pad or trim to match column count
            while (count($row) < count($columns)) $row[] = null;
            $row = array_slice($row, 0, count($columns));

            // Convert empty strings to null for nullable columns
            $row = array_map(fn($v) => ($v === '' || $v === 'NULL') ? null : $v, $row);

            try {
                $stmt->execute($row);
                $inserted++;
            } catch (\PDOException $e) {
                $skipped++;
                if (count($errors) < 20) {
                    $errors[] = "Row " . ($i + 1) . ": " . $e->getMessage();
                }
                if (!$skipErrors) break;
            }
        }

        return ['inserted' => $inserted, 'skipped' => $skipped, 'errors' => $errors];
    }
}

/**
 * Parse CSV content into rows (handles multiline fields properly).
 */
function str_getcsv_rows(string $content, string $delimiter = ',', string $enclosure = '"'): array
{
    $rows = [];
    $stream = fopen('php://temp', 'r+');
    fwrite($stream, $content);
    rewind($stream);
    while (($row = fgetcsv($stream, 0, $delimiter, $enclosure)) !== false) {
        $rows[] = $row;
    }
    fclose($stream);
    return $rows;
}
