<?php
try {
    $serverInfo = $dbInstance->getServerInfo();
} catch (Exception $e) {
    echo '<div class="error-box"><strong>ERROR:</strong> ' . h($e->getMessage()) . '</div>';
    return;
}

$vars = $serverInfo['variables'];
$status = $serverInfo['status'];
$uptime = (int)($status['Uptime'] ?? 0);
?>

<h3 class="section-title">Server Overview</h3>

<div class="info-grid">
    <div class="info-card">
        <div class="info-label">MySQL Version</div>
        <div class="info-value accent"><?= h($serverInfo['version']) ?></div>
    </div>
    <div class="info-card">
        <div class="info-label">Server</div>
        <div class="info-value warning"><?= h($vars['hostname'] ?? php_uname('n')) ?></div>
    </div>
    <div class="info-card">
        <div class="info-label">Port</div>
        <div class="info-value info"><?= h($vars['port'] ?? '3306') ?></div>
    </div>
    <div class="info-card">
        <div class="info-label">Uptime</div>
        <div class="info-value purple"><?= format_uptime($uptime) ?></div>
    </div>
    <div class="info-card">
        <div class="info-label">Character Set</div>
        <div class="info-value muted"><?= h($vars['character_set_server'] ?? '—') ?></div>
    </div>
    <div class="info-card">
        <div class="info-label">Collation</div>
        <div class="info-value muted"><?= h($vars['collation_server'] ?? '—') ?></div>
    </div>
    <div class="info-card">
        <div class="info-label">Data Directory</div>
        <div class="info-value muted" style="font-size:var(--font-size-sm);word-break:break-all;"><?= h($vars['datadir'] ?? '—') ?></div>
    </div>
    <div class="info-card">
        <div class="info-label">Max Connections</div>
        <div class="info-value gold"><?= h($vars['max_connections'] ?? '—') ?></div>
    </div>
    <div class="info-card">
        <div class="info-label">Active Connections</div>
        <div class="info-value accent"><?= h($status['Threads_connected'] ?? '—') ?></div>
    </div>
    <div class="info-card">
        <div class="info-label">Total Queries</div>
        <div class="info-value info"><?= format_number($status['Questions'] ?? 0) ?></div>
    </div>
    <div class="info-card">
        <div class="info-label">InnoDB Buffer Pool</div>
        <div class="info-value purple"><?= format_bytes((int)($vars['innodb_buffer_pool_size'] ?? 0)) ?></div>
    </div>
    <div class="info-card">
        <div class="info-label">Traffic In / Out</div>
        <div class="info-value muted" style="font-size:var(--font-size-sm);">
            <?= format_bytes((int)($status['Bytes_received'] ?? 0)) ?> / <?= format_bytes((int)($status['Bytes_sent'] ?? 0)) ?>
        </div>
    </div>
</div>

<!-- PHP Info -->
<h3 class="section-title" style="margin-top:24px;">PHP Environment</h3>
<div class="info-grid">
    <div class="info-card">
        <div class="info-label">PHP Version</div>
        <div class="info-value accent"><?= PHP_VERSION ?></div>
    </div>
    <div class="info-card">
        <div class="info-label">OS</div>
        <div class="info-value muted" style="font-size:var(--font-size-sm);"><?= h(php_uname('s') . ' ' . php_uname('r')) ?></div>
    </div>
    <div class="info-card">
        <div class="info-label">SAPI</div>
        <div class="info-value info"><?= h(php_sapi_name()) ?></div>
    </div>
    <div class="info-card">
        <div class="info-label">PDO Drivers</div>
        <div class="info-value purple"><?= h(implode(', ', PDO::getAvailableDrivers())) ?></div>
    </div>
    <div class="info-card">
        <div class="info-label">Memory Limit</div>
        <div class="info-value warning"><?= h(ini_get('memory_limit')) ?></div>
    </div>
    <div class="info-card">
        <div class="info-label">Max Upload</div>
        <div class="info-value muted"><?= h(ini_get('upload_max_filesize')) ?></div>
    </div>
</div>

<!-- Database List with Stats -->
<h3 class="section-title" style="margin-top:24px;">All Databases</h3>
<div class="table-wrapper">
    <table class="data-table">
        <thead>
            <tr>
                <th>Database</th>
                <th>Tables</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($databases as $i => $dbName): ?>
            <tr>
                <td>
                    <a href="?db=<?= urlencode($dbName) ?>" style="color:var(--warning);font-weight:600;">
                        <?= h($dbName) ?>
                    </a>
                </td>
                <td class="cell-number">
                    <?php
                    try {
                        $tCount = count($dbInstance->getTables($dbName));
                        echo $tCount;
                    } catch (Exception $e) {
                        echo '<span class="cell-null">N/A</span>';
                    }
                    ?>
                </td>
                <td>
                    <a href="?db=<?= urlencode($dbName) ?>&tab=browse" class="btn btn-ghost btn-sm">Browse</a>
                    <a href="?db=<?= urlencode($dbName) ?>&tab=sql" class="btn btn-ghost btn-sm">SQL</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
