<aside class="sidebar" id="sidebar"
       data-spacing="<?= h($_COOKIE['dbforge_sidebar_spacing'] ?? 'normal') ?>"
       data-hide-counts="<?= h($_COOKIE['dbforge_hide_counts'] ?? '0') ?>">
    <div class="sidebar-header">
        <?= icon('database', 13) ?>
        Databases
        <div class="sidebar-header-actions">
            <button type="button" class="sidebar-gear-btn" id="sidebar-gear" title="Sidebar settings"><?= icon('settings', 11) ?></button>
            <?php if (!(isset($auth) && $auth->isReadOnly())): ?>
            <button type="button" class="sidebar-add-btn" id="sidebar-create-db" title="Create database"><?= icon('plus', 12) ?></button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Sidebar Settings (hidden) -->
    <div class="sidebar-settings" id="sidebar-settings" style="display:none;">
        <div class="sidebar-setting-row">
            <span class="sidebar-setting-label">Spacing</span>
            <div class="sidebar-setting-btns">
                <button type="button" class="sidebar-spacing-btn" data-spacing="compact" title="Compact">S</button>
                <button type="button" class="sidebar-spacing-btn" data-spacing="normal" title="Normal">M</button>
                <button type="button" class="sidebar-spacing-btn" data-spacing="expanded" title="Expanded">L</button>
            </div>
        </div>
        <div class="sidebar-setting-row">
            <label class="sidebar-setting-label sidebar-setting-check">
                <input type="checkbox" id="sidebar-hide-counts">
                Hide row counts
            </label>
        </div>
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
        <div class="db-group <?= $isExpanded ? 'db-group-expanded' : '' ?>">
            <div class="db-item <?= $isExpanded ? 'active' : '' ?>">
                <?php if ($isExpanded): ?>
                <button type="button" class="db-chevron open db-toggle" title="Collapse">
                    <?= icon('chevron-down', 12) ?>
                </button>
                <?php else: ?>
                <a href="?db=<?= urlencode($dbName) ?>&tab=browse" class="db-chevron" title="Expand">
                    <?= icon('chevron-right', 12) ?>
                </a>
                <?php endif; ?>
                <a href="?db=<?= urlencode($dbName) ?>&tab=browse" class="db-link">
                    <?= icon('database', 14, 'db-icon') ?>
                    <span class="db-name"><?= h($dbName) ?></span>
                </a>
                <?php if ($isExpanded): ?>
                <span class="db-count"><?= count($tables) ?></span>
                <?php endif; ?>
            </div>
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
