<?php
/**
 * DBForge — Saved Queries (per-user JSON storage)
 */

{
    return __DIR__ . '/../logs/saved_queries.json';
}

function dbforge_saved_queries_load(): array
{
    $file = dbforge_saved_queries_file();
    if (!file_exists($file)) return [];
    $data = @file_get_contents($file);
    if ($data === false) return [];
    $decoded = json_decode($data, true);
    return is_array($decoded) ? $decoded : [];
}

function dbforge_saved_queries_save_all(array $all): bool
{
    $file = dbforge_saved_queries_file();
    $dir = dirname($file);
    if (!is_dir($dir)) @mkdir($dir, 0750, true);
    return (bool)@file_put_contents($file, json_encode($all, JSON_PRETTY_PRINT), LOCK_EX);
}

function dbforge_saved_queries_get(string $username): array
{
    $all = dbforge_saved_queries_load();
    return $all[$username] ?? [];
}

/**
 * @return array The created entry with id, name, sql, db, created, updated
 */
function dbforge_saved_queries_add(string $username, string $name, string $sql, string $db): array
{
    $all = dbforge_saved_queries_load();
    $list = $all[$username] ?? [];
    $id = substr(md5(uniqid(mt_rand(), true)), 0, 8);
    $entry = [
        'id'      => $id,
        'name'    => $name,
        'sql'     => $sql,
        'db'      => $db,
        'created' => time(),
        'updated' => time(),
    ];
    array_unshift($list, $entry);
    $all[$username] = $list;
    dbforge_saved_queries_save_all($all);
    return $entry;
}

/**
 * @param array $fields Allowed keys: name, sql, db
 */
function dbforge_saved_queries_update(string $username, string $id, array $fields): bool
{
    $all = dbforge_saved_queries_load();
    $list = $all[$username] ?? [];
    foreach ($list as &$q) {
        if ($q['id'] === $id) {
            if (isset($fields['name'])) $q['name'] = $fields['name'];
            if (isset($fields['sql']))  $q['sql']  = $fields['sql'];
            if (isset($fields['db']))   $q['db']   = $fields['db'];
            $q['updated'] = time();
            $all[$username] = $list;
            return dbforge_saved_queries_save_all($all);
        }
    }
    return false;
}

function dbforge_saved_queries_delete(string $username, string $id): bool
{
    $all = dbforge_saved_queries_load();
    $list = $all[$username] ?? [];
    $all[$username] = array_values(array_filter($list, function($q) use ($id) {
        return $q['id'] !== $id;
    }));
    return dbforge_saved_queries_save_all($all);
}
