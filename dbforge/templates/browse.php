<?php if (!$currentDb):
    // ── Server Overview — all databases ──
    $dbStats = [];
    $totalTables = 0;
    $totalSize = 0;
    foreach ($databases as $dbName) {
        $stat = ['name' => $dbName, 'tables' => 0, 'rows' => 0, 'data_size' => 0, 'index_size' => 0, 'collation' => '—'];
        try {
            $tables = $dbInstance->getTables($dbName);
            $stat['tables'] = count($tables);
            $totalTables += $stat['tables'];
            foreach ($tables as $t) {
                $stat['rows'] += (int)($t['Rows'] ?? 0);
                $stat['data_size'] += (int)($t['Data_length'] ?? 0);
                $stat['index_size'] += (int)($t['Index_length'] ?? 0);
            }
            if (!empty($tables[0]['Collation'])) {
                $stat['collation'] = $tables[0]['Collation'];
            }
            $totalSize += $stat['data_size'] + $stat['index_size'];
        } catch (Exception $e) {
            // skip inaccessible dbs
        }
        $dbStats[] = $stat;
    }
?>

<!-- Server Stats -->
<h3 class="section-title"><?= icon('server', 16) ?> Server Overview</h3>

<div class="info-grid" style="margin-bottom:20px;">
    <div class="info-card">
        <div class="info-label">Databases</div>
        <div class="info-value accent"><?= count($databases) ?></div>
    </div>
    <div class="info-card">
        <div class="info-label">Total Tables</div>
        <div class="info-value info"><?= format_number($totalTables) ?></div>
    </div>
    <div class="info-card">
        <div class="info-label">Total Size</div>
        <div class="info-value warning"><?= format_bytes($totalSize) ?></div>
    </div>
    <div class="info-card">
        <div class="info-label">MySQL Version</div>
        <div class="info-value purple"><?= h($serverVersion) ?></div>
    </div>
    <div class="info-card">
        <div class="info-label">PHP Version</div>
        <div class="info-value gold"><?= PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '.' . PHP_RELEASE_VERSION ?></div>
    </div>
    <div class="info-card">
        <div class="info-label">Server</div>
        <div class="info-value muted"><?= h($serverHost) ?></div>
    </div>
</div>

<!-- Quick Actions -->
<div style="display:flex;gap:8px;margin-bottom:20px;">
    <a href="?tab=server" class="btn btn-ghost btn-sm"><?= icon('settings', 13) ?> Server Details</a>
    <a href="?tab=sql" class="btn btn-ghost btn-sm"><?= icon('terminal', 13) ?> SQL Query</a>
</div>

<!-- Database List -->
<div class="table-toolbar">
    <h3 class="section-title" style="margin-bottom:0;"><?= icon('database', 15) ?> All Databases</h3>
    <span class="toolbar-info"><?= count($databases) ?> databases</span>
</div>

<div class="table-wrapper">
    <table class="data-table">
        <thead>
            <tr>
                <th>Database</th>
                <th>Tables</th>
                <th>Rows (est.)</th>
                <th>Data Size</th>
                <th>Index Size</th>
                <th>Total Size</th>
                <th>Collation</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($dbStats as $stat): ?>
            <tr>
                <td>
                    <a href="?db=<?= urlencode($stat['name']) ?>" style="color:var(--warning);font-weight:600;text-decoration:none;display:flex;align-items:center;gap:6px;">
                        <?= icon('database', 14) ?> <?= h($stat['name']) ?>
                    </a>
                </td>
                <td class="cell-number"><?= format_number($stat['tables']) ?></td>
                <td class="cell-number"><?= format_number($stat['rows']) ?></td>
                <td style="color:var(--text-secondary);"><?= format_bytes($stat['data_size']) ?></td>
                <td style="color:var(--text-secondary);"><?= format_bytes($stat['index_size']) ?></td>
                <td style="color:var(--text-primary);font-weight:500;"><?= format_bytes($stat['data_size'] + $stat['index_size']) ?></td>
                <td style="color:var(--text-muted);font-size:var(--font-size-xs);"><?= h($stat['collation']) ?></td>
                <td>
                    <div style="display:flex;gap:4px;">
                        <a href="?db=<?= urlencode($stat['name']) ?>&tab=browse" class="btn btn-ghost btn-sm" title="Browse"><?= icon('table', 13) ?></a>
                        <a href="?db=<?= urlencode($stat['name']) ?>&tab=sql" class="btn btn-ghost btn-sm" title="SQL"><?= icon('terminal', 13) ?></a>
                        <a href="?db=<?= urlencode($stat['name']) ?>&action=export_db" class="btn btn-ghost btn-sm" title="Export"><?= icon('download', 13) ?></a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr style="font-weight:600;">
                <td>Total: <?= count($databases) ?> databases</td>
                <td class="cell-number"><?= format_number($totalTables) ?></td>
                <td></td>
                <td></td>
                <td></td>
                <td style="color:var(--accent);"><?= format_bytes($totalSize) ?></td>
                <td></td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</div>

