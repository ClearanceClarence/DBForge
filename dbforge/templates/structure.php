<?php if (!$currentDb || !$currentTable): ?>
<div class="error-box">Select a table to view its structure.</div>
<?php return; endif; ?>

<?php
try {
    $columns = $dbInstance->getColumns($currentDb, $currentTable);
    $indexes = $dbInstance->getIndexes($currentDb, $currentTable);
    $createSql = $dbInstance->getCreateStatement($currentDb, $currentTable);

    // Foreign keys
    $foreignKeys = [];
    $referencedBy = [];
    try {
        $foreignKeys = $dbInstance->getForeignKeys($currentDb, $currentTable);
        $referencedBy = $dbInstance->getReferencedBy($currentDb, $currentTable);
    } catch (Exception $e) {
        // FK queries may fail on some MySQL configs - not critical
    }

    // Build lookup: column → FK info
    $fkByColumn = [];
    foreach ($foreignKeys as $fk) {
        $fkByColumn[$fk['COLUMN_NAME']] = $fk;
    }

    // Find primary key columns
    $pkColumns = [];
    foreach ($columns as $col) {
        if ($col['Key'] === 'PRI') $pkColumns[] = $col['Field'];
    }

    // Get table status for AUTO_INCREMENT value
    $tableStatus = $dbInstance->getTables($currentDb);
    $autoIncValue = null;
    $autoIncCol = null;
    foreach ($tableStatus as $ts) {
        if ($ts['Name'] === $currentTable) {
            $autoIncValue = $ts['Auto_increment'] ?? null;
            break;
        }
    }
    // Find which column is AUTO_INCREMENT
    foreach ($columns as $col) {
        if (stripos($col['Extra'], 'auto_increment') !== false) {
            $autoIncCol = $col['Field'];
            break;
        }
    }
} catch (Exception $e) {
    echo '<div class="error-box"><strong>ERROR:</strong> ' . h($e->getMessage()) . '</div>';
    return;
}

$isReadOnly = isset($auth) && $auth->isReadOnly();
$csrfToken = isset($auth) ? $auth->generateCsrfToken() : '';
?>

<div id="structure-editor"
     data-db="<?= h($currentDb) ?>"
     data-table="<?= h($currentTable) ?>"
     data-csrf="<?= h($csrfToken) ?>"
     data-readonly="<?= $isReadOnly ? '1' : '0' ?>">

<?php if ($autoIncValue !== null && $autoIncCol): ?>
<!-- Auto Increment Info -->
<div class="auto-inc-bar">
    <div class="auto-inc-info">
        <?= icon('zap', 15) ?>
        <span class="auto-inc-label">AUTO_INCREMENT</span>
        <span class="auto-inc-col"><?= h($autoIncCol) ?></span>
        <span class="auto-inc-sep">→</span>
        <span class="auto-inc-value">Next ID: <strong><?= format_number($autoIncValue) ?></strong></span>
    </div>
    <?php if (!$isReadOnly): ?>
    <div class="auto-inc-actions" id="auto-inc-actions">
        <input type="number" id="auto-inc-input" class="auto-inc-input"
               value="<?= (int)$autoIncValue ?>" min="1" title="Set next AUTO_INCREMENT value">
        <button type="button" class="btn btn-ghost btn-sm" id="auto-inc-set-btn" title="Set value">
            <?= icon('check', 12) ?> Set
        </button>
        <button type="button" class="btn btn-ghost btn-sm" id="auto-inc-reset-btn" title="Reset to max(id)+1">
            <?= icon('refresh', 12) ?> Reset
        </button>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Columns -->
<div class="table-toolbar">
    <h3 class="section-title" style="margin-bottom:0;">
        <?= icon('columns', 16) ?> Columns — <span class="highlight"><?= h($currentTable) ?></span>
    </h3>
    <?php if (!$isReadOnly): ?>
    <div class="toolbar-actions">
        <button type="button" class="btn btn-primary btn-sm" id="add-column-btn">
            <?= icon('plus', 13) ?> Add Column
        </button>
    </div>
    <?php endif; ?>
</div>

