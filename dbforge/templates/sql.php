<?php
$sqlInput = '';
$queryResult = null;
$maxHistory = (int)($config['app']['max_query_history'] ?? 50);

// POST submission takes priority (user edited and pressed execute)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sql'])) {
    // Validate CSRF
    if (isset($auth) && $auth->csrfEnabled() && !$auth->validateCsrf()) {
        $queryResult = ['success' => false, 'error' => 'Invalid security token. Please reload the page.', 'code' => '', 'time' => 0];
    } else {
        $sqlInput = $_POST['sql'];
        if (!empty($sqlInput)) {
            // Read-only enforcement
            if (isset($auth) && $auth->isReadOnly() && $auth->isWriteQuery($sqlInput)) {
                $queryResult = ['success' => false, 'error' => 'Write operations are disabled in read-only mode.', 'code' => '', 'time' => 0];
            } else {
                $queryResult = $dbInstance->executeQuery($currentDb ?? 'mysql', $sqlInput);
                if (isset($auth)) {
                    $auth->logQuery($currentDb ?? 'mysql', $sqlInput, $queryResult['time'] ?? 0);
                }
                // Record to history
                dbforge_record_history($sqlInput, $currentDb, $queryResult, $maxHistory);
            }
        }
    }
}
// GET with run=1 → auto-execute (from quick query links)
elseif (!empty($_GET['sql'])) {
    $sqlInput = $_GET['sql'];
    if (($_GET['run'] ?? '') === '1') {
        if (isset($auth) && $auth->isReadOnly() && $auth->isWriteQuery($sqlInput)) {
            $queryResult = ['success' => false, 'error' => 'Write operations are disabled in read-only mode.', 'code' => '', 'time' => 0];
        } else {
            $queryResult = $dbInstance->executeQuery($currentDb ?? 'mysql', $sqlInput);
            if (isset($auth)) {
                $auth->logQuery($currentDb ?? 'mysql', $sqlInput, $queryResult['time'] ?? 0);
            }
            dbforge_record_history($sqlInput, $currentDb, $queryResult, $maxHistory);
        }
    }
}

// History helper
function dbforge_record_history(string $sql, ?string $db, array $result, int $max): void
{
    if (!isset($_SESSION['dbforge_query_history'])) {
        $_SESSION['dbforge_query_history'] = [];
    }

    // Don't duplicate the exact same query at position 0
    if (!empty($_SESSION['dbforge_query_history']) && $_SESSION['dbforge_query_history'][0]['sql'] === $sql) {
        // Update the existing entry
        $_SESSION['dbforge_query_history'][0]['timestamp'] = time();
        $_SESSION['dbforge_query_history'][0]['db'] = $db;
        $_SESSION['dbforge_query_history'][0]['success'] = $result['success'];
        $_SESSION['dbforge_query_history'][0]['time'] = $result['time'] ?? 0;
        $_SESSION['dbforge_query_history'][0]['rows'] = $result['count'] ?? ($result['affected'] ?? 0);
        return;
    }

    array_unshift($_SESSION['dbforge_query_history'], [
        'sql'       => $sql,
        'db'        => $db,
        'success'   => $result['success'],
        'time'      => $result['time'] ?? 0,
        'rows'      => $result['count'] ?? ($result['affected'] ?? 0),
        'timestamp' => time(),
    ]);

    // Trim to max
    $_SESSION['dbforge_query_history'] = array_slice($_SESSION['dbforge_query_history'], 0, $max);
}

$queryHistory = $_SESSION['dbforge_query_history'] ?? [];
?>

<!-- Clean URL after auto-run so GET param doesn't interfere with future edits -->
<script>
if (window.location.search.includes('sql=')) {
    var clean = window.location.pathname + '?db=' + encodeURIComponent('<?= addslashes($currentDb ?? '') ?>') + '&tab=sql'
        <?php if ($currentTable): ?> + '&table=' + encodeURIComponent('<?= addslashes($currentTable) ?>') <?php endif; ?>;
    history.replaceState(null, '', clean);
}
</script>

