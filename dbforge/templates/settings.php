<?php
/**
 * DBForge — Settings Page
 * Allows editing config.php from the UI.
 */

$settingsMsg = '';
$settingsErr = '';

// ── Handle Settings Save ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_settings_action'] ?? '') === 'save') {
    // CSRF check
    if (isset($auth) && $auth->csrfEnabled() && !$auth->validateCsrf()) {
        $settingsErr = 'Invalid security token. Please reload and try again.';
    } else {
        $newConfig = $config; // Start from current config

        // ── Database ──
        $newConfig['db']['host']     = trim($_POST['db_host'] ?? '127.0.0.1');
        $newConfig['db']['port']     = (int)($_POST['db_port'] ?? 3306);
        $newConfig['db']['username'] = trim($_POST['db_user'] ?? 'root');
        // Only update password if the field wasn't left as placeholder
        if (($_POST['db_pass_changed'] ?? '0') === '1') {
            $newConfig['db']['password'] = $_POST['db_pass'] ?? '';
        }

        // ── App ──
        $newConfig['app']['default_theme']   = $_POST['default_theme'] ?? 'light-clean';
        $newConfig['app']['rows_per_page']   = max(10, min(500, (int)($_POST['rows_per_page'] ?? 50)));
        $newConfig['app']['enable_export']   = isset($_POST['enable_export']);

        // ── Fonts ──
        $fontZones = dbforge_font_zones();
        if (!isset($newConfig['app']['fonts'])) $newConfig['app']['fonts'] = [];
        foreach ($fontZones as $zoneKey => $zone) {
            $newConfig['app']['fonts'][$zoneKey] = trim($_POST['font_' . $zoneKey] ?? '');
        }

        // ── Security ──
        $newConfig['security']['require_auth']      = isset($_POST['require_auth']);
        $newConfig['security']['csrf_enabled']       = isset($_POST['csrf_enabled']);
        $newConfig['security']['force_https']        = isset($_POST['force_https']);
        $newConfig['security']['read_only']          = isset($_POST['read_only']);
        $newConfig['security']['session_lifetime']   = max(300, (int)($_POST['session_lifetime'] ?? 3600));
        $newConfig['security']['max_login_attempts'] = max(1, (int)($_POST['max_login_attempts'] ?? 5));
        $newConfig['security']['lockout_duration']   = max(30, (int)($_POST['lockout_duration'] ?? 300));
        $newConfig['security']['query_log']          = isset($_POST['query_log']);

        // IP whitelist (one per line)
        $ipRaw = trim($_POST['ip_whitelist'] ?? '');
        $newConfig['security']['ip_whitelist'] = $ipRaw
            ? array_values(array_filter(array_map('trim', explode("\n", $ipRaw))))
            : [];

        // Hidden databases (one per line)
        $hiddenRaw = trim($_POST['hidden_databases'] ?? '');
        $newConfig['security']['hidden_databases'] = $hiddenRaw
            ? array_values(array_filter(array_map('trim', explode("\n", $hiddenRaw))))
            : [];

        // ── Users ──
        // Keep existing users, process changes
        $existingUsers = $newConfig['security']['users'] ?? [];
        $updatedUsers = [];

        // Existing users
        $keepUsers = $_POST['keep_user'] ?? [];
        foreach ($existingUsers as $uname => $uhash) {
            if (in_array($uname, $keepUsers)) {
                // Check if password is being changed
                $newPass = $_POST['user_newpass_' . $uname] ?? '';
                if (!empty($newPass)) {
                    if (strlen($newPass) < 6) {
                        $settingsErr = "Password for '{$uname}' must be at least 6 characters.";
                        break;
                    }
                    $updatedUsers[$uname] = password_hash($newPass, PASSWORD_BCRYPT);
                } else {
                    $updatedUsers[$uname] = $uhash;
                }
            }
            // If not in keepUsers, user is deleted
        }

        // New user
        $newUsername = trim($_POST['new_username'] ?? '');
        $newPassword = $_POST['new_password'] ?? '';
        if (!empty($newUsername)) {
            if (strlen($newUsername) < 3) {
                $settingsErr = 'New username must be at least 3 characters.';
            } elseif (strlen($newPassword) < 6) {
                $settingsErr = 'New password must be at least 6 characters.';
            } elseif (isset($updatedUsers[$newUsername])) {
                $settingsErr = "Username '{$newUsername}' already exists.";
            } else {
                $updatedUsers[$newUsername] = password_hash($newPassword, PASSWORD_BCRYPT);
            }
        }

        // Must have at least one user if auth is enabled
        if ($newConfig['security']['require_auth'] && empty($updatedUsers)) {
            $settingsErr = 'You must have at least one user when authentication is enabled.';
        }

        if (empty($settingsErr)) {
            $newConfig['security']['users'] = $updatedUsers;

            // Write config.php
            $written = writeConfig(__DIR__ . '/../config.php', $newConfig);
            if ($written) {
                $settingsMsg = 'Settings saved successfully. Some changes may require a page reload.';
                $config = $newConfig; // Update in-memory
                if (isset($auth)) {
                    $auth->logActivity('Settings updated');
                }
            } else {
                $settingsErr = 'Could not write config.php. Check file permissions.';
            }
        }
    }
}