<?php return; endif; ?>

<?php if ($currentDb && !$currentTable):
    // ── Database Overview ──
    try {
        $allTables = $dbInstance->getTables($currentDb);
    } catch (Exception $e) {
        echo '<div class="error-box"><strong>ERROR:</strong> ' . h($e->getMessage()) . '</div>';
        return;
    }

    $totalRows = 0;
    $totalDataSize = 0;
    $totalIndexSize = 0;
    $exactCounts = [];
    foreach ($allTables as $t) {
        $tName = $t['Name'];
        try {
            $exactCounts[$tName] = $dbInstance->getExactRowCount($currentDb, $tName);
        } catch (Exception $e) {
            $exactCounts[$tName] = (int)($t['Rows'] ?? 0);
        }
        $totalRows += $exactCounts[$tName];
        $totalDataSize += (int)($t['Data_length'] ?? 0);
        $totalIndexSize += (int)($t['Index_length'] ?? 0);
    }
    $totalSize = $totalDataSize + $totalIndexSize;
?>

<!-- Database Stats -->
<h3 class="section-title">Database: <span class="highlight"><?= h($currentDb) ?></span></h3>

<div class="info-grid" style="margin-bottom:20px;">
    <div class="info-card">
        <div class="info-label">Tables</div>
        <div class="info-value accent"><?= count($allTables) ?></div>
    </div>
    <div class="info-card">
        <div class="info-label">Total Rows</div>
        <div class="info-value info"><?= format_number($totalRows) ?></div>
    </div>
    <div class="info-card">
        <div class="info-label">Data Size</div>
        <div class="info-value warning"><?= format_bytes($totalDataSize) ?></div>
    </div>
    <div class="info-card">
        <div class="info-label">Index Size</div>
        <div class="info-value purple"><?= format_bytes($totalIndexSize) ?></div>
    </div>
    <div class="info-card">
        <div class="info-label">Total Size</div>
        <div class="info-value gold"><?= format_bytes($totalSize) ?></div>
    </div>
    <div class="info-card">
        <div class="info-label">Default Collation</div>
        <div class="info-value muted" style="font-size:var(--font-size-sm);"><?= h($allTables[0]['Collation'] ?? '—') ?></div>
    </div>
</div>

<!-- Quick Actions -->
<div style="display:flex;gap:8px;margin-bottom:20px;">
    <a href="?db=<?= urlencode($currentDb) ?>&tab=sql" class="btn btn-primary btn-sm"><?= icon('terminal', 13) ?> SQL Query</a>
    <a href="?db=<?= urlencode($currentDb) ?>&action=export_db" class="btn btn-ghost btn-sm"><?= icon('download', 13) ?> Export Database</a>
    <a href="?tab=server" class="btn btn-ghost btn-sm"><?= icon('settings', 13) ?> Server Info</a>
</div>

<!-- Table List -->
<div class="table-toolbar">
    <h3 class="section-title" style="margin-bottom:0;">All Tables</h3>
    <span class="toolbar-info"><?= count($allTables) ?> tables</span>
</div>

<div class="table-wrapper">
    <table class="data-table">
        <thead>
            <tr>
                <th>Table Name</th>
                <th>Engine</th>
                <th>Rows</th>
                <th>Data Size</th>
                <th>Index Size</th>
                <th>Auto Inc.</th>
                <th>Collation</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($allTables as $i => $tbl):
                $tName = $tbl['Name'];
                $tRows = $exactCounts[$tName] ?? 0;
                $tData = (int)($tbl['Data_length'] ?? 0);
                $tIdx  = (int)($tbl['Index_length'] ?? 0);
            ?>
            <tr>
                <td>
                    <a href="?db=<?= urlencode($currentDb) ?>&table=<?= urlencode($tName) ?>&tab=browse"
                       style="color:var(--info);font-weight:600;text-decoration:none;">
                        <?= h($tName) ?>
                    </a>
                </td>
                <td style="color:var(--purple);"><?= h($tbl['Engine'] ?? '—') ?></td>
                <td class="cell-number"><?= format_number($tRows) ?></td>
                <td style="color:var(--text-secondary);"><?= format_bytes($tData) ?></td>
                <td style="color:var(--text-secondary);"><?= format_bytes($tIdx) ?></td>
                <td style="color:var(--text-muted);"><?= h($tbl['Auto_increment'] ?? '—') ?></td>
                <td style="color:var(--text-muted);font-size:var(--font-size-xs);"><?= h($tbl['Collation'] ?? '—') ?></td>
                <td>
                    <div style="display:flex;gap:4px;">
                        <a href="?db=<?= urlencode($currentDb) ?>&table=<?= urlencode($tName) ?>&tab=browse" class="btn btn-ghost btn-sm" title="Browse"><?= icon('table', 13) ?></a>
                        <a href="?db=<?= urlencode($currentDb) ?>&table=<?= urlencode($tName) ?>&tab=structure" class="btn btn-ghost btn-sm" title="Structure"><?= icon('columns', 13) ?></a>
                        <a href="?db=<?= urlencode($currentDb) ?>&table=<?= urlencode($tName) ?>&tab=sql&run=1&sql=<?= urlencode("SELECT * FROM `{$tName}` LIMIT 25") ?>" class="btn btn-ghost btn-sm" title="SQL"><?= icon('terminal', 13) ?></a>
                        <a href="?db=<?= urlencode($currentDb) ?>&table=<?= urlencode($tName) ?>&tab=export" class="btn btn-ghost btn-sm" title="Export"><?= icon('download', 13) ?></a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php return; endif; ?>