<div class="table-wrapper">
    <table class="data-table" id="structure-table">
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
                <?php if (!$isReadOnly): ?>
                <th style="width:80px;text-align:center;">Actions</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($columns as $i => $col): ?>
            <?php
                $isPK = $col['Key'] === 'PRI';
                $isFK = isset($fkByColumn[$col['Field']]);
                $fkInfo = $isFK ? $fkByColumn[$col['Field']] : null;
                $rowClass = 'struct-row';
                if ($isPK) $rowClass .= ' struct-row-pk';
                if ($isFK) $rowClass .= ' struct-row-fk';
            ?>
            <tr data-original-name="<?= h($col['Field']) ?>" class="<?= $rowClass ?>">
                <!-- Column Name -->
                <td class="struct-cell" data-field="name">
                    <span class="struct-display struct-name-display">
                        <?php if ($isPK): ?>
                        <span class="struct-key-icon struct-pk-icon" title="Primary Key"><?= icon('key', 13) ?></span>
                        <?php endif; ?>
                        <?php if ($isFK): ?>
                        <span class="struct-key-icon struct-fk-icon" title="Foreign Key → <?= h($fkInfo['REFERENCED_TABLE_NAME'] . '.' . $fkInfo['REFERENCED_COLUMN_NAME']) ?>"><?= icon('external-link', 13) ?></span>
                        <?php endif; ?>
                        <span style="font-weight:600;color:var(--text-primary);"><?= h($col['Field']) ?></span>
                    </span>
                    <?php if (!$isReadOnly): ?>
                    <input type="text" class="struct-input" value="<?= h($col['Field']) ?>" style="display:none;">
                    <?php endif; ?>
                </td>

                <!-- Type (searchable) -->
                <td class="struct-cell" data-field="type">
                    <span class="struct-display" style="color:var(--purple);font-family:var(--font-mono);">
                        <?= h($col['Type']) ?>
                    </span>
                    <?php if (!$isReadOnly): ?>
                    <input type="text" class="struct-input struct-type-input" list="mysql-types"
                           value="<?= h($col['Type']) ?>" style="display:none;"
                           placeholder="e.g. VARCHAR(255)">
                    <?php endif; ?>
                </td>

                <!-- Null -->
                <td class="struct-cell" data-field="null">
                    <span class="struct-display">
                        <?php if ($col['Null'] === 'YES'): ?>
                        <span class="badge badge-nullable">YES</span>
                        <?php else: ?>
                        <span class="badge badge-yes">NO</span>
                        <?php endif; ?>
                    </span>
                    <?php if (!$isReadOnly): ?>
                    <label class="struct-check" style="display:none;">
                        <input type="checkbox" class="struct-null-check" <?= $col['Null'] === 'YES' ? 'checked' : '' ?>>
                        <span>Allow NULL</span>
                    </label>
                    <?php endif; ?>
                </td>

                <!-- Key -->
                <td class="struct-key-cell">
                    <?php if ($isPK): ?>
                    <span class="key-badge key-badge-pk"><?= icon('key', 11) ?> PK</span>
                    <?php endif; ?>
                    <?php if ($isFK): ?>
                    <a href="?db=<?= urlencode($fkInfo['REFERENCED_TABLE_SCHEMA'] ?? $currentDb) ?>&table=<?= urlencode($fkInfo['REFERENCED_TABLE_NAME']) ?>&tab=structure"
                       class="key-badge key-badge-fk" title="<?= h($fkInfo['CONSTRAINT_NAME']) ?>">
                        <?= icon('external-link', 11) ?> FK → <?= h($fkInfo['REFERENCED_TABLE_NAME']) ?>.<?= h($fkInfo['REFERENCED_COLUMN_NAME']) ?>
                    </a>
                    <?php elseif ($col['Key'] === 'UNI'): ?>
                    <span class="key-badge key-badge-uni"><?= icon('hash', 11) ?> UNI</span>
                    <?php elseif ($col['Key'] === 'MUL' && !$isFK): ?>
                    <span class="key-badge key-badge-idx"><?= icon('list', 11) ?> IDX</span>
                    <?php endif; ?>
                </td>

                <!-- Default -->
                <td class="struct-cell" data-field="default">
                    <span class="struct-display" style="font-family:var(--font-mono);">
                        <?php if ($col['Default'] !== null): ?>
                        <span style="color:var(--text-secondary);"><?= h($col['Default']) ?></span>
                        <?php else: ?>
                        <span class="cell-null">NULL</span>
                        <?php endif; ?>
                    </span>
                    <?php if (!$isReadOnly): ?>
                    <div class="struct-default-wrap" style="display:none;">
                        <input type="text" class="struct-input struct-default-input"
                               value="<?= h($col['Default'] ?? '') ?>"
                               placeholder="Default value"
                               <?= $col['Default'] === null ? 'disabled' : '' ?>>
                        <label class="struct-check-mini">
                            <input type="checkbox" class="struct-default-null" <?= $col['Default'] === null ? 'checked' : '' ?>>
                            NULL
                        </label>
                    </div>
                    <?php endif; ?>
                </td>

                <!-- Extra -->
                <td class="struct-cell" data-field="extra">
                    <span class="struct-display" style="color:var(--info);">
                        <?= h($col['Extra']) ?>
                    </span>
                    <?php if (!$isReadOnly): ?>
                    <select class="struct-input struct-extra-select" style="display:none;">
                        <option value="" <?= empty($col['Extra']) ? 'selected' : '' ?>>—</option>
                        <option value="AUTO_INCREMENT" <?= $col['Extra'] === 'auto_increment' ? 'selected' : '' ?>>AUTO_INCREMENT</option>
                        <option value="on update CURRENT_TIMESTAMP" <?= str_contains($col['Extra'], 'on update') ? 'selected' : '' ?>>ON UPDATE CURRENT_TIMESTAMP</option>
                    </select>
                    <?php endif; ?>
                </td>

                <!-- Collation -->
                <td style="font-family:var(--font-mono);font-size:var(--font-size-xs);color:var(--text-muted);">
                    <?= h($col['Collation'] ?? '') ?: '<span style="opacity:0.3">—</span>' ?>
                </td>

                <!-- Comment -->
                <td class="struct-cell" data-field="comment">
                    <span class="struct-display" style="color:var(--text-muted);font-size:var(--font-size-xs);">
                        <?= h($col['Comment'] ?? '') ?: '<span style="opacity:0.3">—</span>' ?>
                    </span>
                    <?php if (!$isReadOnly): ?>
                    <input type="text" class="struct-input struct-comment-input"
                           value="<?= h($col['Comment'] ?? '') ?>" style="display:none;"
                           placeholder="Add comment...">
                    <?php endif; ?>
                </td>

                <!-- Actions -->
                <?php if (!$isReadOnly): ?>
                <td style="text-align:center;">
                    <div class="struct-actions">
                        <button type="button" class="btn btn-ghost btn-sm struct-edit-btn" title="Edit">
                            <?= icon('edit', 12) ?>
                        </button>
                        <button type="button" class="btn btn-primary btn-sm struct-save-btn" title="Save" style="display:none;">
                            <?= icon('check', 12) ?>
                        </button>
                        <button type="button" class="btn btn-ghost btn-sm struct-cancel-btn" title="Cancel" style="display:none;">
                            <?= icon('x', 12) ?>
                        </button>
                        <button type="button" class="btn btn-danger btn-sm struct-drop-btn" title="Drop column"
                                data-column="<?= h($col['Field']) ?>">
                            <?= icon('trash', 12) ?>
                        </button>
                    </div>
                </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Add Column Form (hidden by default) -->
