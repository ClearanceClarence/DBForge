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
            <button type="submit" class="btn btn-primary" id="run-btn">
                <?= icon('play', 13) ?> Execute
            </button>
        </div>
        <!-- Line numbers -->
        <div class="editor-line-numbers" id="editor-line-numbers" aria-hidden="true"></div>
    </div>
</form>

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
