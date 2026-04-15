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
    <?php if (!(isset($auth) && $auth->isReadOnly())): ?>
    <button type="button" class="btn btn-primary btn-sm" id="home-create-db-btn"><?= icon('plus', 13) ?> Create Database</button>
    <?php endif; ?>
</div>

<!-- Create Database Form (hidden) -->
<?php if (!(isset($auth) && $auth->isReadOnly())): ?>
<div id="create-db-form" class="create-db-form" style="display:none;">
    <div class="settings-section">
        <h3 class="section-title"><?= icon('plus', 16) ?> Create New Database</h3>
        <div class="settings-grid">
            <div class="settings-field">
                <label class="settings-label">Database Name</label>
                <input type="text" id="create-db-name" class="settings-input" placeholder="my_database"
                       pattern="[a-zA-Z0-9_\-]+" title="Letters, numbers, underscores, hyphens only">
            </div>
            <div class="settings-field">
                <label class="settings-label">Character Set</label>
                <select id="create-db-charset" class="settings-input">
                    <option value="utf8mb4" selected>utf8mb4 (recommended)</option>
                    <option value="utf8">utf8</option>
                    <option value="latin1">latin1</option>
                    <option value="ascii">ascii</option>
                    <option value="binary">binary</option>
                </select>
            </div>
            <div class="settings-field">
                <label class="settings-label">Collation</label>
                <select id="create-db-collation" class="settings-input">
                    <option value="utf8mb4_general_ci" selected>utf8mb4_general_ci</option>
                    <option value="utf8mb4_unicode_ci">utf8mb4_unicode_ci</option>
                    <option value="utf8mb4_bin">utf8mb4_bin</option>
                    <option value="utf8_general_ci">utf8_general_ci</option>
                    <option value="latin1_swedish_ci">latin1_swedish_ci</option>
                </select>
            </div>
        </div>
        <div style="display:flex;gap:8px;margin-top:10px;">
            <button type="button" class="btn btn-primary btn-sm" id="create-db-submit">
                <?= icon('plus', 13) ?> Create Database
            </button>
            <button type="button" class="btn btn-ghost btn-sm" id="create-db-cancel">
                <?= icon('x', 13) ?> Cancel
            </button>
        </div>
    </div>
</div>
<?php endif; ?>

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
                        <?php if (!(isset($auth) && $auth->isReadOnly()) && !in_array(strtolower($stat['name']), ['mysql','information_schema','performance_schema','sys'])): ?>
                        <button type="button" class="btn btn-danger btn-sm drop-db-btn" data-db="<?= h($stat['name']) ?>" title="Drop database" style="padding:2px 6px;"><?= icon('trash', 12) ?></button>
                        <?php endif; ?>
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
    <?php if (!(isset($auth) && $auth->isReadOnly())): ?>
    <button type="button" class="btn btn-primary btn-sm" id="create-table-btn"><?= icon('plus', 13) ?> Create Table</button>
    <?php endif; ?>
    <a href="?db=<?= urlencode($currentDb) ?>&action=export_db" class="btn btn-ghost btn-sm"><?= icon('download', 13) ?> Export Database</a>
    <a href="?tab=server" class="btn btn-ghost btn-sm"><?= icon('settings', 13) ?> Server Info</a>
</div>