<?php
$page    = max(1, (int) input('page', 1));
$perPage = (int) ($config['app']['rows_per_page'] ?? 50);
$orderBy = input('sort');
$orderDir = input('dir', 'ASC');
$search  = input('search', '');

try {
    $columns = $dbInstance->getColumns($currentDb, $currentTable);
    $result  = $dbInstance->browseTable($currentDb, $currentTable, $page, $perPage, $orderBy, $orderDir, $search ?: null);
} catch (Exception $e) {
    echo '<div class="error-box"><strong>ERROR:</strong> ' . h($e->getMessage()) . '</div>';
    return;
}

$rows       = $result['rows'];
$total      = $result['total'];
$totalPages = $result['total_pages'];

// Build column lookup for key info
$colInfo = [];
foreach ($columns as $col) {
    $colInfo[$col['Field']] = $col;
}
?>

<!-- Toolbar -->
<div class="table-toolbar">
    <form method="get" class="search-box">
        <input type="hidden" name="db" value="<?= h($currentDb) ?>">
        <input type="hidden" name="table" value="<?= h($currentTable) ?>">
        <input type="hidden" name="tab" value="browse">
        <?= icon("search", 14) ?>
        <input type="text" name="search" class="search-input" placeholder="Filter rows…" value="<?= h($search) ?>">
    </form>
    <div class="toolbar-info">
        <span>Showing <?= format_number(count($rows)) ?> of <?= format_number($total) ?> rows</span>
        <span>|</span>
        <span>Page <?= $page ?>/<?= max(1, $totalPages) ?></span>
    </div>
    <div class="toolbar-actions">
        <button type="button" class="btn btn-ghost btn-sm" id="toggle-col-types" onclick="DBForge.toggleColTypes()" title="Toggle column types">
            <?= icon('eye', 13) ?> Types
        </button>
        <a href="?db=<?= urlencode($currentDb) ?>&table=<?= urlencode($currentTable) ?>&tab=export" class="btn btn-ghost btn-sm"><?= icon("download", 13) ?> Export</a>
    </div>
</div>

<?php
// Find primary key column for inline editing + bulk select
$pkCol = null;
foreach ($columns as $col) {
    if ($col['Key'] === 'PRI') { $pkCol = $col['Field']; break; }
}
?>

<!-- Bulk Actions Bar (hidden until rows selected) -->
<?php if ($pkCol && !empty($rows)): ?>
<div class="bulk-bar" id="bulk-bar" style="display:none;">
    <label class="bulk-count" id="bulk-count">0 rows selected</label>
    <button type="button" class="btn btn-danger btn-sm" id="bulk-delete-btn">
        <?= icon('trash', 13) ?> Delete Selected
    </button>
    <button type="button" class="btn btn-ghost btn-sm" id="bulk-clear-btn">
        <?= icon('x', 13) ?> Clear
    </button>
</div>
<?php endif; ?>