<!-- SQL Header -->
<div class="sql-header">
    <span class="sql-db-label">
        <?= icon('database', 14) ?> <?= h($currentDb ?? 'No database selected') ?>
    </span>
    <span style="font-size:var(--font-size-xs);color:var(--text-muted);">Ctrl+Enter to execute</span>
</div>

<!-- Editor — always POSTs, never carries GET sql param -->
<form method="post" id="sql-form" action="?db=<?= urlencode($currentDb ?? '') ?>&tab=sql<?= $currentTable ? '&table=' . urlencode($currentTable) : '' ?>">
    <?php if (isset($auth)): ?><?= $auth->csrfField() ?><?php endif; ?>
    <input type="hidden" name="db" value="<?= h($currentDb ?? '') ?>">
    <input type="hidden" name="tab" value="sql">
    <?php if ($currentTable): ?>
    <input type="hidden" name="table" value="<?= h($currentTable) ?>">
    <?php endif; ?>
    <div class="editor-wrap">
        <div class="editor-container" id="editor-container">
            <!-- Highlighted backdrop -->
            <pre class="editor-backdrop" id="editor-backdrop" aria-hidden="true"><code id="editor-highlight"></code></pre>
            <!-- Actual textarea (transparent text, visible caret) -->
            <textarea
                name="sql"
                class="sql-editor"
                id="sql-editor"
                spellcheck="false"
                rows="8"
                placeholder="SELECT * FROM users WHERE ..."
            ><?= h($sqlInput) ?></textarea>
        </div>
        <div class="editor-footer">
            <div class="editor-hints">
                <span class="editor-hint"><kbd>Ctrl</kbd>+<kbd>Enter</kbd> Execute</span>
                <span class="editor-hint"><kbd>Tab</kbd> Indent</span>
                <span class="editor-hint"><kbd>Ctrl</kbd>+<kbd>Shift</kbd>+<kbd>S</kbd> Focus editor</span>
            </div>
            <div style="display:flex;gap:8px;">
                <button type="button" class="btn btn-ghost" id="explain-btn" title="EXPLAIN the current query">
                    <?= icon('activity', 13) ?> Explain
                </button>
                <button type="submit" class="btn btn-primary" id="run-btn">
                    <?= icon('play', 13) ?> Execute
                </button>
            </div>
        </div>
        <!-- Line numbers -->
        <div class="editor-line-numbers" id="editor-line-numbers" aria-hidden="true"></div>
    </div>
</form>

<!-- EXPLAIN Panel -->
<div class="explain-panel" id="explain-panel" style="display:none;">
    <div class="explain-header">
        <span class="explain-title"><?= icon('activity', 13) ?> Query Plan</span>
        <span class="explain-meta" id="explain-meta"></span>
        <button type="button" class="explain-close" id="explain-close" title="Close">&times;</button>
    </div>
    <div class="explain-body" id="explain-body">
        <!-- filled by JS -->
    </div>
</div>

