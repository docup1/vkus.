<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');

$root = __DIR__;
$defaultDb = $root . '/database/app.sqlite';
$fallbackDb = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'vkusno-demo-app.sqlite';
$fallbackJson = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'vkusno-demo-app.json';
$selectedDb = ((is_dir($root . '/database') || @mkdir($root . '/database', 0775, true)) && is_writable($root . '/database')) ? $defaultDb : $fallbackDb;
$checks = [
    'PHP version' => PHP_VERSION,
    'SAPI' => PHP_SAPI,
    'project root' => $root,
    'public/index.php exists' => is_file($root . '/public/index.php') ? 'yes' : 'no',
    'app/bootstrap.php exists' => is_file($root . '/app/bootstrap.php') ? 'yes' : 'no',
    'storage mode' => (getenv('VIT_FORCE_JSON') === '1' || !extension_loaded('pdo_sqlite')) ? 'json fallback' : 'sqlite',
    'database dir exists' => is_dir($root . '/database') ? 'yes' : 'no',
    'database dir writable' => is_writable($root . '/database') ? 'yes' : 'no',
    'selected sqlite path' => $selectedDb,
    'json fallback path' => $fallbackJson,
    'selected sqlite dir writable' => is_writable(dirname($selectedDb)) ? 'yes' : 'no',
    'database/app.sqlite exists' => is_file($root . '/database/app.sqlite') ? 'yes' : 'no',
    'database/app.sqlite writable' => is_file($root . '/database/app.sqlite') ? (is_writable($root . '/database/app.sqlite') ? 'yes' : 'no') : 'n/a',
    'pdo_sqlite loaded' => extension_loaded('pdo_sqlite') ? 'yes' : 'no',
    'sqlite3 loaded' => extension_loaded('sqlite3') ? 'yes' : 'no',
    'openssl loaded' => extension_loaded('openssl') ? 'yes' : 'no',
    'mbstring loaded' => extension_loaded('mbstring') ? 'yes' : 'no',
];
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>VIT health</title>
    <style>
        body { margin: 0; background: #F8F4E8; color: #1B1B1B; font-family: Arial, sans-serif; }
        main { max-width: 900px; margin: 40px auto; padding: 24px; background: #fff; border: 2px solid #1B1B1B; border-radius: 8px; }
        td { padding: 8px 12px; border-bottom: 1px solid #ddd; }
        td:first-child { font-weight: 700; }
    </style>
</head>
<body>
<main>
    <h1>VIT health</h1>
    <table>
        <?php foreach ($checks as $name => $value): ?>
            <tr>
                <td><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</main>
</body>
</html>
