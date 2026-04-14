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

    private function escapeIdentifier(string $identifier): string
    {
        return str_replace('`', '``', $identifier);
    }
}