<!-- Query History -->
<?php if (!empty($queryHistory)): ?>
<div class="history-panel" id="history-panel">
    <div class="history-header" id="history-toggle">
        <?= icon('clock', 14) ?>
        <span>Query History</span>
        <span class="history-count"><?= count($queryHistory) ?></span>
        <div class="history-header-actions">
            <input type="text" class="history-search" id="history-search" placeholder="Filter…" onclick="event.stopPropagation();">
            <button type="button" class="btn btn-ghost btn-sm history-clear-btn" id="history-clear" onclick="event.stopPropagation();" title="Clear history">
                <?= icon('trash', 11) ?>
            </button>
            <span class="history-chevron" id="history-chevron"><?= icon('chevron-down', 12) ?></span>
        </div>
    </div>
    <div class="history-list" id="history-list" style="display:none;">
        <?php foreach ($queryHistory as $i => $hq): ?>
        <div class="history-item <?= $hq['success'] ? '' : 'history-item-err' ?>" data-sql="<?= h($hq['sql']) ?>" data-idx="<?= $i ?>">
            <span class="history-item-status"><?= $hq['success'] ? icon('check', 10) : icon('x', 10) ?></span>
            <div class="history-item-body">
                <code class="history-item-sql" id="hq-<?= $i ?>"><?= h(truncate($hq['sql'], 150)) ?></code>
                <div class="history-item-meta">
                    <?php if ($hq['db']): ?>
                    <span class="history-item-db"><?= icon('database', 9) ?> <?= h($hq['db']) ?></span>
                    <?php endif; ?>
                    <span><?= number_format($hq['time'], 3) ?>s</span>
                    <?php if ($hq['rows']): ?>
                    <span><?= format_number($hq['rows']) ?> rows</span>
                    <?php endif; ?>
                    <span class="history-item-time" title="<?= date('Y-m-d H:i:s', $hq['timestamp']) ?>">
                        <?= dbforge_time_ago($hq['timestamp']) ?>
                    </span>
                </div>
            </div>
            <button type="button" class="history-delete-btn" data-idx="<?= $i ?>" title="Remove"><?= icon('x', 10) ?></button>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var toggle = document.getElementById('history-toggle');
    var list = document.getElementById('history-list');
    var chevron = document.getElementById('history-chevron');
    var search = document.getElementById('history-search');
    var clearBtn = document.getElementById('history-clear');

    if (!toggle || !list) return;

    // Toggle open/close
    toggle.addEventListener('click', function() {
        var open = list.style.display !== 'none';
        list.style.display = open ? 'none' : '';
        chevron.style.transform = open ? '' : 'rotate(180deg)';
    });

    // Syntax-highlight history SQL
    if (typeof DBForge !== 'undefined' && DBForge.tokenize) {
        list.querySelectorAll('.history-item-sql').forEach(function(el) {
            var tokens = DBForge.tokenize(el.textContent);
            tokens = DBForge.resolveTableNames(tokens);
            el.innerHTML = DBForge.renderTokens(tokens);
        });
    }

    // Click history item → load into editor (ignore delete button clicks)
    list.addEventListener('click', function(e) {
        if (e.target.closest('.history-delete-btn')) return;
        var item = e.target.closest('.history-item');
        if (!item) return;
        var sql = item.dataset.sql;
        var editor = document.getElementById('sql-editor');
        if (editor && sql) {
            editor.value = sql;
            editor.dispatchEvent(new Event('input'));
            editor.focus();
            editor.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });

    // Delete individual history item
    list.addEventListener('click', function(e) {
        var btn = e.target.closest('.history-delete-btn');
        if (!btn) return;
        var idx = parseInt(btn.dataset.idx);
        var item = btn.closest('.history-item');

        var fd = new FormData();
        fd.append('action', 'delete_history_item');
        fd.append('idx', idx);
        fd.append('_csrf_token', DBForge.getCsrfToken());
        fetch('ajax.php', { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    item.style.transition = 'opacity 0.15s, height 0.15s, padding 0.15s';
                    item.style.opacity = '0';
                    item.style.height = '0';
                    item.style.padding = '0 16px';
                    item.style.overflow = 'hidden';
                    setTimeout(function() { item.remove(); }, 160);

                    // Update count badge
                    var countEl = document.querySelector('.history-count');
                    if (countEl) {
                        var remaining = list.querySelectorAll('.history-item').length - 1;
                        countEl.textContent = remaining;
                        if (remaining === 0) {
                            document.getElementById('history-panel').remove();
                        }
                    }
                }
            });
    });

    // Filter
    if (search) {
        search.addEventListener('input', function() {
            var q = search.value.toLowerCase();
            list.querySelectorAll('.history-item').forEach(function(item) {
                var sql = item.dataset.sql.toLowerCase();
                item.style.display = (!q || sql.includes(q)) ? '' : 'none';
            });
        });
    }

    // Clear history
    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            if (typeof DBForge !== 'undefined' && DBForge.confirm) {
                DBForge.confirm({
                    title: 'Clear Query History',
                    message: 'This will remove all queries from your session history. This cannot be undone.',
                    confirmText: 'Clear',
                    cancelText: 'Cancel',
                    danger: true,
                }).then(function(ok) {
                    if (!ok) return;
                    doClear();
                });
            } else {
                doClear();
            }
        });

        function doClear() {
            var fd = new FormData();
            fd.append('action', 'clear_history');
            fd.append('_csrf_token', DBForge.getCsrfToken());
            fetch('ajax.php', { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success) {
                        document.getElementById('history-panel').remove();
                        DBForge.setStatus('Query history cleared.');
                    }
                });
        }
    }
});
</script>