<?php if (!$isReadOnly): ?>
<div id="add-column-form" style="display:none;margin-top:12px;">
    <div class="settings-section">
        <h3 class="section-title"><?= icon('plus', 16) ?> Add New Column</h3>
        <div class="settings-grid">
            <div class="settings-field">
                <label class="settings-label">Column Name</label>
                <input type="text" id="new-col-name" class="settings-input" placeholder="column_name">
            </div>
            <div class="settings-field">
                <label class="settings-label">Type</label>
                <input type="text" id="new-col-type" class="settings-input" list="mysql-types" placeholder="VARCHAR(255)">
            </div>
            <div class="settings-field" style="flex:0.5;">
                <label class="settings-label">Null</label>
                <label class="settings-check" style="padding:8px 0;">
                    <input type="checkbox" id="new-col-null" checked> Allow NULL
                </label>
            </div>
        </div>
        <div class="settings-grid">
            <div class="settings-field">
                <label class="settings-label">Default Value</label>
                <div style="display:flex;gap:8px;align-items:center;">
                    <input type="text" id="new-col-default" class="settings-input" placeholder="Default value" style="flex:1;">
                    <label class="struct-check-mini">
                        <input type="checkbox" id="new-col-default-null" checked> NULL
                    </label>
                </div>
            </div>
            <div class="settings-field">
                <label class="settings-label">Comment</label>
                <input type="text" id="new-col-comment" class="settings-input" placeholder="Optional comment">
            </div>
            <div class="settings-field" style="flex:0.6;">
                <label class="settings-label">Position</label>
                <select id="new-col-after" class="settings-input">
                    <option value="">At end</option>
                    <option value="FIRST">First</option>
                    <?php foreach ($columns as $col): ?>
                    <option value="<?= h($col['Field']) ?>">After <?= h($col['Field']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div style="display:flex;gap:8px;margin-top:8px;">
            <button type="button" class="btn btn-primary btn-sm" id="add-column-submit">
                <?= icon('plus', 13) ?> Add Column
            </button>
            <button type="button" class="btn btn-ghost btn-sm" id="add-column-cancel">
                <?= icon('x', 13) ?> Cancel
            </button>
        </div>
    </div>
</div>
<?php endif; ?>

</div><!-- /structure-editor -->

<!-- MySQL Type Datalist -->
<datalist id="mysql-types">
    <option value="INT">
    <option value="INT UNSIGNED">
    <option value="TINYINT">
    <option value="TINYINT(1)">
    <option value="SMALLINT">
    <option value="MEDIUMINT">
    <option value="BIGINT">
    <option value="BIGINT UNSIGNED">
    <option value="FLOAT">
    <option value="DOUBLE">
    <option value="DECIMAL(10,2)">
    <option value="DECIMAL(15,2)">
    <option value="VARCHAR(50)">
    <option value="VARCHAR(100)">
    <option value="VARCHAR(150)">
    <option value="VARCHAR(255)">
    <option value="CHAR(1)">
    <option value="CHAR(36)">
    <option value="TEXT">
    <option value="TINYTEXT">
    <option value="MEDIUMTEXT">
    <option value="LONGTEXT">
    <option value="BLOB">
    <option value="MEDIUMBLOB">
    <option value="LONGBLOB">
    <option value="DATE">
    <option value="DATETIME">
    <option value="DATETIME DEFAULT CURRENT_TIMESTAMP">
    <option value="TIMESTAMP">
    <option value="TIMESTAMP DEFAULT CURRENT_TIMESTAMP">
    <option value="TIME">
    <option value="YEAR">
    <option value="BOOLEAN">
    <option value="ENUM('value1','value2')">
    <option value="SET('value1','value2')">
    <option value="JSON">
    <option value="BINARY(16)">
    <option value="VARBINARY(255)">
</datalist>

<!-- Keys & Relations -->
<?php if (!empty($foreignKeys) || !empty($referencedBy)): ?>
<?php
// Group FKs
$fkGrouped = [];
foreach ($foreignKeys as $fk) {
    $name = $fk['CONSTRAINT_NAME'];
    if (!isset($fkGrouped[$name])) {
        $fkGrouped[$name] = ['name' => $name, 'columns' => [], 'ref_table' => $fk['REFERENCED_TABLE_NAME'],
            'ref_schema' => $fk['REFERENCED_TABLE_SCHEMA'], 'ref_columns' => [],
            'on_update' => $fk['UPDATE_RULE'], 'on_delete' => $fk['DELETE_RULE']];
    }
    $fkGrouped[$name]['columns'][] = $fk['COLUMN_NAME'];
    $fkGrouped[$name]['ref_columns'][] = $fk['REFERENCED_COLUMN_NAME'];
}
// Group reverse refs
$refGrouped = [];
foreach ($referencedBy as $ref) {
    $name = $ref['CONSTRAINT_NAME'];
    if (!isset($refGrouped[$name])) {
        $refGrouped[$name] = ['table' => $ref['referencing_table'], 'columns' => [],
            'local_columns' => [], 'on_update' => $ref['UPDATE_RULE'], 'on_delete' => $ref['DELETE_RULE']];
    }
    $refGrouped[$name]['columns'][] = $ref['referencing_column'];
    $refGrouped[$name]['local_columns'][] = $ref['local_column'];
}
?>

<?php if (!empty($fkGrouped)): ?>
<h3 class="section-title" style="margin-top:20px;"><?= icon('external-link', 16) ?> Foreign Keys</h3>
<div class="table-wrapper">
    <table class="data-table">
        <thead>
            <tr>
                <th>Constraint</th>
                <th>Column</th>
                <th></th>
                <th>References</th>
                <th>On Delete</th>
                <th>On Update</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($fkGrouped as $fk): ?>
            <tr>
                <td style="font-family:var(--font-mono);font-size:var(--font-size-xs);color:var(--text-muted);"><?= h($fk['name']) ?></td>
                <td><code class="rel-col"><?= h(implode(', ', $fk['columns'])) ?></code></td>
                <td class="rel-arrow">→</td>
                <td>
                    <a href="?db=<?= urlencode($fk['ref_schema'] ?? $currentDb) ?>&table=<?= urlencode($fk['ref_table']) ?>&tab=structure" class="rel-table-link">
                        <?= icon('table', 12) ?> <?= h($fk['ref_table']) ?>
                    </a>
                    <code class="rel-col">.<?= h(implode(', .', $fk['ref_columns'])) ?></code>
                </td>
                <td><span class="rel-rule rel-rule-<?= strtolower($fk['on_delete']) ?>"><?= h($fk['on_delete']) ?></span></td>
                <td><span class="rel-rule rel-rule-<?= strtolower($fk['on_update']) ?>"><?= h($fk['on_update']) ?></span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php if (!empty($refGrouped)): ?>
<h3 class="section-title" style="margin-top:20px;"><?= icon('layers', 16) ?> Referenced By</h3>
<div class="table-wrapper">
    <table class="data-table">
        <thead>
            <tr>
                <th>Constraint</th>
                <th>From Table</th>
                <th></th>
                <th>This Column</th>
                <th>On Delete</th>
                <th>On Update</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($refGrouped as $cname => $ref): ?>
            <tr>
                <td style="font-family:var(--font-mono);font-size:var(--font-size-xs);color:var(--text-muted);"><?= h($cname) ?></td>
                <td>
                    <a href="?db=<?= urlencode($currentDb) ?>&table=<?= urlencode($ref['table']) ?>&tab=structure" class="rel-table-link">
                        <?= icon('table', 12) ?> <?= h($ref['table']) ?>
                    </a>
                    <code class="rel-col">.<?= h(implode(', .', $ref['columns'])) ?></code>
                </td>
                <td class="rel-arrow">→</td>
                <td><code class="rel-col"><?= h(implode(', ', $ref['local_columns'])) ?></code></td>
                <td><span class="rel-rule rel-rule-<?= strtolower($ref['on_delete']) ?>"><?= h($ref['on_delete']) ?></span></td>
                <td><span class="rel-rule rel-rule-<?= strtolower($ref['on_update']) ?>"><?= h($ref['on_update']) ?></span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php endif; ?>

<!-- Indexes -->
<h3 class="section-title" style="margin-top:24px;"><?= icon('key', 16) ?> Indexes</h3>
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
<h3 class="section-title" style="margin-top:24px;"><?= icon('code', 16) ?> Create Statement</h3>
<div class="code-block" id="create-statement-block"><code id="create-statement-code"><?= h($createSql) ?></code></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var el = document.getElementById('create-statement-code');
    if (!el || typeof DBForge === 'undefined') return;
    var raw = el.textContent;
    var tokens = DBForge.tokenize(raw);
    tokens = DBForge.resolveTableNames(tokens);
    el.innerHTML = DBForge.renderTokens(tokens);
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editor = document.getElementById('structure-editor');
    if (!editor || editor.dataset.readonly === '1') return;

    const db = editor.dataset.db;
    const table = editor.dataset.table;
    const csrf = editor.dataset.csrf;

    // ── Edit / Save / Cancel per row ──

    editor.querySelectorAll('.struct-edit-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const row = btn.closest('tr');
            row.classList.add('struct-editing');
            row.querySelectorAll('.struct-display').forEach(el => el.style.display = 'none');
            row.querySelectorAll('.struct-input, .struct-check, .struct-default-wrap, .struct-extra-select').forEach(el => el.style.display = '');
            row.querySelector('.struct-edit-btn').style.display = 'none';
            row.querySelector('.struct-save-btn').style.display = '';
            row.querySelector('.struct-cancel-btn').style.display = '';
            // Focus name input
            const nameInput = row.querySelector('[data-field="name"] .struct-input');
            if (nameInput) nameInput.focus();
        });
    });

    editor.querySelectorAll('.struct-cancel-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            window.location.reload();
        });
    });

    editor.querySelectorAll('.struct-save-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const row = btn.closest('tr');
            saveColumn(row);
        });
    });

    // Default NULL checkbox toggles default input
    editor.querySelectorAll('.struct-default-null').forEach(cb => {
        cb.addEventListener('change', () => {
            const input = cb.closest('.struct-default-wrap').querySelector('.struct-default-input');
            input.disabled = cb.checked;
            if (cb.checked) input.value = '';
        });
    });

    function saveColumn(row) {
        const origName = row.dataset.originalName;
        const newName = row.querySelector('[data-field="name"] .struct-input').value.trim();
        const newType = row.querySelector('[data-field="type"] .struct-input').value.trim();
        const nullable = row.querySelector('.struct-null-check').checked;
        const defaultNull = row.querySelector('.struct-default-null').checked;
        const defaultVal = defaultNull ? null : row.querySelector('.struct-default-input').value;
        const extra = row.querySelector('.struct-extra-select').value;
        const comment = row.querySelector('.struct-comment-input').value;

        if (!newName || !newType) {
            DBForge.setStatus('Column name and type are required.');
            return;
        }

        row.style.opacity = '0.5';

        const formData = new FormData();
        formData.append('action', 'alter_column');
        formData.append('db', db);
        formData.append('table', table);
        formData.append('original_name', origName);
        formData.append('new_name', newName);
        formData.append('new_type', newType);
        formData.append('nullable', nullable ? '1' : '0');
        formData.append('default_value', defaultVal ?? '');
        formData.append('default_null', defaultNull ? '1' : '0');
        formData.append('extra', extra);
        formData.append('comment', comment);
        formData.append('_csrf_token', csrf);

        fetch('ajax.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                row.style.opacity = '1';
                if (data.error) {
                    DBForge.setStatus('Error: ' + data.error);
                    return;
                }
                DBForge.setStatus('Column "' + newName + '" updated successfully.');
                window.location.reload();
            })
            .catch(err => {
                row.style.opacity = '1';
                DBForge.setStatus('Network error: ' + err.message);
            });
    }

    // ── Drop Column ──

    editor.querySelectorAll('.struct-drop-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const colName = btn.dataset.column;
            DBForge.confirm({
                title: 'Drop Column',
                message: 'Drop column "' + colName + '" from ' + table + '? This will delete all data in this column permanently.',
                confirmText: 'Drop Column',
                cancelText: 'Cancel',
                danger: true,
            }).then(ok => {
                if (!ok) return;
                const formData = new FormData();
                formData.append('action', 'drop_column');
                formData.append('db', db);
                formData.append('table', table);
                formData.append('column', colName);
                formData.append('_csrf_token', csrf);

                fetch('ajax.php', { method: 'POST', body: formData })
                    .then(r => r.json())
                    .then(data => {
                        if (data.error) {
                            DBForge.setStatus('Error: ' + data.error);
                            return;
                        }
                        DBForge.setStatus('Column "' + colName + '" dropped.');
                        window.location.reload();
                    });
            });
        });
    });

    // ── Add Column ──

    const addBtn = document.getElementById('add-column-btn');
    const addForm = document.getElementById('add-column-form');
    const addCancel = document.getElementById('add-column-cancel');
    const addSubmit = document.getElementById('add-column-submit');

    if (addBtn && addForm) {
        addBtn.addEventListener('click', () => {
            addForm.style.display = '';
            addBtn.style.display = 'none';
            document.getElementById('new-col-name').focus();
        });

        addCancel.addEventListener('click', () => {
            addForm.style.display = 'none';
            addBtn.style.display = '';
        });

        // Default NULL toggle for add form
        document.getElementById('new-col-default-null').addEventListener('change', function() {
            document.getElementById('new-col-default').disabled = this.checked;
            if (this.checked) document.getElementById('new-col-default').value = '';
        });

        addSubmit.addEventListener('click', () => {
            const name = document.getElementById('new-col-name').value.trim();
            const type = document.getElementById('new-col-type').value.trim();
            const nullable = document.getElementById('new-col-null').checked;
            const defaultNull = document.getElementById('new-col-default-null').checked;
            const defaultVal = defaultNull ? null : document.getElementById('new-col-default').value;
            const comment = document.getElementById('new-col-comment').value;
            const after = document.getElementById('new-col-after').value;

            if (!name || !type) {
                DBForge.setStatus('Column name and type are required.');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'add_column');
            formData.append('db', db);
            formData.append('table', table);
            formData.append('name', name);
            formData.append('type', type);
            formData.append('nullable', nullable ? '1' : '0');
            formData.append('default_value', defaultVal ?? '');
            formData.append('default_null', defaultNull ? '1' : '0');
            formData.append('comment', comment);
            formData.append('after', after);
            formData.append('_csrf_token', csrf);

            fetch('ajax.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.error) {
                        DBForge.setStatus('Error: ' + data.error);
                        return;
                    }
                    DBForge.setStatus('Column "' + name + '" added.');
                    window.location.reload();
                })
                .catch(err => {
                    DBForge.setStatus('Network error: ' + err.message);
                });
        });
    }

    // ── Keyboard: Enter saves, Escape cancels ──
    editor.addEventListener('keydown', function(e) {
        const row = e.target.closest('.struct-editing');
        if (!row) return;
        if (e.key === 'Enter') { e.preventDefault(); saveColumn(row); }
        if (e.key === 'Escape') { e.preventDefault(); window.location.reload(); }
    });

    // ── Auto Increment ──

    var setBtn = document.getElementById('auto-inc-set-btn');
    var resetBtn = document.getElementById('auto-inc-reset-btn');
    var incInput = document.getElementById('auto-inc-input');

    function setAutoInc(value) {
        var formData = new FormData();
        formData.append('action', 'set_auto_increment');
        formData.append('db', db);
        formData.append('table', table);
        formData.append('value', value);
        formData.append('_csrf_token', csrf);

        fetch('ajax.php', { method: 'POST', body: formData })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.error) {
                    DBForge.setStatus('Error: ' + data.error);
                    return;
                }
                DBForge.setStatus('AUTO_INCREMENT set to ' + data.new_value);
                window.location.reload();
            })
            .catch(function(err) {
                DBForge.setStatus('Network error: ' + err.message);
            });
    }

    if (setBtn) {
        setBtn.addEventListener('click', function() {
            var val = parseInt(incInput.value);
            if (isNaN(val) || val < 1) {
                DBForge.setStatus('AUTO_INCREMENT must be a positive number.');
                return;
            }
            DBForge.confirm({
                title: 'Set AUTO_INCREMENT',
                message: 'Set AUTO_INCREMENT to ' + val + '? This will affect the next inserted row\'s ID.',
                confirmText: 'Set to ' + val,
                cancelText: 'Cancel',
                danger: false,
            }).then(function(ok) {
                if (ok) setAutoInc(val);
            });
        });
    }

    if (resetBtn) {
        resetBtn.addEventListener('click', function() {
            DBForge.confirm({
                title: 'Reset AUTO_INCREMENT',
                message: 'Reset to MAX(' + '<?= h($autoIncCol ?? "id") ?>' + ') + 1? This closes any gaps in the ID sequence.',
                confirmText: 'Reset',
                cancelText: 'Cancel',
                danger: false,
            }).then(function(ok) {
                if (ok) setAutoInc(0); // 0 = server calculates reset
            });
        });
    }
});
</script>