<!-- Create Table Form -->
<?php if (!(isset($auth) && $auth->isReadOnly())): ?>
<div id="create-table-form" style="display:none;margin-bottom:20px;">
    <div class="settings-section">
        <h3 class="section-title"><?= icon('plus', 16) ?> Create New Table</h3>

        <!-- Table Options -->
        <div class="settings-grid">
            <div class="settings-field">
                <label class="settings-label">Table Name</label>
                <input type="text" id="ct-name" class="settings-input" placeholder="my_table">
            </div>
            <div class="settings-field" style="flex:0.6;">
                <label class="settings-label">Engine</label>
                <select id="ct-engine" class="settings-input">
                    <option value="InnoDB" selected>InnoDB</option>
                    <option value="MyISAM">MyISAM</option>
                    <option value="MEMORY">MEMORY</option>
                    <option value="ARCHIVE">ARCHIVE</option>
                </select>
            </div>
            <div class="settings-field" style="flex:0.8;">
                <label class="settings-label">Collation</label>
                <select id="ct-collation" class="settings-input">
                    <option value="utf8mb4_general_ci" selected>utf8mb4_general_ci</option>
                    <option value="utf8mb4_unicode_ci">utf8mb4_unicode_ci</option>
                    <option value="utf8mb4_bin">utf8mb4_bin</option>
                    <option value="utf8_general_ci">utf8_general_ci</option>
                    <option value="latin1_swedish_ci">latin1_swedish_ci</option>
                </select>
            </div>
            <div class="settings-field">
                <label class="settings-label">Comment</label>
                <input type="text" id="ct-comment" class="settings-input" placeholder="Optional">
            </div>
        </div>

        <!-- Column Definitions -->
        <div class="ct-columns-header">
            <span class="settings-label" style="margin:0;">Columns</span>
            <button type="button" class="btn btn-ghost btn-sm" id="ct-add-col"><?= icon('plus', 12) ?> Add Column</button>
        </div>

        <div class="table-wrapper" style="margin-top:8px;">
            <table class="data-table" id="ct-columns-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th style="width:50px;">Null</th>
                        <th>Default</th>
                        <th style="width:40px;" title="Primary Key">PK</th>
                        <th style="width:40px;" title="Auto Increment">AI</th>
                        <th style="width:40px;" title="Unique">UQ</th>
                        <th style="width:40px;" title="Index">IDX</th>
                        <th style="width:36px;"></th>
                    </tr>
                </thead>
                <tbody id="ct-columns-body">
                    <!-- Rows added by JS -->
                </tbody>
            </table>
        </div>

        <!-- SQL Preview -->
        <details class="ct-preview" style="margin-top:14px;">
            <summary style="cursor:pointer;font-size:var(--font-size-xs);font-weight:600;color:var(--text-muted);">
                <?= icon('code', 12) ?> SQL Preview
            </summary>
            <div class="code-block" id="ct-sql-preview" style="margin-top:8px;font-size:var(--font-size-xs);min-height:40px;"></div>
        </details>

        <!-- Actions -->
        <div style="display:flex;gap:8px;margin-top:14px;">
            <button type="button" class="btn btn-primary" id="ct-submit">
                <?= icon('plus', 14) ?> Create Table
            </button>
            <button type="button" class="btn btn-ghost" id="ct-cancel">
                <?= icon('x', 14) ?> Cancel
            </button>
        </div>
    </div>
</div>

<!-- MySQL Type Datalist for Create Table -->
<datalist id="ct-types">
    <option value="INT"><option value="INT UNSIGNED"><option value="TINYINT"><option value="TINYINT(1)">
    <option value="SMALLINT"><option value="MEDIUMINT"><option value="BIGINT"><option value="BIGINT UNSIGNED">
    <option value="FLOAT"><option value="DOUBLE"><option value="DECIMAL(10,2)"><option value="DECIMAL(15,2)">
    <option value="VARCHAR(50)"><option value="VARCHAR(100)"><option value="VARCHAR(150)"><option value="VARCHAR(255)">
    <option value="CHAR(1)"><option value="CHAR(36)">
    <option value="TEXT"><option value="TINYTEXT"><option value="MEDIUMTEXT"><option value="LONGTEXT">
    <option value="BLOB"><option value="MEDIUMBLOB"><option value="LONGBLOB">
    <option value="DATE"><option value="DATETIME"><option value="TIMESTAMP"><option value="TIME"><option value="YEAR">
    <option value="BOOLEAN"><option value="ENUM('')"><option value="SET('')"><option value="JSON">
    <option value="BINARY(16)"><option value="VARBINARY(255)">