<!-- Results -->
<?php if ($queryResult !== null): ?>
<div style="margin-top:16px;">
    <?php if (!$queryResult['success']): ?>
        <div class="error-box">
            <strong>ERROR <?= h($queryResult['code'] ?? '') ?>:</strong> <?= h($queryResult['error']) ?>
            <span style="float:right;color:var(--text-muted);"><?= $queryResult['time'] ?>s</span>
        </div>
    <?php elseif ($queryResult['type'] === 'select'): ?>
        <div class="result-meta">
            <?= format_number($queryResult['count']) ?> rows returned in <?= $queryResult['time'] ?>s
        </div>
        <?php if (!empty($queryResult['rows'])): ?>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <?php foreach ($queryResult['columns'] as $col): ?>
                        <th><?= h($col) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($queryResult['rows'] as $row): ?>
                    <tr>
                        <?php foreach ($queryResult['columns'] as $col): ?>
                        <td>
                            <?php if ($row[$col] === null): ?>
                            <span class="cell-null">NULL</span>
                            <?php else: ?>
                            <?= h(truncate(strval($row[$col]), 100)) ?>
                            <?php endif; ?>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div style="color:var(--text-muted);padding:20px;text-align:center;">Empty result set</div>
        <?php endif; ?>
    <?php else: ?>
        <div class="success-box">
            Query executed successfully. <?= format_number($queryResult['affected']) ?> row(s) affected in <?= $queryResult['time'] ?>s
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Quick Queries -->
<?php if ($currentTable): ?>
<div style="margin-top:24px;">
    <h4 class="section-title" style="font-size:var(--font-size-sm);">Quick Queries</h4>
    <div class="quick-queries">
        <?php
        $quickQueries = [
            "SELECT * FROM `{$currentTable}`",
            "SELECT * FROM `{$currentTable}` LIMIT 25",
            "SELECT COUNT(*) FROM `{$currentTable}`",
            "DESCRIBE `{$currentTable}`",
            "SHOW INDEX FROM `{$currentTable}`",
            "SHOW CREATE TABLE `{$currentTable}`",
        ];
        foreach ($quickQueries as $q):
        ?>
        <a href="#" class="quick-query" onclick="DBForge.loadAndRun(this.dataset.sql);return false;" data-sql="<?= h($q) ?>">
            <code><?= h($q) ?></code>
        </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<script>
