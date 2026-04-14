<?php if (!$currentDb || !$currentTable): ?>
<div class="error-box">Select a table to view its info.</div>
<?php return; endif; ?>

<?php
try {
    $columns = $dbInstance->getColumns($currentDb, $currentTable);
    $tableStatus = $dbInstance->getTables($currentDb);
    $thisTable = null;
    foreach ($tableStatus as $t) {
        if ($t['Name'] === $currentTable) {
            $thisTable = $t;
            break;
        }
    }
} catch (Exception $e) {
    echo '<div class="error-box"><strong>ERROR:</strong> ' . h($e->getMessage()) . '</div>';
    return;
}

$primaryKey = '—';
$indexCount = 0;
foreach ($columns as $col) {
    if ($col['Key'] === 'PRI') $primaryKey = $col['Field'];
    if (!empty($col['Key'])) $indexCount++;
}
?>

<h3 class="section-title">Table Information — <span class="highlight"><?= h($currentTable) ?></span></h3>

<div class="info-grid">
    <div class="info-card">
        <div class="info-label">Database</div>
        <div class="info-value warning"><?= h($currentDb) ?></div>
    </div>
    <div class="info-card">
        <div class="info-label">Table</div>
        <div class="info-value info"><?= h($currentTable) ?></div>
    </div>
    <div class="info-card">
        <div class="info-label">Engine</div>
        <div class="info-value purple"><?= h($thisTable['Engine'] ?? '—') ?></div>
    </div>
    <div class="info-card">
        <div class="info-label">Collation</div>
        <div class="info-value muted"><?= h($thisTable['Collation'] ?? '—') ?></div>
    </div>
    <div class="info-card">
        <div class="info-label">Rows</div>
        <div class="info-value accent"><?= format_number($thisTable['Rows'] ?? 0) ?></div>
    </div>
    <div class="info-card">
        <div class="info-label">Columns</div>
        <div class="info-value gold"><?= count($columns) ?></div>
    </div>
    <div class="info-card">
        <div class="info-label">Primary Key</div>
        <div class="info-value gold"><?= h($primaryKey) ?></div>
    </div>
    <div class="info-card">
        <div class="info-label">Auto Increment</div>
        <div class="info-value info"><?= h($thisTable['Auto_increment'] ?? '—') ?></div>
    </div>
    <div class="info-card">
        <div class="info-label">Indexes</div>
        <div class="info-value purple"><?= $indexCount ?></div>
    </div>
    <div class="info-card">
        <div class="info-label">Data Length</div>
        <div class="info-value muted"><?= format_bytes((int)($thisTable['Data_length'] ?? 0)) ?></div>
    </div>
    <div class="info-card">
        <div class="info-label">Index Length</div>
        <div class="info-value muted"><?= format_bytes((int)($thisTable['Index_length'] ?? 0)) ?></div>
    </div>
    <div class="info-card">
        <div class="info-label">Row Format</div>
        <div class="info-value muted"><?= h($thisTable['Row_format'] ?? '—') ?></div>
    </div>
    <div class="info-card">
        <div class="info-label">Avg Row Length</div>
        <div class="info-value muted"><?= format_bytes((int)($thisTable['Avg_row_length'] ?? 0)) ?></div>
    </div>
    <div class="info-card">
        <div class="info-label">Created</div>
        <div class="info-value muted" style="font-size:var(--font-size-sm);"><?= h($thisTable['Create_time'] ?? '—') ?></div>
    </div>
    <div class="info-card">
        <div class="info-label">Updated</div>
        <div class="info-value muted" style="font-size:var(--font-size-sm);"><?= h($thisTable['Update_time'] ?? '—') ?></div>
    </div>
    <div class="info-card">
        <div class="info-label">Comment</div>
        <div class="info-value muted" style="font-size:var(--font-size-sm);"><?= h($thisTable['Comment'] ?? '—') ?></div>
    </div>
</div>

<!-- Quick Queries -->
<h3 class="section-title" style="margin-top:24px;">Quick Queries</h3>
<div class="quick-queries">
    <?php
    $queries = [
        "SELECT * FROM `{$currentTable}`",
        "SELECT COUNT(*) FROM `{$currentTable}`",
        "DESCRIBE `{$currentTable}`",
        "SHOW CREATE TABLE `{$currentTable}`",
        "SELECT * FROM `{$currentTable}` LIMIT 10",
    ];
    foreach ($queries as $q):
    ?>
    <a href="?db=<?= urlencode($currentDb) ?>&table=<?= urlencode($currentTable) ?>&tab=sql&run=1&sql=<?= urlencode($q) ?>" class="quick-query">
        <code><?= h($q) ?></code>
    </a>
    <?php endforeach; ?>
</div>

<!-- Danger Zone -->
<h3 class="section-title" style="margin-top:30px;color:var(--danger);">Danger Zone</h3>
<div style="display:flex;gap:8px;">
    <button class="btn btn-danger btn-sm" onclick="DBForge.confirm({
        title: 'Truncate Table',
        message: 'TRUNCATE TABLE `<?= h($currentTable) ?>`? This will delete ALL rows. This cannot be undone.',
        confirmText: 'Truncate',
        cancelText: 'Cancel',
        danger: true
    }).then(function(ok){ if(ok) window.location='?db=<?= urlencode($currentDb) ?>&table=<?= urlencode($currentTable) ?>&action=truncate'; })">
        <?= icon('alert-triangle', 13) ?> Truncate Table
    </button>
    <button class="btn btn-danger btn-sm" onclick="DBForge.confirm({
        title: 'Drop Table',
        message: 'DROP TABLE `<?= h($currentTable) ?>`? This will permanently delete the table and all its data. This cannot be undone.',
        confirmText: 'Drop Table',
        cancelText: 'Cancel',
        danger: true
    }).then(function(ok){ if(ok) window.location='?db=<?= urlencode($currentDb) ?>&table=<?= urlencode($currentTable) ?>&action=drop'; })">
        <?= icon('trash', 13) ?> Drop Table
    </button>
</div>