<!-- Data Table -->
<div class="table-wrapper"
     id="browse-table"
     data-db="<?= h($currentDb) ?>"
     data-table="<?= h($currentTable) ?>"
     data-pk="<?= h($pkCol ?? '') ?>">
    <table class="data-table">
        <thead>
            <tr>
                <?php if ($pkCol): ?>
                <th style="width:36px;text-align:center;padding:6px;">
                    <input type="checkbox" id="select-all" class="row-checkbox" title="Select all">
                </th>
                <?php endif; ?>
                <?php foreach ($columns as $col): ?>
                <?php
                    $field = $col['Field'];
                    $isSorted = ($orderBy === $field);
                    $nextDir = ($isSorted && $orderDir === 'ASC') ? 'DESC' : 'ASC';
                    $sortUrl = "?db=" . urlencode($currentDb) . "&table=" . urlencode($currentTable) . "&tab=browse&sort=" . urlencode($field) . "&dir={$nextDir}" . ($search ? "&search=" . urlencode($search) : '');
                ?>
                <th>
                    <a href="<?= $sortUrl ?>" style="color:inherit;text-decoration:none;display:block;">
                        <div style="display:flex;align-items:center;gap:4px;">
                            <?= h($field) ?>
                            <?php if ($col['Key'] === 'PRI'): ?>
                            <span class="key-icon" title="Primary Key"><?= icon('key', 12) ?></span>
                            <?php endif; ?>
                            <?php if ($isSorted): ?>
                            <span class="sort-icon"><?= icon($orderDir === 'ASC' ? 'arrow-up' : 'arrow-down', 11) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="col-type"><?= h($col['Type']) ?></div>
                    </a>
                </th>
                <?php endforeach; ?>
                <?php if ($pkCol): ?>
                <th style="width:40px;text-align:center;">⋯</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
            <tr>
                <td colspan="<?= count($columns) + ($pkCol ? 2 : 0) ?>" style="text-align:center;padding:30px;color:var(--text-muted);">
                    <?= $search ? 'No rows match your filter.' : 'Table is empty.' ?>
                </td>
            </tr>
            <?php else: ?>
            <?php foreach ($rows as $ri => $row): ?>
            <?php $pkVal = $pkCol ? ($row[$pkCol] ?? '') : ''; ?>
            <tr data-pk-val="<?= h(strval($pkVal)) ?>">
                <?php if ($pkCol): ?>
                <td style="text-align:center;padding:4px 6px;">
                    <input type="checkbox" class="row-checkbox row-select" data-pk="<?= h(strval($pkVal)) ?>">
                </td>
                <?php endif; ?>
                <?php foreach ($columns as $col): ?>
                <?php
                    $field = $col['Field'];
                    $value = $row[$field] ?? null;
                    $cls = cell_class($value, $col['Key']);
                    $isEditable = ($pkCol && $col['Key'] !== 'PRI');
                ?>
                <td class="<?= $cls ?><?= $isEditable ? ' cell-editable' : '' ?>"
                    <?php if ($isEditable): ?>
                    data-col="<?= h($field) ?>"
                    data-value="<?= h($value !== null ? strval($value) : '') ?>"
                    data-null="<?= $value === null ? '1' : '0' ?>"
                    <?php endif; ?>
                >
                    <?php if ($value === null): ?>
                        <span class="cell-null">NULL</span>
                    <?php elseif ($cls === 'cell-hash'): ?>
                        <?= h(truncate($value, 20)) ?>
                    <?php else: ?>
                        <?= h(truncate(strval($value), 80)) ?>
                    <?php endif; ?>
                </td>
                <?php endforeach; ?>
                <?php if ($pkCol): ?>
                <td style="text-align:center;padding:4px;">
                    <button class="btn btn-danger btn-sm row-delete-btn"
                            data-pk="<?= h(strval($pkVal)) ?>"
                            title="Delete row"
                            style="padding:2px 6px;font-size:11px;"><?= icon('trash', 12) ?></button>
                </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<div class="pagination">
    <?php
    $baseUrl = "?db=" . urlencode($currentDb) . "&table=" . urlencode($currentTable) . "&tab=browse"
        . ($orderBy ? "&sort=" . urlencode($orderBy) . "&dir={$orderDir}" : '')
        . ($search ? "&search=" . urlencode($search) : '');
    ?>
    <a href="<?= $baseUrl ?>&page=1" class="page-btn <?= $page <= 1 ? 'disabled' : '' ?>">« First</a>
    <a href="<?= $baseUrl ?>&page=<?= max(1, $page - 1) ?>" class="page-btn <?= $page <= 1 ? 'disabled' : '' ?>">‹ Prev</a>

    <?php
    $start = max(1, $page - 3);
    $end = min($totalPages, $page + 3);
    for ($p = $start; $p <= $end; $p++):
    ?>
    <a href="<?= $baseUrl ?>&page=<?= $p ?>" class="page-btn <?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
    <?php endfor; ?>

    <a href="<?= $baseUrl ?>&page=<?= min($totalPages, $page + 1) ?>" class="page-btn <?= $page >= $totalPages ? 'disabled' : '' ?>">Next ›</a>
    <a href="<?= $baseUrl ?>&page=<?= $totalPages ?>" class="page-btn <?= $page >= $totalPages ? 'disabled' : '' ?>">Last »</a>
</div>
<?php endif; ?>
