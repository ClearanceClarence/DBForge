<?php if (!$currentDb || !$currentTable): ?>
<div class="error-box">Select a table to export.</div>
<?php return; endif; ?>

<h3 class="section-title">Export — <span class="highlight"><?= h($currentTable) ?></span></h3>

<div class="info-grid" style="max-width:600px;">
    <div class="info-card">
        <div class="info-label">Export as SQL</div>
        <div style="margin-top:8px;font-size:var(--font-size-sm);color:var(--text-secondary);margin-bottom:12px;">
            CREATE TABLE + INSERT statements
        </div>
        <a href="?db=<?= urlencode($currentDb) ?>&table=<?= urlencode($currentTable) ?>&action=export_sql"
           class="btn btn-primary btn-sm"><?= icon('download', 13) ?> Download .sql</a>
    </div>
    <div class="info-card">
        <div class="info-label">Export as CSV</div>
        <div style="margin-top:8px;font-size:var(--font-size-sm);color:var(--text-secondary);margin-bottom:12px;">
            Comma-separated values with headers
        </div>
        <a href="?db=<?= urlencode($currentDb) ?>&table=<?= urlencode($currentTable) ?>&action=export_csv"
           class="btn btn-primary btn-sm"><?= icon('download', 13) ?> Download .csv</a>
    </div>
</div>

<!-- Export All Tables -->
<h3 class="section-title" style="margin-top:30px;">Export Entire Database</h3>
<div class="info-card" style="max-width:600px;">
    <div class="info-label">Export all tables in <span style="color:var(--warning);"><?= h($currentDb) ?></span></div>
    <div style="margin-top:8px;font-size:var(--font-size-sm);color:var(--text-secondary);margin-bottom:12px;">
        Generates SQL dump of all tables with CREATE + INSERT statements.
    </div>
    <a href="?db=<?= urlencode($currentDb) ?>&action=export_db"
       class="btn btn-primary btn-sm"><?= icon('download', 13) ?> Download full database .sql</a>
</div>
