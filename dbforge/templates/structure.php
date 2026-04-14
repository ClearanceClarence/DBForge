<?php if (!$currentDb || !$currentTable): ?>
<div class="error-box">Select a table to view its structure.</div>
<?php return; endif; ?>

<?php
try {
    $columns = $dbInstance->getColumns($currentDb, $currentTable);
    $indexes = $dbInstance->getIndexes($currentDb, $currentTable);
    $createSql = $dbInstance->getCreateStatement($currentDb, $currentTable);
} catch (Exception $e) {
    echo '<div class="error-box"><strong>ERROR:</strong> ' . h($e->getMessage()) . '</div>';
    return;
}
?>

<!-- Columns -->
<h3 class="section-title">Columns — <span class="highlight"><?= h($currentTable) ?></span></h3>
<div class="table-wrapper">
    <table class="data-table">
        <thead>
            <tr>
                <th>Column</th>
                <th>Type</th>
                <th>Null</th>
                <th>Key</th>
                <th>Default</th>
                <th>Extra</th>
                <th>Collation</th>
                <th>Comment</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($columns as $i => $col): ?>
            <tr>
                <td style="font-weight:600;color:var(--text-primary);"><?= h($col['Field']) ?></td>
                <td style="color:var(--purple);font-family:var(--font-mono);"><?= h($col['Type']) ?></td>
                <td>
                    <?php if ($col['Null'] === 'YES'): ?>
                    <span class="badge badge-nullable">YES</span>
                    <?php else: ?>
                    <span class="badge badge-yes">NO</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($col['Key'] === 'PRI'): ?>
                    <span class="badge badge-primary">PRIMARY</span>
                    <?php elseif ($col['Key'] === 'UNI'): ?>
                    <span class="badge badge-unique">UNIQUE</span>
                    <?php elseif ($col['Key'] === 'MUL'): ?>
                    <span class="badge badge-index">INDEX</span>
                    <?php endif; ?>
                </td>
                <td style="font-family:var(--font-mono);">
                    <?php if ($col['Default'] !== null): ?>
                    <span style="color:var(--text-secondary);"><?= h($col['Default']) ?></span>
                    <?php else: ?>
                    <span class="cell-null">NULL</span>
                    <?php endif; ?>
                </td>
                <td style="color:var(--info);"><?= h($col['Extra']) ?></td>
                <td style="color:var(--text-muted);font-size:var(--font-size-xs);"><?= h($col['Collation'] ?? '') ?></td>
                <td style="color:var(--text-muted);font-size:var(--font-size-xs);"><?= h($col['Comment'] ?? '') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Indexes -->
<h3 class="section-title" style="margin-top:24px;">Indexes</h3>
<div class="table-wrapper">
    <table class="data-table">
        <thead>
            <tr>
                <th>Key Name</th>
                <th>Column</th>
                <th>Seq</th>
                <th>Non Unique</th>
                <th>Type</th>
                <th>Cardinality</th>
                <th>Sub Part</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($indexes)): ?>
            <tr><td colspan="7" style="text-align:center;color:var(--text-muted);padding:20px;">No indexes</td></tr>
            <?php else: ?>
            <?php foreach ($indexes as $idx): ?>
            <tr>
                <td style="color:var(--gold);font-weight:600;"><?= h($idx['Key_name']) ?></td>
                <td><?= h($idx['Column_name']) ?></td>
                <td class="cell-number"><?= h($idx['Seq_in_index']) ?></td>
                <td>
                    <?php if ($idx['Non_unique']): ?>
                    <span class="badge badge-no">Yes</span>
                    <?php else: ?>
                    <span class="badge badge-yes">No</span>
                    <?php endif; ?>
                </td>
                <td><?= h($idx['Index_type']) ?></td>
                <td class="cell-number"><?= h($idx['Cardinality'] ?? '—') ?></td>
                <td><?= h($idx['Sub_part'] ?? '—') ?></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Create Statement -->
<h3 class="section-title" style="margin-top:24px;">Create Statement</h3>
<div class="code-block"><?= h($createSql) ?></div>
