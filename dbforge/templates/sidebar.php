<aside class="sidebar">
    <div class="sidebar-header">
        <?= icon('database', 13) ?>
        Databases
    </div>
    <div class="sidebar-content">
        <?php foreach ($databases as $dbName): ?>
        <?php
            $isExpanded = ($dbName === $currentDb);
            $tables = [];
            if ($isExpanded) {
                try {
                    $tables = $dbInstance->getTables($dbName);
                } catch (Exception $e) {
                    $tables = [];
                }
            }
        ?>
        <div class="db-group">
            <a href="?db=<?= urlencode($dbName) ?>&tab=browse" class="db-item <?= $isExpanded ? 'active' : '' ?>">
                <span class="db-chevron <?= $isExpanded ? 'open' : '' ?>"><?= icon($isExpanded ? 'chevron-down' : 'chevron-right', 12) ?></span>
                <?= icon('database', 14, 'db-icon') ?>
                <span class="db-name"><?= h($dbName) ?></span>
                <?php if ($isExpanded): ?>
                <span class="db-count"><?= count($tables) ?></span>
                <?php endif; ?>
            </a>
            <?php if ($isExpanded && !empty($tables)): ?>
            <div class="table-list">
                <?php foreach ($tables as $tbl): ?>
                <?php
                    $tblName = $tbl['Name'];
                    try {
                        $exactCount = $dbInstance->getExactRowCount($dbName, $tblName);
                    } catch (Exception $e) {
                        $exactCount = $tbl['Rows'] ?? 0;
                    }
                ?>
                <a href="?db=<?= urlencode($dbName) ?>&table=<?= urlencode($tblName) ?>&tab=browse"
                   class="table-item <?= $tblName === $currentTable ? 'active' : '' ?>">
                    <?= icon('table', 13, 'table-icon') ?>
                    <span class="table-name"><?= h($tblName) ?></span>
                    <span class="row-count"><?= format_number($exactCount) ?></span>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</aside>