(function() {
    var btn = document.getElementById('explain-btn');
    var panel = document.getElementById('explain-panel');
    var body = document.getElementById('explain-body');
    var meta = document.getElementById('explain-meta');
    var closeBtn = document.getElementById('explain-close');
    if (!btn || !panel) return;

    // Efficiency classification for the `type` column (access method)
    var typeClass = {
        'system': 'good', 'const': 'good', 'eq_ref': 'good',
        'ref': 'ok', 'ref_or_null': 'ok', 'range': 'ok', 'fulltext': 'ok',
        'index_merge': 'ok', 'unique_subquery': 'ok', 'index_subquery': 'ok',
        'index': 'warn',
        'ALL': 'bad'
    };

    // Extra notes — substrings that indicate warnings
    var extraWarnings = [
        'Using filesort', 'Using temporary', 'Using where; Using filesort',
        'Range checked for each record', 'Using join buffer'
    ];
    var extraGood = ['Using index', 'Using index condition', 'Using where'];

    function classifyExtra(extra) {
        if (!extra) return '';
        for (var i = 0; i < extraWarnings.length; i++) {
            if (extra.indexOf(extraWarnings[i]) !== -1) return 'warn';
        }
        for (var i = 0; i < extraGood.length; i++) {
            if (extra.indexOf(extraGood[i]) !== -1) return 'good';
        }
        return '';
    }

    function formatNum(n) {
        if (n == null) return '—';
        var num = parseInt(n, 10);
        if (isNaN(num)) return String(n);
        return num.toLocaleString();
    }

    function rowsCellClass(n) {
        var num = parseInt(n, 10);
        if (isNaN(num)) return '';
        if (num > 100000) return 'explain-rows-bad';
        if (num > 10000)  return 'explain-rows-warn';
        return '';
    }

    function renderResults(data) {
        if (data.error) {
            body.innerHTML = '<div class="explain-error">' + escapeHtml(data.error) + '</div>';
            meta.textContent = '';
            return;
        }
        if (!data.rows || !data.rows.length) {
            body.innerHTML = '<div class="explain-empty">No plan returned.</div>';
            return;
        }

        var cols = Object.keys(data.rows[0]);
        var html = '<table class="explain-table"><thead><tr>';
        cols.forEach(function(c) { html += '<th>' + escapeHtml(c) + '</th>'; });
        html += '</tr></thead><tbody>';

        data.rows.forEach(function(row) {
            html += '<tr>';
            cols.forEach(function(col) {
                var val = row[col];
                var cls = '';
                var display = val == null ? '<span class="explain-null">NULL</span>' : escapeHtml(String(val));

                if (col === 'type' && val) {
                    cls = 'explain-type explain-type-' + (typeClass[val] || '');
                    display = '<span class="' + cls + '">' + escapeHtml(val) + '</span>';
                    cls = '';
                } else if (col === 'Extra' && val) {
                    var extraCls = classifyExtra(val);
                    display = '';
                    val.split('; ').forEach(function(part) {
                        var partCls = classifyExtra(part) || extraCls;
                        display += '<span class="explain-extra-tag ' + (partCls ? 'explain-extra-' + partCls : '') + '">' + escapeHtml(part) + '</span>';
                    });
                } else if (col === 'rows' && val != null) {
                    cls = 'explain-rows ' + rowsCellClass(val);
                    display = formatNum(val);
                } else if (col === 'key' || col === 'possible_keys') {
                    if (val == null) {
                        display = '<span class="explain-null">—</span>';
                    } else {
                        display = '<code>' + escapeHtml(String(val)) + '</code>';
                    }
                } else if (col === 'table' && val) {
                    display = '<code class="explain-table-name">' + escapeHtml(val) + '</code>';
                } else if (col === 'id') {
                    cls = 'explain-id';
                }

                html += cls ? '<td class="' + cls + '">' : '<td>';
                html += display + '</td>';
            });
            html += '</tr>';
        });
        html += '</tbody></table>';

        // Legend
        html += '<div class="explain-legend">';
        html += '<span class="legend-item"><span class="legend-swatch good"></span> Efficient</span>';
        html += '<span class="legend-item"><span class="legend-swatch ok"></span> OK</span>';
        html += '<span class="legend-item"><span class="legend-swatch warn"></span> Worth reviewing</span>';
        html += '<span class="legend-item"><span class="legend-swatch bad"></span> Full scan / slow</span>';
        html += '</div>';

        body.innerHTML = html;
        meta.textContent = data.time + ' ms · ' + data.rows.length + ' step' + (data.rows.length === 1 ? '' : 's');
    }

    function escapeHtml(s) {
        return s.replace(/[&<>"']/g, function(c) {
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
        });
    }

    btn.addEventListener('click', function() {
        var editor = document.getElementById('sql-editor');
        if (!editor) return;
        // Use selected text if any, otherwise full editor
        var selStart = editor.selectionStart, selEnd = editor.selectionEnd;
        var sql = (selStart !== selEnd) ? editor.value.substring(selStart, selEnd) : editor.value;
        sql = sql.trim();
        if (!sql) { DBForge.setStatus('Nothing to explain.'); return; }

        btn.disabled = true;
        var orig = btn.innerHTML;
        btn.textContent = 'Explaining…';

        var fd = new FormData();
        fd.append('action', 'explain_query');
        fd.append('db', <?= json_encode($currentDb ?? '') ?>);
        fd.append('sql', sql);
        fd.append('_csrf_token', DBForge.getCsrfToken());

        fetch('ajax.php', { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                btn.disabled = false;
                btn.innerHTML = orig;
                panel.style.display = '';
                renderResults(data);
                panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            })
            .catch(function(err) {
                btn.disabled = false;
                btn.innerHTML = orig;
                renderResults({ error: err.message });
            });
    });

    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            panel.style.display = 'none';
        });
    }
})();
</script>