/**
 * Write config array back to PHP file
 */
function writeConfig(string $path, array $cfg): bool
{
    $export = function ($val, $indent = 2) use (&$export) {
        $pad = str_repeat('    ', $indent);
        $padInner = str_repeat('    ', $indent + 1);

        if (is_array($val)) {
            // Check if sequential array
            $isSeq = array_keys($val) === range(0, count($val) - 1);
            if (empty($val)) return '[]';

            $lines = [];
            foreach ($val as $k => $v) {
                $key = $isSeq ? '' : var_export($k, true) . ' => ';
                $lines[] = $padInner . $key . $export($v, $indent + 1);
            }
            return "[\n" . implode(",\n", $lines) . ",\n{$pad}]";
        }
        if (is_bool($val)) return $val ? 'true' : 'false';
        if (is_int($val) || is_float($val)) return (string)$val;
        if (is_null($val)) return 'null';

        // String - check for __DIR__ reference
        if (str_contains($val, '__DIR__')) return $val;

        return var_export($val, true);
    };

    $out = "<?php\n/**\n * DBForge Configuration\n * Last modified: " . date('Y-m-d H:i:s') . "\n */\n\nreturn ";
    $out .= $export($cfg, 0);
    $out .= ";\n";

    // Preserve the __DIR__ reference for query_log_file
    $out = str_replace(
        "'__DIR__ . '/logs/queries.log''",
        "__DIR__ . '/logs/queries.log'",
        $out
    );

    return (bool)@file_put_contents($path, $out, LOCK_EX);
}

$sec = $config['security'] ?? [];
$app = $config['app'] ?? [];
$db  = $config['db'] ?? [];
?>

<?php if ($settingsMsg): ?>
<div class="success-box" style="margin-bottom:16px;">
    <?= icon('check', 14) ?> <?= h($settingsMsg) ?>
</div>
<?php endif; ?>

<?php if ($settingsErr): ?>
<div class="error-box" style="margin-bottom:16px;">
    <strong>Error:</strong> <?= h($settingsErr) ?>
</div>
<?php endif; ?>

