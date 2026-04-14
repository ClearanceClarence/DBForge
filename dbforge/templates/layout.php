<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($appName) ?> — <?= h($currentDb ?? 'Server') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Base theme (always loaded first) -->
    <link rel="stylesheet" href="themes/dark-industrial/style.css" id="base-theme">
    <?php if ($activeTheme !== 'dark-industrial'): ?>
    <!-- Active theme overrides -->
    <link rel="stylesheet" href="themes/<?= h($activeTheme) ?>/style.css" id="active-theme">
    <?php endif; ?>
    <?php if (isset($auth)): ?>
    <?= $auth->csrfMeta() ?>
    <?php endif; ?>
</head>
<body>
<div class="app-wrapper">

    <!-- ═══ Header ═══ -->
    <header class="app-header">
        <div class="header-left">
            <a href="?" class="logo">
                <svg class="logo-icon" width="22" height="22" viewBox="0 0 24 24" fill="none">
                    <rect x="3" y="1" width="18" height="22" rx="2" stroke="currentColor" stroke-width="1.5"/>
                    <ellipse cx="12" cy="7" rx="6" ry="3" stroke="currentColor" stroke-width="1.5"/>
                    <path d="M6 7v5c0 1.66 2.69 3 6 3s6-1.34 6-3V7" stroke="currentColor" stroke-width="1.5"/>
                    <path d="M6 12v5c0 1.66 2.69 3 6 3s6-1.34 6-3v-5" stroke="currentColor" stroke-width="1.5"/>
                </svg>
                <span class="logo-text"><?= h($appName) ?></span>
                <span class="logo-version">v<?= h($appVersion) ?></span>
            </a>
            <div class="header-meta">
                <span class="header-chip"><?= icon('server', 11) ?> <?= h($serverHost) ?></span>
                <span class="header-chip"><?= icon('database', 11) ?> MySQL <?= h($serverVersion) ?></span>
                <span class="header-chip"><?= icon('layers', 11) ?> <?= count($databases) ?> databases</span>
            </div>
        </div>
        <div class="header-right">
            <?php if (isset($auth) && $auth->isReadOnly()): ?>
            <span class="header-chip" style="color:var(--warning);border-color:var(--warning);"><?= icon('eye', 11) ?> Read-Only</span>
            <?php endif; ?>
            <select class="theme-select" id="theme-selector" onchange="DBForge.switchTheme(this.value)">
                <?php foreach ($themes as $slug => $theme): ?>
                <option value="<?= h($slug) ?>" <?= $slug === $activeTheme ? 'selected' : '' ?>>
                    <?= h($theme['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
            <span class="status-dot online"></span>
            <span class="header-chip"><?= icon('activity', 11) ?> Connected</span>
            <?php if (isset($auth) && $auth->isAuthRequired() && $auth->isLoggedIn()): ?>
            <span class="header-chip"><?= icon('key', 11) ?> <?= h($auth->getUsername()) ?></span>
            <a href="?action=logout" class="btn btn-ghost btn-sm" style="padding:2px 8px;font-size:var(--font-size-xs);"><?= icon('x', 12) ?> Logout</a>
            <?php endif; ?>
        </div>
    </header>

    <div class="app-body">

        <!-- ═══ Sidebar ═══ -->
        <?php include __DIR__ . '/sidebar.php'; ?>

        <!-- ═══ Main Area ═══ -->
        <main class="main-area">

            <!-- Tab Bar -->
            <div class="tab-bar">
                <div class="tab-group">
                    <?php
                    $tabs = [
                        'browse'    => ['icon' => 'table', 'label' => 'Browse'],
                        'structure' => ['icon' => 'columns', 'label' => 'Structure'],
                        'sql'       => ['icon' => 'terminal', 'label' => 'SQL'],
                        'info'      => ['icon' => 'info', 'label' => 'Info'],
                        'export'    => ['icon' => 'download', 'label' => 'Export'],
                    ];
                    foreach ($tabs as $tabId => $tab):
                        $isActive = ($activeTab === $tabId);
                        $href = "?db=" . urlencode($currentDb ?? '') . "&table=" . urlencode($currentTable ?? '') . "&tab={$tabId}";
                        if ($tabId === 'sql') {
                            $href = "?db=" . urlencode($currentDb ?? '') . "&tab=sql";
                        }
                    ?>
                    <a href="<?= $href ?>" class="tab-btn <?= $isActive ? 'active' : '' ?>">
                        <?= icon($tab['icon'], 14) ?>
                        <?= $tab['label'] ?>
                    </a>
                    <?php endforeach; ?>
                    <a href="?tab=server" class="tab-btn <?= $activeTab === 'server' ? 'active' : '' ?>">
                        <?= icon('server', 14) ?> Server
                    </a>
                    <a href="?tab=settings" class="tab-btn <?= $activeTab === 'settings' ? 'active' : '' ?>">
                        <?= icon('settings', 14) ?> Settings
                    </a>
                </div>
                <?php if ($currentDb): ?>
                <div class="breadcrumb">
                    <span class="db"><?= h($currentDb) ?></span>
                    <?php if ($currentTable): ?>
                    <span class="sep">›</span>
                    <span class="tbl"><?= h($currentTable) ?></span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Content Area -->
            <div class="content-area fade-in">
                <?php include $contentTemplate; ?>
            </div>
        </main>
    </div>

    <!-- ═══ Status Bar ═══ -->
    <footer class="status-bar">
        <div class="status-left">
            <span class="status-dot online"></span>
            <span id="status-message">Ready</span>
        </div>
        <div class="status-right">
            <span><?= h($appName) ?> © <?= date('Y') ?></span>
            <span style="color: var(--text-muted);">|</span>
            <span>PHP <?= PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION ?> · MySQL <?= h($serverVersion) ?></span>
        </div>
    </footer>
</div>

<script src="js/dbforge.js"></script>
</body>
</html>