</datalist>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('create-table-form');
    var btn = document.getElementById('create-table-btn');
    if (!form || !btn) return;

    var db = '<?= addslashes($currentDb) ?>';
    var csrf = DBForge.getCsrfToken();
    var colIndex = 0;

    // Toggle form
    btn.addEventListener('click', function() {
        form.style.display = '';
        btn.style.display = 'none';
        if (!document.querySelectorAll('#ct-columns-body tr').length) {
            addColumnRow(true); // First column: id, INT, PK, AI
            addColumnRow();
        }
        document.getElementById('ct-name').focus();
    });
    document.getElementById('ct-cancel').addEventListener('click', function() {
        form.style.display = 'none';
        btn.style.display = '';
    });

    // Add column
    document.getElementById('ct-add-col').addEventListener('click', function() { addColumnRow(); });

    function addColumnRow(isFirst) {
        var idx = colIndex++;
        var tr = document.createElement('tr');
        tr.dataset.idx = idx;
        tr.innerHTML =
            '<td><input type="text" class="ct-input ct-col-name" data-idx="'+idx+'" placeholder="column_name" value="'+(isFirst?'id':'')+'"></td>' +
            '<td><input type="text" class="ct-input ct-col-type" data-idx="'+idx+'" list="ct-types" placeholder="VARCHAR(255)" value="'+(isFirst?'INT UNSIGNED':'')+'"></td>' +
            '<td style="text-align:center;"><input type="checkbox" class="ct-col-null" data-idx="'+idx+'"'+(isFirst?'':' checked')+'></td>' +
            '<td><input type="text" class="ct-input ct-col-default" data-idx="'+idx+'" placeholder="NULL" style="font-size:11px;"></td>' +
            '<td style="text-align:center;"><input type="checkbox" class="ct-col-pk" data-idx="'+idx+'"'+(isFirst?' checked':'')+'></td>' +
            '<td style="text-align:center;"><input type="checkbox" class="ct-col-ai" data-idx="'+idx+'"'+(isFirst?' checked':'')+'></td>' +
            '<td style="text-align:center;"><input type="checkbox" class="ct-col-uq" data-idx="'+idx+'"></td>' +
            '<td style="text-align:center;"><input type="checkbox" class="ct-col-idx" data-idx="'+idx+'"></td>' +
            '<td style="text-align:center;"><button type="button" class="btn btn-danger btn-sm ct-remove-col" style="padding:1px 5px;">×</button></td>';
        document.getElementById('ct-columns-body').appendChild(tr);

        tr.querySelector('.ct-remove-col').addEventListener('click', function() {
            tr.remove();
            updatePreview();
        });

        // Auto-uncheck null when PK is checked
        tr.querySelector('.ct-col-pk').addEventListener('change', function() {
            if (this.checked) tr.querySelector('.ct-col-null').checked = false;
            updatePreview();
        });
        // Auto-check PK when AI is checked
        tr.querySelector('.ct-col-ai').addEventListener('change', function() {
            if (this.checked) {
                tr.querySelector('.ct-col-pk').checked = true;
                tr.querySelector('.ct-col-null').checked = false;
            }
            updatePreview();
        });

        // Update preview on any change
        tr.querySelectorAll('input').forEach(function(inp) {
            inp.addEventListener('input', updatePreview);
            inp.addEventListener('change', updatePreview);
        });

        updatePreview();
    }

    // Build SQL preview
    function buildSql() {
        var name = document.getElementById('ct-name').value.trim();
        var engine = document.getElementById('ct-engine').value;
        var collation = document.getElementById('ct-collation').value;
        var comment = document.getElementById('ct-comment').value.trim();

        if (!name) return '-- Enter a table name';

        var rows = document.querySelectorAll('#ct-columns-body tr');
        if (!rows.length) return '-- Add at least one column';

        var cols = [];
        var pks = [];
        var uqs = [];
        var idxs = [];

        rows.forEach(function(row) {
            var cName = row.querySelector('.ct-col-name').value.trim();
            var cType = row.querySelector('.ct-col-type').value.trim();
            if (!cName || !cType) return;

            var nullable = row.querySelector('.ct-col-null').checked;
            var def = row.querySelector('.ct-col-default').value.trim();
            var pk = row.querySelector('.ct-col-pk').checked;
            var ai = row.querySelector('.ct-col-ai').checked;
            var uq = row.querySelector('.ct-col-uq').checked;
            var idx = row.querySelector('.ct-col-idx').checked;

            var line = '  `' + cName + '` ' + cType;
            line += nullable ? ' NULL' : ' NOT NULL';
            if (ai) line += ' AUTO_INCREMENT';
            else if (def && def.toUpperCase() !== 'NULL') {
                if (def.toUpperCase() === 'CURRENT_TIMESTAMP') line += ' DEFAULT CURRENT_TIMESTAMP';
                else line += " DEFAULT '" + def.replace(/'/g, "\\'") + "'";
            } else if (nullable && !def) {
                line += ' DEFAULT NULL';
            }
            cols.push(line);

            if (pk) pks.push('`' + cName + '`');
            if (uq) uqs.push(cName);
            if (idx) idxs.push(cName);
        });

        if (!cols.length) return '-- Define at least one column';

        if (pks.length) cols.push('  PRIMARY KEY (' + pks.join(', ') + ')');
        uqs.forEach(function(c) { cols.push('  UNIQUE KEY `uq_' + c + '` (`' + c + '`)'); });
        idxs.forEach(function(c) { cols.push('  KEY `idx_' + c + '` (`' + c + '`)'); });

        var sql = 'CREATE TABLE `' + name + '` (\n' + cols.join(',\n') + '\n)';
        sql += ' ENGINE=' + engine;
        sql += ' DEFAULT CHARSET=' + collation.split('_')[0];
        sql += ' COLLATE=' + collation;
        if (comment) sql += " COMMENT='" + comment.replace(/'/g, "\\'") + "'";
        sql += ';';
        return sql;
    }

    function updatePreview() {
        var el = document.getElementById('ct-sql-preview');
        var sql = buildSql();
        // Use tokenizer if available
        if (typeof DBForge !== 'undefined' && DBForge.tokenize) {
            var tokens = DBForge.tokenize(sql);
            tokens = DBForge.resolveTableNames(tokens);
            el.innerHTML = '<code>' + DBForge.renderTokens(tokens) + '</code>';
        } else {
            el.textContent = sql;
        }
    }

    // Watch table options for preview updates
    ['ct-name','ct-engine','ct-collation','ct-comment'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) { el.addEventListener('input', updatePreview); el.addEventListener('change', updatePreview); }
    });

    // Submit
    document.getElementById('ct-submit').addEventListener('click', function() {
        var sql = buildSql();
        if (sql.startsWith('--')) {
            DBForge.setStatus(sql.replace('-- ', ''));
            return;
        }

        var formData = new FormData();
        formData.append('action', 'execute_sql');
        formData.append('db', db);
        formData.append('sql', sql);
        formData.append('_csrf_token', csrf);

        fetch('ajax.php', { method: 'POST', body: formData })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.error) {
                    DBForge.setStatus('Error: ' + data.error);
                    return;
                }
                var tblName = document.getElementById('ct-name').value.trim();
                DBForge.setStatus('Table "' + tblName + '" created.');
                window.location.href = '?db=' + encodeURIComponent(db) + '&table=' + encodeURIComponent(tblName) + '&tab=structure';
            })
            .catch(function(err) { DBForge.setStatus('Network error: ' + err.message); });
    });

    // Enter on table name focuses first column name
    document.getElementById('ct-name').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            var first = document.querySelector('#ct-columns-body .ct-col-name');
            if (first) first.focus();
        }
    });
});
</script>
<?php endif; ?>

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
        <?php if (!(isset($auth) && $auth->isReadOnly())): ?>
        <button type="button" class="btn btn-primary btn-sm" id="insert-row-btn">
            <?= icon('plus', 13) ?> Insert Row
        </button>
        <?php endif; ?>
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