<form method="post" action="?tab=settings" id="settings-form">
    <?php if (isset($auth)): ?><?= $auth->csrfField() ?><?php endif; ?>
    <input type="hidden" name="_settings_action" value="save">

    <!-- ═══ Database Connection ═══ -->
    <div class="settings-section">
        <h3 class="section-title"><?= icon('database', 16) ?> Database Connection</h3>
        <div class="settings-grid">
            <div class="settings-field" style="flex:3;">
                <label class="settings-label" for="s-db-host">Host</label>
                <input type="text" id="s-db-host" name="db_host" class="settings-input"
                       value="<?= h($db['host'] ?? '127.0.0.1') ?>">
            </div>
            <div class="settings-field" style="flex:1;">
                <label class="settings-label" for="s-db-port">Port</label>
                <input type="number" id="s-db-port" name="db_port" class="settings-input"
                       value="<?= h($db['port'] ?? 3306) ?>">
            </div>
        </div>
        <div class="settings-grid">
            <div class="settings-field">
                <label class="settings-label" for="s-db-user">Username</label>
                <input type="text" id="s-db-user" name="db_user" class="settings-input"
                       value="<?= h($db['username'] ?? 'root') ?>" autocomplete="off">
            </div>
            <div class="settings-field">
                <label class="settings-label" for="s-db-pass">Password</label>
                <input type="password" id="s-db-pass" name="db_pass" class="settings-input"
                       value="" placeholder="••••••• (unchanged)" autocomplete="off"
                       onchange="document.getElementById('db-pass-changed').value='1'">
                <input type="hidden" id="db-pass-changed" name="db_pass_changed" value="0">
                <div class="settings-hint">Leave empty to keep current password</div>
            </div>
        </div>
    </div>

    <!-- ═══ Application ═══ -->
    <div class="settings-section">
        <h3 class="section-title"><?= icon('layers', 16) ?> Application</h3>
        <div class="settings-grid">
            <div class="settings-field">
                <label class="settings-label" for="s-theme">Default Theme</label>
                <select id="s-theme" name="default_theme" class="settings-input">
                    <?php foreach ($themes as $slug => $theme): ?>
                    <option value="<?= h($slug) ?>" <?= ($app['default_theme'] ?? '') === $slug ? 'selected' : '' ?>>
                        <?= h($theme['name']) ?> (<?= h($theme['type'] ?? '') ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="settings-field">
                <label class="settings-label" for="s-rpp">Rows Per Page</label>
                <input type="number" id="s-rpp" name="rows_per_page" class="settings-input"
                       value="<?= (int)($app['rows_per_page'] ?? 50) ?>" min="10" max="500">
            </div>
        </div>
        <div class="settings-check-group">
            <label class="settings-check">
                <input type="checkbox" name="enable_export" <?= !empty($app['enable_export']) ? 'checked' : '' ?>>
                <?= icon('download', 14) ?> Enable data export (SQL / CSV)
            </label>
        </div>
    </div>

    <!-- ═══ Authentication ═══ -->
    <div class="settings-section">
        <h3 class="section-title"><?= icon('key', 16) ?> Authentication</h3>
        <div class="settings-check-group">
            <label class="settings-check">
                <input type="checkbox" name="require_auth" <?= !empty($sec['require_auth']) ? 'checked' : '' ?>>
                <?= icon('key', 14) ?> Require login (recommended for production)
            </label>
            <label class="settings-check">
                <input type="checkbox" name="csrf_enabled" <?= ($sec['csrf_enabled'] ?? true) ? 'checked' : '' ?>>
                <?= icon('hash', 14) ?> CSRF protection on forms and AJAX
            </label>
            <label class="settings-check">
                <input type="checkbox" name="force_https" <?= !empty($sec['force_https']) ? 'checked' : '' ?>>
                <?= icon('external-link', 14) ?> Force HTTPS redirect
            </label>
            <label class="settings-check">
                <input type="checkbox" name="read_only" <?= !empty($sec['read_only']) ? 'checked' : '' ?>>
                <?= icon('eye', 14) ?> Read-only mode (blocks INSERT/UPDATE/DELETE/DROP)
            </label>
            <label class="settings-check">
                <input type="checkbox" name="query_log" <?= !empty($sec['query_log']) ? 'checked' : '' ?>>
                <?= icon('file-text', 14) ?> Query audit log (logs/queries.log)
            </label>
        </div>
        <div class="settings-grid" style="margin-top:12px;">
            <div class="settings-field">
                <label class="settings-label" for="s-session">Session Timeout (seconds)</label>
                <input type="number" id="s-session" name="session_lifetime" class="settings-input"
                       value="<?= (int)($sec['session_lifetime'] ?? 3600) ?>" min="300">
                <div class="settings-hint">Default: 3600 (1 hour)</div>
            </div>
            <div class="settings-field">
                <label class="settings-label" for="s-maxlogin">Max Login Attempts</label>
                <input type="number" id="s-maxlogin" name="max_login_attempts" class="settings-input"
                       value="<?= (int)($sec['max_login_attempts'] ?? 5) ?>" min="1">
            </div>
            <div class="settings-field">
                <label class="settings-label" for="s-lockout">Lockout Duration (seconds)</label>
                <input type="number" id="s-lockout" name="lockout_duration" class="settings-input"
                       value="<?= (int)($sec['lockout_duration'] ?? 300) ?>" min="30">
            </div>
        </div>
    </div>

    <!-- ═══ Users ═══ -->
    <div class="settings-section">
        <h3 class="section-title"><?= icon('key', 16) ?> User Accounts</h3>
        <div class="settings-hint" style="margin-bottom:12px;">Passwords are stored as bcrypt hashes. Leave the password field empty to keep the current password.</div>

        <?php foreach (($sec['users'] ?? []) as $uname => $uhash): ?>
        <div class="settings-user-row">
            <label class="settings-check" style="flex-shrink:0;">
                <input type="checkbox" name="keep_user[]" value="<?= h($uname) ?>" checked>
            </label>
            <div class="settings-field" style="flex:1;">
                <input type="text" class="settings-input" value="<?= h($uname) ?>" disabled
                       style="opacity:0.7;">
            </div>
            <div class="settings-field" style="flex:1.5;">
                <input type="password" name="user_newpass_<?= h($uname) ?>" class="settings-input"
                       placeholder="New password (leave empty to keep)" autocomplete="new-password">
            </div>
        </div>
        <?php endforeach; ?>

        <div class="settings-divider"></div>
        <div class="settings-hint" style="margin-bottom:8px;">Add new user:</div>
        <div class="settings-user-row">
            <div class="settings-field" style="flex:1;">
                <input type="text" name="new_username" class="settings-input"
                       placeholder="Username (min 3 chars)" autocomplete="off">
            </div>
            <div class="settings-field" style="flex:1.5;">
                <input type="password" name="new_password" class="settings-input"
                       placeholder="Password (min 6 chars)" autocomplete="new-password">
            </div>
        </div>
    </div>

    <!-- ═══ Fonts ═══ -->
    <div class="settings-section">
        <h3 class="section-title"><?= icon('edit', 16) ?> Fonts</h3>
        <div class="settings-hint" style="margin-bottom:14px;">Customize fonts for different parts of the interface. Select "Theme default" to use the theme's built-in font. Google Fonts are loaded automatically.</div>

        <?php
        $fontZones = dbforge_font_zones();
        $fontCatalog = dbforge_font_catalog();
        $currentFonts = $config['app']['fonts'] ?? [];
        ?>

        <div class="font-grid">
            <?php foreach ($fontZones as $zoneKey => $zone): ?>
            <div class="font-zone">
                <label class="settings-label" for="font-<?= h($zoneKey) ?>"><?= h($zone['label']) ?></label>
                <select id="font-<?= h($zoneKey) ?>" name="font_<?= h($zoneKey) ?>" class="settings-input font-select"
                        data-zone="<?= h($zoneKey) ?>" data-catalog="<?= h($zone['catalog']) ?>">
                    <?php
                    $cat = $fontCatalog[$zone['catalog']] ?? [];
                    $currentVal = $currentFonts[$zoneKey] ?? '';
                    $hasGroups = true;
                    $inGoogle = false;
                    $inSystem = false;
                    foreach ($cat as $fontName => $fontInfo):
                        $isGoogle = !empty($fontInfo['google']);
                        // Group headers
                        if ($fontName === '' || ($isGoogle && !$inGoogle)):
                            if ($inSystem) echo '</optgroup>';
                            if ($fontName === ''):
                                // default option
                            elseif ($isGoogle && !$inGoogle):
                                echo '<optgroup label="Google Fonts">';
                                $inGoogle = true;
                            endif;
                        elseif (!$isGoogle && $inGoogle && !$inSystem):
                            echo '</optgroup><optgroup label="System Fonts">';
                            $inSystem = true;
                        endif;
                    ?>
                    <option value="<?= h($fontName) ?>" <?= $currentVal === $fontName ? 'selected' : '' ?>
                            <?= $fontName ? 'style="font-family: ' . h($fontName) . ';"' : '' ?>>
                        <?= h($fontInfo['label']) ?>
                    </option>
                    <?php endforeach; ?>
                    <?php if ($inGoogle || $inSystem) echo '</optgroup>'; ?>
                </select>
                <div class="settings-hint"><?= h($zone['desc']) ?></div>
                <div class="font-preview" id="font-preview-<?= h($zoneKey) ?>"
                     data-catalog="<?= h($zone['catalog']) ?>">
                    The quick brown fox jumps over the lazy dog — 0123456789
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ═══ Access Control ═══ -->
    <div class="settings-section">
        <h3 class="section-title"><?= icon('filter', 16) ?> Access Control</h3>
        <div class="settings-grid">
            <div class="settings-field">
                <label class="settings-label" for="s-ipwl">IP Whitelist</label>
                <textarea id="s-ipwl" name="ip_whitelist" class="settings-textarea" rows="3"
                          placeholder="One IP or CIDR per line (empty = allow all)"
                ><?= h(implode("\n", $sec['ip_whitelist'] ?? [])) ?></textarea>
                <div class="settings-hint">e.g. 192.168.1.0/24, 10.0.0.50</div>
            </div>
            <div class="settings-field">
                <label class="settings-label" for="s-hiddendb">Hidden Databases</label>
                <textarea id="s-hiddendb" name="hidden_databases" class="settings-textarea" rows="3"
                          placeholder="One database name per line"
                ><?= h(implode("\n", $sec['hidden_databases'] ?? [])) ?></textarea>
                <div class="settings-hint">These won't appear in sidebar or autocomplete</div>
            </div>
        </div>
    </div>

    <!-- ═══ Save ═══ -->
    <div class="settings-footer">
        <button type="submit" class="btn btn-primary">
            <?= icon('check', 14) ?> Save Settings
        </button>
        <span class="settings-hint">Changes are written to config.php</span>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var loadedFonts = {};

    function loadGoogleFont(fontName, weights) {
        if (!fontName || loadedFonts[fontName]) return;
        loadedFonts[fontName] = true;
        var family = fontName.replace(/ /g, '+') + ':wght@' + (weights || '400;700');
        var link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'https://fonts.googleapis.com/css2?family=' + family + '&display=swap';
        document.head.appendChild(link);
    }

    // Font catalog (mirrors PHP)
    var googleFonts = {
        'DM Sans':1,'Inter':1,'Nunito Sans':1,'Open Sans':1,'Lato':1,'Roboto':1,
        'Source Sans 3':1,'Outfit':1,'Sora':1,'Work Sans':1,'Poppins':1,'IBM Plex Sans':1,
        'JetBrains Mono':1,'Fira Code':1,'Source Code Pro':1,'IBM Plex Mono':1,
        'Roboto Mono':1,'Inconsolata':1,'Space Mono':1,'Ubuntu Mono':1
    };

    document.querySelectorAll('.font-select').forEach(function(sel) {
        var zone = sel.dataset.zone;
        var catalog = sel.dataset.catalog;
        var preview = document.getElementById('font-preview-' + zone);

        function update() {
            var fontName = sel.value;
            if (!fontName) {
                preview.style.fontFamily = catalog === 'mono' ? 'var(--font-mono)' : 'var(--font-body)';
                return;
            }
            // Load if Google Font
            if (googleFonts[fontName]) loadGoogleFont(fontName, '400;600;700');

            var fallback = catalog === 'mono' ? ', monospace' : ', system-ui, sans-serif';
            preview.style.fontFamily = "'" + fontName + "'" + fallback;
        }

        sel.addEventListener('change', update);
        update(); // Initial
    });
});
</script>
