<?php if (!$currentDb): ?>
<div class="info-card" style="max-width:500px;margin:40px auto;text-align:center;">
    <div style="margin-bottom:8px;"><?= icon("database", 36) ?></div>
    <div class="info-label">No Database Selected</div>
    <div style="margin-top:6px;color:var(--text-secondary);font-size:var(--font-size-sm);">Choose a database from the sidebar to get started.</div>
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
        <a href="?db=<?= urlencode($currentDb) ?>&table=<?= urlencode($currentTable) ?>&tab=export" class="btn btn-ghost btn-sm"><?= icon("download", 13) ?> Export</a>
    </div>
</div>

<!-- Data Table -->
<?php
// Find primary key column for inline editing
$pkCol = null;
foreach ($columns as $col) {
    if ($col['Key'] === 'PRI') { $pkCol = $col['Field']; break; }
}
?>
<div class="table-wrapper"
     id="browse-table"
     data-db="<?= h($currentDb) ?>"
     data-table="<?= h($currentTable) ?>"
     data-pk="<?= h($pkCol ?? '') ?>">
    <table class="data-table">
        <thead>
            <tr>
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
                <td colspan="<?= count($columns) + ($pkCol ? 1 : 0) ?>" style="text-align:center;padding:30px;color:var(--text-muted);">
                    <?= $search ? 'No rows match your filter.' : 'Table is empty.' ?>
                </td>
            </tr>
            <?php else: ?>
            <?php foreach ($rows as $ri => $row): ?>
            <?php $pkVal = $pkCol ? ($row[$pkCol] ?? '') : ''; ?>
            <tr data-pk-val="<?= h(strval($pkVal)) ?>">
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
