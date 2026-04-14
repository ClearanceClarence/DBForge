<?php
$sqlInput = '';
$queryResult = null;

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
                // Log query
                if (isset($auth)) {
                    $auth->logQuery($currentDb ?? 'mysql', $sqlInput, $queryResult['time'] ?? 0);
                }
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
        }
    }
}
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
