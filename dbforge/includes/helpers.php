<?php
/**
 * DBForge - Helper Functions
 */

/**
 * Load the theme system - returns available themes and active theme info
 */
function dbforge_load_themes(string $themesDir, string $defaultTheme): array
{
    $themes = [];
    foreach (glob($themesDir . '/*/theme.json') as $file) {
        $data = json_decode(file_get_contents($file), true);
        if ($data) {
            $slug = basename(dirname($file));
            $data['slug'] = $slug;
            $data['css_path'] = "themes/{$slug}/style.css";
            $themes[$slug] = $data;
        }
    }

    // Determine active theme from cookie or default
    $active = $_COOKIE['dbforge_theme'] ?? $defaultTheme;
    if (!isset($themes[$active])) {
        $active = $defaultTheme;
    }

    return [
        'list'   => $themes,
        'active' => $active,
        'data'   => $themes[$active] ?? [],
    ];
}

/**
 * Format bytes to human readable
 */
function format_bytes(int $bytes, int $precision = 2): string
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

/**
 * Format number with separator
 */
function format_number($num): string
{
    return number_format((int) $num, 0, '.', ',');
}

/**
 * Format uptime seconds to human string
 */
function format_uptime(int $seconds): string
{
    $days = floor($seconds / 86400);
    $hours = floor(($seconds % 86400) / 3600);
    $mins = floor(($seconds % 3600) / 60);
    $parts = [];
    if ($days > 0) $parts[] = "{$days}d";
    if ($hours > 0) $parts[] = "{$hours}h";
    if ($mins > 0) $parts[] = "{$mins}m";
    return implode(' ', $parts) ?: '< 1m';
}

/**
 * Escape HTML output
 */
function h(string $str): string
{
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * Truncate string
 */
function truncate(string $str, int $len = 60): string
{
    if (mb_strlen($str) <= $len) return $str;
    return mb_substr($str, 0, $len) . '…';
}

/**
 * Detect column value type for styling
 */
function cell_class($value, string $columnKey = ''): string
{
    if ($value === null) return 'cell-null';
    if ($columnKey === 'PRI') return 'cell-primary';
    if (is_numeric($value)) return 'cell-number';
    if (preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) return 'cell-date';
    if (preg_match('/^\$2[ayb]\$/', $value)) return 'cell-hash';
    return '';
}

/**
 * Get a safe request parameter
 */
function input(string $key, $default = null)
{
    return $_GET[$key] ?? $_POST[$key] ?? $default;
}