<!-- Insert Row Form (hidden by default) -->
<?php if (!(isset($auth) && $auth->isReadOnly())): ?>
<div id="insert-row-form" style="display:none;" class="insert-row-form">
    <div class="insert-row-card">
        <div class="insert-row-header">
            <?= icon('plus', 16) ?>
            <span>Insert Row into <strong><?= h($currentTable) ?></strong></span>
            <button type="button" class="btn btn-ghost btn-sm" id="insert-row-cancel" style="margin-left:auto;"><?= icon('x', 12) ?> Close</button>
        </div>
        <div class="insert-row-body">
            <?php foreach ($columns as $col):
                $field = $col['Field'];
                $type = strtolower($col['Type']);
                $isAI = stripos($col['Extra'], 'auto_increment') !== false;
                $isNullable = $col['Null'] === 'YES';
                $hasDefault = $col['Default'] !== null;
                $defaultVal = $col['Default'] ?? '';
                $isPK = $col['Key'] === 'PRI';

                // Determine input type
                $inputType = 'text';
                $isTextarea = false;
                $placeholder = $type;
                if (preg_match('/^(text|mediumtext|longtext|tinytext|blob|mediumblob|longblob|json)/', $type)) {
                    $isTextarea = true;
                } elseif (preg_match('/^(int|smallint|mediumint|bigint|tinyint)/', $type)) {
                    $inputType = 'number';
                    $placeholder = $isAI ? 'AUTO' : '0';
                } elseif (preg_match('/^(float|double|decimal|numeric)/', $type)) {
                    $inputType = 'number';
                    $placeholder = '0.00';
                } elseif (preg_match('/^date$/', $type)) {
                    $inputType = 'date';
                    $placeholder = 'YYYY-MM-DD';
                } elseif (preg_match('/^datetime|^timestamp/', $type)) {
                    $inputType = 'datetime-local';
                    $placeholder = 'YYYY-MM-DD HH:MM:SS';
                } elseif (preg_match('/^time$/', $type)) {
                    $inputType = 'time';
                    $placeholder = 'HH:MM:SS';
                } elseif (preg_match('/^year/', $type)) {
                    $inputType = 'number';
                    $placeholder = date('Y');
                } elseif (preg_match('/^enum\((.+)\)/', $type, $enumMatch)) {
                    $inputType = 'enum';
                }
            ?>
            <div class="insert-field" data-col="<?= h($field) ?>">
                <div class="insert-field-label">
                    <span class="insert-field-name"><?= h($field) ?></span>
                    <span class="insert-field-type"><?= h($col['Type']) ?></span>
                    <?php if ($isPK): ?><span class="key-badge key-badge-pk" style="font-size:9px;padding:0 4px;"><?= icon('key', 9) ?> PK</span><?php endif; ?>
                    <?php if ($isAI): ?><span class="insert-field-ai"><?= icon('zap', 10) ?> AI</span><?php endif; ?>
                </div>
                <div class="insert-field-input">
                    <?php if ($inputType === 'enum'):
                        // Parse enum values
                        preg_match_all("/'([^']+)'/", $col['Type'], $enumVals);
                    ?>
                    <select class="insert-input" data-col="<?= h($field) ?>" <?= $isAI ? 'disabled' : '' ?>>
                        <?php if ($isNullable): ?><option value="__NULL__">NULL</option><?php endif; ?>
                        <?php foreach ($enumVals[1] ?? [] as $ev): ?>
                        <option value="<?= h($ev) ?>" <?= $defaultVal === $ev ? 'selected' : '' ?>><?= h($ev) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php elseif ($isTextarea): ?>
                    <textarea class="insert-input insert-input-large" data-col="<?= h($field) ?>"
                              placeholder="<?= h($placeholder) ?>" <?= $isAI ? 'disabled' : '' ?>><?= $hasDefault ? h($defaultVal) : '' ?></textarea>
                    <?php else: ?>
                    <input type="<?= $inputType ?>" class="insert-input" data-col="<?= h($field) ?>"
                           placeholder="<?= h($placeholder) ?>"
                           value="<?= $hasDefault && !$isAI ? h($defaultVal) : '' ?>"
                           <?= $isAI ? 'disabled' : '' ?>
                           <?= $inputType === 'number' ? 'step="any"' : '' ?>>
                    <?php endif; ?>

                    <div class="insert-field-options">
                        <?php if ($isAI): ?>
                        <label class="insert-field-check" title="Auto-generated. Check to set manually.">
                            <input type="checkbox" class="insert-ai-override" data-col="<?= h($field) ?>">
                            <span>Manual</span>
                        </label>
                        <?php endif; ?>
                        <?php if ($isNullable && $inputType !== 'enum'): ?>
                        <label class="insert-field-check">
                            <input type="checkbox" class="insert-null-check" data-col="<?= h($field) ?>" <?= (!$hasDefault && !$isAI) ? 'checked' : '' ?>>
                            <span>NULL</span>
                        </label>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="insert-row-footer">
            <button type="button" class="btn btn-primary" id="insert-row-submit">
                <?= icon('plus', 14) ?> Insert Row
            </button>
            <label class="insert-field-check">
                <input type="checkbox" id="insert-another" checked>
                <span>Insert another after</span>
            </label>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var formEl = document.getElementById('insert-row-form');
    var btn = document.getElementById('insert-row-btn');
    if (!formEl || !btn) return;

    var db = '<?= addslashes($currentDb) ?>';
    var table = '<?= addslashes($currentTable) ?>';
    var csrf = DBForge.getCsrfToken();

    // Toggle
    btn.addEventListener('click', function() {
        formEl.style.display = '';
        btn.style.display = 'none';
        var firstInput = formEl.querySelector('.insert-input:not([disabled])');
        if (firstInput) firstInput.focus();
    });
    document.getElementById('insert-row-cancel').addEventListener('click', function() {
        formEl.style.display = 'none';
        btn.style.display = '';
    });

    // AI override toggles
    formEl.querySelectorAll('.insert-ai-override').forEach(function(cb) {
        cb.addEventListener('change', function() {
            var col = cb.dataset.col;
            var input = formEl.querySelector('.insert-input[data-col="' + col + '"]');
            if (input) {
                input.disabled = !cb.checked;
                if (!cb.checked) input.value = '';
            }
        });
    });

    // NULL checkbox toggles
    formEl.querySelectorAll('.insert-null-check').forEach(function(cb) {
        cb.addEventListener('change', function() {
            var col = cb.dataset.col;
            var input = formEl.querySelector('.insert-input[data-col="' + col + '"]');
            if (input) {
                input.disabled = cb.checked;
                if (cb.checked) { if (input.tagName === 'TEXTAREA') input.value = ''; else input.value = ''; }
                else input.focus();
            }
        });
        // Apply initial state
        if (cb.checked) {
            var col = cb.dataset.col;
            var input = formEl.querySelector('.insert-input[data-col="' + col + '"]');
            if (input) input.disabled = true;
        }
    });

    // Submit
    document.getElementById('insert-row-submit').addEventListener('click', function() {
        var data = {};
        var fields = formEl.querySelectorAll('.insert-field');

        fields.forEach(function(f) {
            var col = f.dataset.col;
            var input = f.querySelector('.insert-input');
            var nullCb = f.querySelector('.insert-null-check');
            var aiCb = f.querySelector('.insert-ai-override');
            var isAI = !!aiCb;

            // Skip AI columns unless manually set
            if (isAI && !aiCb.checked) return;
            // NULL
            if (nullCb && nullCb.checked) { data[col] = null; return; }
            // Disabled (shouldn't happen outside AI)
            if (input.disabled) return;

            var val = input.value;
            // Enum NULL option
            if (input.tagName === 'SELECT' && val === '__NULL__') { data[col] = null; return; }

            data[col] = val;
        });

        var formData = new FormData();
        formData.append('action', 'insert_row');
        formData.append('db', db);
        formData.append('table', table);
        formData.append('data', JSON.stringify(data));
        formData.append('_csrf_token', csrf);

        fetch('ajax.php', { method: 'POST', body: formData })
            .then(function(r) { return r.json(); })
            .then(function(result) {
                if (result.error) {
                    DBForge.setStatus('Insert error: ' + result.error);
                    return;
                }
                DBForge.setStatus('Row inserted' + (result.insert_id ? ' (ID: ' + result.insert_id + ')' : '') + '.');

                if (document.getElementById('insert-another').checked) {
                    // Clear non-default, non-AI fields
                    fields.forEach(function(f) {
                        var input = f.querySelector('.insert-input');
                        var aiCb = f.querySelector('.insert-ai-override');
                        if (aiCb || input.disabled) return;
                        if (input.tagName === 'SELECT') return;
                        if (input.dataset.hasDefault) return;
                        input.value = '';
                    });
                    var firstInput = formEl.querySelector('.insert-input:not([disabled])');
                    if (firstInput) firstInput.focus();
                } else {
                    window.location.reload();
                }
            })
            .catch(function(err) { DBForge.setStatus('Network error: ' + err.message); });
    });

    // Enter key submits (except in textarea)
    formEl.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
            e.preventDefault();
            document.getElementById('insert-row-submit').click();
        }
    });
});
</script>
<?php endif; ?>

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
