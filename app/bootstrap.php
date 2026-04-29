<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli' && !defined('VIT_PUBLIC_ENTRY')) {
    http_response_code(404);
    exit;
}

define('BASE_PATH', dirname(__DIR__));
define('DB_DIR', BASE_PATH . '/database');
define('DB_DEFAULT_PATH', DB_DIR . '/app.sqlite');
define('DB_FALLBACK_PATH', rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'vkusno-demo-app.sqlite');
define('JSON_FALLBACK_PATH', rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'vkusno-demo-app.json');
define('JWT_SECRET', 'demo-vkusno-i-tochka-secret-change-me');
define('JWT_COOKIE', 'vit_token');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    try {
        ensure_database_storage();

        $pdo = new PDO('sqlite:' . database_path());
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA foreign_keys = ON');
        migrate($pdo);
    } catch (Throwable $e) {
        render_boot_error($e);
    }

    return $pdo;
}

function storage_is_json(): bool
{
    return getenv('VIT_FORCE_JSON') === '1' || !extension_loaded('pdo_sqlite');
}

function init_storage(): void
{
    if (storage_is_json()) {
        json_read();
        return;
    }

    db();
}

function seed_products_array(): array
{
    return [
        ['category' => 'Бургеры', 'title' => 'Биг Спешиал', 'description' => 'Большой бургер с двумя рублеными бифштексами из говядины, сыром, салатом, луком и фирменным соусом.', 'image' => 'product-1.png', 'price' => 299, 'weight' => '340 г', 'is_featured' => 1],
        ['category' => 'Бургеры', 'title' => 'Биг Хит', 'description' => 'Сочный бургер с двумя говяжьими котлетами, маринованными огурчиками, луком и соусом в мягкой булочке.', 'image' => 'product-2.png', 'price' => 186, 'weight' => '228 г', 'is_featured' => 1],
        ['category' => 'Бургеры', 'title' => 'Гранд Де Люкс', 'description' => 'Бифштекс из говядины, свежие овощи, сыр и насыщенный соус для большого обеда.', 'image' => 'product-3.png', 'price' => 219, 'weight' => '251 г', 'is_featured' => 0],
        ['category' => 'Бургеры', 'title' => 'Чикен Премьер', 'description' => 'Куриная котлета в хрустящей панировке, свежий салат, сыр и нежный соус.', 'image' => 'product-4.png', 'price' => 173, 'weight' => '234 г', 'is_featured' => 1],
        ['category' => 'Роллы', 'title' => 'Цезарь Ролл', 'description' => 'Курица, свежий салат, сыр и соус в мягкой тортилье: легкий формат для быстрого перекуса.', 'image' => 'product-5.png', 'price' => 190, 'weight' => '211 г', 'is_featured' => 0],
        ['category' => 'Картофель и снеки', 'title' => 'Картофель Фри', 'description' => 'Классический золотистый картофель с хрустящей корочкой и мягкой серединкой.', 'image' => 'product-6.png', 'price' => 115, 'weight' => '100 г', 'is_featured' => 1],
        ['category' => 'Картофель и снеки', 'title' => 'Наггетсы 9 шт.', 'description' => 'Куриное филе в хрустящей панировке. Хорошо дружит с сырным, барбекю или кисло-сладким соусом.', 'image' => 'product-7.png', 'price' => 199, 'weight' => '156 г', 'is_featured' => 0],
        ['category' => 'Бургеры', 'title' => 'Гранд', 'description' => 'Говяжий бифштекс, сыр, свежие овощи и соус в мягкой булочке с кунжутом.', 'image' => 'product-8.png', 'price' => 193, 'weight' => '202 г', 'is_featured' => 0],
        ['category' => 'Напитки', 'title' => 'Капучино', 'description' => 'Горячий кофе с молочной пенкой для спокойной паузы между делами.', 'image' => 'product-9.png', 'price' => 145, 'weight' => '300 мл', 'is_featured' => 0],
        ['category' => 'Десерты', 'title' => 'Вишневый пирожок', 'description' => 'Горячий пирожок с яркой вишневой начинкой и хрустящим тестом.', 'image' => 'product-10.png', 'price' => 79, 'weight' => '80 г', 'is_featured' => 1],
        ['category' => 'Соусы', 'title' => 'Соус Сырный', 'description' => 'Нежный сырный соус для картофеля, наггетсов и всего, что хочется сделать еще вкуснее.', 'image' => 'product-11.png', 'price' => 45, 'weight' => '25 мл', 'is_featured' => 0],
        ['category' => 'Бургеры', 'title' => 'Двойной Биг Хит', 'description' => 'Большой бургер с четырьмя говяжьими котлетами, огурчиками, луком и фирменным соусом.', 'image' => 'product-12.png', 'price' => 248, 'weight' => '303 г', 'is_featured' => 0],
    ];
}

function json_read(): array
{
    if (!is_file(JSON_FALLBACK_PATH)) {
        $products = [];
        foreach (seed_products_array() as $index => $product) {
            $product['id'] = $index + 1;
            $products[] = $product;
        }
        json_write(['next_user_id' => 1, 'users' => [], 'profiles' => [], 'products' => $products, 'cart_items' => []]);
    }

    $raw = file_get_contents(JSON_FALLBACK_PATH);
    $data = $raw ? json_decode($raw, true) : null;
    if (!is_array($data)) {
        throw new RuntimeException('JSON-хранилище повреждено: ' . JSON_FALLBACK_PATH);
    }
    return $data;
}

function json_write(array $data): void
{
    $dir = dirname(JSON_FALLBACK_PATH);
    if (!is_dir($dir) || !is_writable($dir)) {
        throw new RuntimeException('Папка JSON-хранилища недоступна на запись: ' . $dir);
    }
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    if ($json === false || file_put_contents(JSON_FALLBACK_PATH, $json, LOCK_EX) === false) {
        throw new RuntimeException('Не удалось записать JSON-хранилище: ' . JSON_FALLBACK_PATH);
    }
}

function json_product_by_id(array $data, int $id): ?array
{
    foreach ($data['products'] as $product) {
        if ((int)$product['id'] === $id) {
            return $product;
        }
    }
    return null;
}

function ensure_database_storage(): void
{
    if (!extension_loaded('pdo_sqlite')) {
        throw new RuntimeException('На сервере не включено PHP-расширение pdo_sqlite.');
    }

    $path = database_path();
    $dir = dirname($path);

    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        throw new RuntimeException('Не удалось создать папку для SQLite: ' . $dir);
    }

    if (!is_writable($dir)) {
        @chmod($dir, 0775);
    }

    if (!is_writable($dir)) {
        throw new RuntimeException('Папка SQLite недоступна на запись для PHP: ' . $dir);
    }

    if (is_file($path) && !is_writable($path)) {
        @chmod($path, 0664);
    }

    if (is_file($path) && !is_writable($path)) {
        throw new RuntimeException('Файл SQLite недоступен на запись для PHP: ' . $path);
    }
}

function database_path(): string
{
    $fromEnv = getenv('VIT_SQLITE_PATH');
    if (is_string($fromEnv) && $fromEnv !== '') {
        return $fromEnv;
    }

    if ((is_dir(DB_DIR) || @mkdir(DB_DIR, 0775, true)) && is_writable(DB_DIR)) {
        if (!is_file(DB_DEFAULT_PATH) || is_writable(DB_DEFAULT_PATH) || @chmod(DB_DEFAULT_PATH, 0664)) {
            return DB_DEFAULT_PATH;
        }
    }

    return DB_FALLBACK_PATH;
}

function render_boot_error(Throwable $e): void
{
    http_response_code(500);
    $message = $e->getMessage();
    error_log('[vkusno-demo] ' . $message);

    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }

    echo '<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>Ошибка запуска</title><style>body{margin:0;background:#F8F4E8;color:#1B1B1B;font-family:Arial,sans-serif}main{max-width:760px;margin:8vh auto;padding:28px;background:#fff;border:2px solid #1B1B1B;border-radius:8px;box-shadow:8px 8px 0 #F7BE23}h1{margin-top:0;font-size:32px}.msg{padding:14px;background:#ffe8df;border-radius:8px;color:#9d260f}code{background:#f5f1e7;padding:2px 5px;border-radius:4px}</style></head><body><main>';
    echo '<h1>Сайт запустился, но база данных недоступна</h1>';
    echo '<p>Проверьте, что PHP может создавать и изменять SQLite-файл. Сейчас выбран путь: <code>' . htmlspecialchars(database_path(), ENT_QUOTES, 'UTF-8') . '</code>.</p>';
    echo '<p class="msg">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>';
    echo '</main></body></html>';
    exit;
}

function migrate(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            created_at TEXT NOT NULL
        );

        CREATE TABLE IF NOT EXISTS profiles (
            user_id INTEGER PRIMARY KEY,
            name TEXT DEFAULT '',
            phone TEXT DEFAULT '',
            city TEXT DEFAULT '',
            address TEXT DEFAULT '',
            FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            category TEXT NOT NULL,
            title TEXT NOT NULL,
            description TEXT NOT NULL,
            image TEXT NOT NULL,
            price INTEGER NOT NULL,
            weight TEXT NOT NULL,
            is_featured INTEGER NOT NULL DEFAULT 0
        );

        CREATE TABLE IF NOT EXISTS cart_items (
            user_id INTEGER NOT NULL,
            product_id INTEGER NOT NULL,
            qty INTEGER NOT NULL DEFAULT 1,
            PRIMARY KEY (user_id, product_id),
            FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE
        );
    ");

    $count = (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
    if ($count === 0) {
        seed_products($pdo);
    }
}

function seed_products(PDO $pdo): void
{
    $stmt = $pdo->prepare('
        INSERT INTO products (category, title, description, image, price, weight, is_featured)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ');

    foreach (seed_products_array() as $product) {
        $stmt->execute([
            $product['category'],
            $product['title'],
            $product['description'],
            $product['image'],
            $product['price'],
            $product['weight'],
            $product['is_featured'],
        ]);
    }
}

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function route(): string
{
    return $_GET['route'] ?? 'home';
}

function url_for(string $route, array $params = []): string
{
    $params = array_merge(['route' => $route], $params);
    return 'index.php?' . http_build_query($params);
}

function asset_url(string $path): string
{
    return 'assets/' . ltrim($path, '/');
}

function redirect_to(string $route, array $params = []): void
{
    header('Location: ' . url_for($route, $params));
    exit;
}

function flash(?string $message = null, string $type = 'ok'): ?array
{
    if ($message !== null) {
        $_SESSION['flash'] = ['message' => $message, 'type' => $type];
        return null;
    }

    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function base64url_encode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode(string $data): string
{
    $pad = 4 - (strlen($data) % 4);
    if ($pad < 4) {
        $data .= str_repeat('=', $pad);
    }
    return base64_decode(strtr($data, '-_', '+/')) ?: '';
}

function jwt_encode(array $payload): string
{
    $header = ['alg' => 'HS256', 'typ' => 'JWT'];
    $segments = [
        base64url_encode(json_encode($header)),
        base64url_encode(json_encode($payload)),
    ];
    $signature = hash_hmac('sha256', implode('.', $segments), JWT_SECRET, true);
    $segments[] = base64url_encode($signature);
    return implode('.', $segments);
}

function jwt_decode(string $token): ?array
{
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return null;
    }

    [$header, $payload, $signature] = $parts;
    $expected = base64url_encode(hash_hmac('sha256', $header . '.' . $payload, JWT_SECRET, true));
    if (!hash_equals($expected, $signature)) {
        return null;
    }

    $data = json_decode(base64url_decode($payload), true);
    if (!is_array($data) || (($data['exp'] ?? 0) < time())) {
        return null;
    }

    return $data;
}

function issue_auth_cookie(int $userId): void
{
    $token = jwt_encode([
        'sub' => $userId,
        'iat' => time(),
        'exp' => time() + 60 * 60 * 24 * 7,
    ]);

    setcookie(JWT_COOKIE, $token, [
        'expires' => time() + 60 * 60 * 24 * 7,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    $_COOKIE[JWT_COOKIE] = $token;
}

function clear_auth_cookie(): void
{
    setcookie(JWT_COOKIE, '', [
        'expires' => time() - 3600,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    unset($_COOKIE[JWT_COOKIE]);
}

function current_user(): ?array
{
    static $user = false;

    if ($user !== false) {
        return $user;
    }

    $token = $_COOKIE[JWT_COOKIE] ?? '';
    $payload = $token ? jwt_decode($token) : null;
    if (!$payload || empty($payload['sub'])) {
        $user = null;
        return null;
    }

    if (storage_is_json()) {
        $data = json_read();
        $id = (int)$payload['sub'];
        foreach ($data['users'] as $row) {
            if ((int)$row['id'] === $id) {
                $profile = $data['profiles'][(string)$id] ?? ['name' => '', 'phone' => '', 'city' => '', 'address' => ''];
                $user = array_merge($row, $profile);
                return $user;
            }
        }
        $user = null;
        return null;
    }

    $stmt = db()->prepare('
        SELECT users.id, users.email, users.created_at, profiles.name, profiles.phone, profiles.city, profiles.address
        FROM users
        LEFT JOIN profiles ON profiles.user_id = users.id
        WHERE users.id = ?
    ');
    $stmt->execute([(int)$payload['sub']]);
    $user = $stmt->fetch() ?: null;

    return $user;
}

function require_user(): array
{
    $user = current_user();
    if (!$user) {
        redirect_to('login', ['next' => route()]);
    }
    return $user;
}

function products(?string $category = null): array
{
    if (storage_is_json()) {
        $items = json_read()['products'];
        if ($category) {
            $items = array_values(array_filter($items, static fn(array $item): bool => $item['category'] === $category));
        }
        usort($items, static fn(array $a, array $b): int => [$a['category'], $a['id']] <=> [$b['category'], $b['id']]);
        return $items;
    }

    if ($category) {
        $stmt = db()->prepare('SELECT * FROM products WHERE category = ? ORDER BY id');
        $stmt->execute([$category]);
        return $stmt->fetchAll();
    }
    return db()->query('SELECT * FROM products ORDER BY category, id')->fetchAll();
}

function featured_products(): array
{
    if (storage_is_json()) {
        return array_slice(array_values(array_filter(json_read()['products'], static fn(array $item): bool => (int)$item['is_featured'] === 1)), 0, 3);
    }

    return db()->query('SELECT * FROM products WHERE is_featured = 1 ORDER BY id LIMIT 3')->fetchAll();
}

function categories(): array
{
    if (storage_is_json()) {
        $categories = array_values(array_unique(array_map(static fn(array $item): string => $item['category'], json_read()['products'])));
        sort($categories);
        return $categories;
    }

    return db()->query('SELECT DISTINCT category FROM products ORDER BY category')->fetchAll(PDO::FETCH_COLUMN);
}

function cart_items(int $userId): array
{
    if (storage_is_json()) {
        $data = json_read();
        $items = [];
        foreach ($data['cart_items'][(string)$userId] ?? [] as $productId => $qty) {
            $product = json_product_by_id($data, (int)$productId);
            if ($product) {
                $product['qty'] = (int)$qty;
                $items[] = $product;
            }
        }
        return $items;
    }

    $stmt = db()->prepare('
        SELECT cart_items.qty, products.*
        FROM cart_items
        JOIN products ON products.id = cart_items.product_id
        WHERE cart_items.user_id = ?
        ORDER BY cart_items.rowid
    ');
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function cart_total(array $items): int
{
    $total = 0;
    foreach ($items as $item) {
        $total += (int)$item['price'] * (int)$item['qty'];
    }
    return $total;
}

function cart_count(?array $user): int
{
    if (!$user) {
        return 0;
    }
    if (storage_is_json()) {
        return array_sum(json_read()['cart_items'][(string)$user['id']] ?? []);
    }

    $stmt = db()->prepare('SELECT COALESCE(SUM(qty), 0) FROM cart_items WHERE user_id = ?');
    $stmt->execute([(int)$user['id']]);
    return (int)$stmt->fetchColumn();
}

function handle_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        return;
    }

    if (storage_is_json()) {
        handle_post_json();
        return;
    }

    $action = $_POST['action'] ?? '';
    $pdo = db();

    if ($action === 'register') {
        $email = trim(mb_strtolower($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $name = trim($_POST['name'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($password) < 6) {
            flash('Введите корректный email и пароль минимум из 6 символов.', 'error');
            redirect_to('register');
        }

        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('INSERT INTO users (email, password_hash, created_at) VALUES (?, ?, ?)');
            $stmt->execute([$email, password_hash($password, PASSWORD_DEFAULT), date('c')]);
            $userId = (int)$pdo->lastInsertId();
            $stmt = $pdo->prepare('INSERT INTO profiles (user_id, name, city) VALUES (?, ?, ?)');
            $stmt->execute([$userId, $name, 'Москва']);
            $pdo->commit();
            issue_auth_cookie($userId);
            flash('Профиль создан. Можно собирать демо-заказ.');
            redirect_to($_POST['next'] ?? 'profile');
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            flash('Такой email уже зарегистрирован.', 'error');
            redirect_to('register');
        }
    }

    if ($action === 'login') {
        $email = trim(mb_strtolower($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            flash('Неверный email или пароль.', 'error');
            redirect_to('login');
        }

        issue_auth_cookie((int)$user['id']);
        flash('Вы вошли в профиль.');
        redirect_to($_POST['next'] ?? 'menu');
    }

    if ($action === 'logout') {
        clear_auth_cookie();
        flash('Вы вышли из профиля.');
        redirect_to('home');
    }

    if ($action === 'profile') {
        $user = require_user();
        $stmt = $pdo->prepare('UPDATE profiles SET name = ?, phone = ?, city = ?, address = ? WHERE user_id = ?');
        $stmt->execute([
            trim($_POST['name'] ?? ''),
            trim($_POST['phone'] ?? ''),
            trim($_POST['city'] ?? ''),
            trim($_POST['address'] ?? ''),
            (int)$user['id'],
        ]);
        flash('Профиль обновлен.');
        redirect_to('profile');
    }

    if ($action === 'password') {
        $user = require_user();
        $current = (string)($_POST['current_password'] ?? '');
        $new = (string)($_POST['new_password'] ?? '');
        $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
        $stmt->execute([(int)$user['id']]);
        $hash = (string)$stmt->fetchColumn();

        if (!password_verify($current, $hash) || mb_strlen($new) < 6) {
            flash('Проверьте текущий пароль и задайте новый минимум из 6 символов.', 'error');
            redirect_to('profile');
        }

        $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $stmt->execute([password_hash($new, PASSWORD_DEFAULT), (int)$user['id']]);
        flash('Пароль изменен.');
        redirect_to('profile');
    }

    if ($action === 'cart-add') {
        $user = current_user();
        if (!$user) {
            flash('Войдите, чтобы добавить блюдо в корзину.', 'error');
            redirect_to('login', ['next' => 'menu']);
        }

        $productId = (int)($_POST['product_id'] ?? 0);
        $stmt = $pdo->prepare('
            INSERT INTO cart_items (user_id, product_id, qty) VALUES (?, ?, 1)
            ON CONFLICT(user_id, product_id) DO UPDATE SET qty = qty + 1
        ');
        $stmt->execute([(int)$user['id'], $productId]);
        flash('Блюдо добавлено в корзину.');
        redirect_to($_POST['from'] ?? 'menu');
    }

    if ($action === 'cart-update') {
        $user = require_user();
        $productId = (int)($_POST['product_id'] ?? 0);
        $qty = max(1, min(20, (int)($_POST['qty'] ?? 1)));
        $stmt = $pdo->prepare('UPDATE cart_items SET qty = ? WHERE user_id = ? AND product_id = ?');
        $stmt->execute([$qty, (int)$user['id'], $productId]);
        redirect_to('cart');
    }

    if ($action === 'cart-remove') {
        $user = require_user();
        $stmt = $pdo->prepare('DELETE FROM cart_items WHERE user_id = ? AND product_id = ?');
        $stmt->execute([(int)$user['id'], (int)($_POST['product_id'] ?? 0)]);
        flash('Позиция удалена.');
        redirect_to('cart');
    }

    if ($action === 'cart-clear') {
        $user = require_user();
        $stmt = $pdo->prepare('DELETE FROM cart_items WHERE user_id = ?');
        $stmt->execute([(int)$user['id']]);
        flash('Корзина очищена.');
        redirect_to('cart');
    }

    if ($action === 'checkout') {
        $user = require_user();
        $stmt = $pdo->prepare('DELETE FROM cart_items WHERE user_id = ?');
        $stmt->execute([(int)$user['id']]);
        flash('Демо-заказ собран. В реальном продукте здесь был бы следующий шаг оформления.');
        redirect_to('cart');
    }
}

function handle_post_json(): void
{
    $action = $_POST['action'] ?? '';
    $data = json_read();

    if ($action === 'register') {
        $email = trim(mb_strtolower($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $name = trim($_POST['name'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($password) < 6) {
            flash('Введите корректный email и пароль минимум из 6 символов.', 'error');
            redirect_to('register');
        }

        foreach ($data['users'] as $row) {
            if ($row['email'] === $email) {
                flash('Такой email уже зарегистрирован.', 'error');
                redirect_to('register');
            }
        }

        $userId = (int)$data['next_user_id'];
        $data['next_user_id'] = $userId + 1;
        $data['users'][] = [
            'id' => $userId,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'created_at' => date('c'),
        ];
        $data['profiles'][(string)$userId] = ['name' => $name, 'phone' => '', 'city' => 'Москва', 'address' => ''];
        json_write($data);

        issue_auth_cookie($userId);
        flash('Профиль создан. Можно собирать демо-заказ.');
        redirect_to($_POST['next'] ?? 'profile');
    }

    if ($action === 'login') {
        $email = trim(mb_strtolower($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        foreach ($data['users'] as $row) {
            if ($row['email'] === $email && password_verify($password, $row['password_hash'])) {
                issue_auth_cookie((int)$row['id']);
                flash('Вы вошли в профиль.');
                redirect_to($_POST['next'] ?? 'menu');
            }
        }
        flash('Неверный email или пароль.', 'error');
        redirect_to('login');
    }

    if ($action === 'logout') {
        clear_auth_cookie();
        flash('Вы вышли из профиля.');
        redirect_to('home');
    }

    if ($action === 'profile') {
        $user = require_user();
        $data['profiles'][(string)$user['id']] = [
            'name' => trim($_POST['name'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'city' => trim($_POST['city'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
        ];
        json_write($data);
        flash('Профиль обновлен.');
        redirect_to('profile');
    }

    if ($action === 'password') {
        $user = require_user();
        $current = (string)($_POST['current_password'] ?? '');
        $new = (string)($_POST['new_password'] ?? '');
        foreach ($data['users'] as $index => $row) {
            if ((int)$row['id'] === (int)$user['id']) {
                if (!password_verify($current, $row['password_hash']) || mb_strlen($new) < 6) {
                    flash('Проверьте текущий пароль и задайте новый минимум из 6 символов.', 'error');
                    redirect_to('profile');
                }
                $data['users'][$index]['password_hash'] = password_hash($new, PASSWORD_DEFAULT);
                json_write($data);
                flash('Пароль изменен.');
                redirect_to('profile');
            }
        }
    }

    if ($action === 'cart-add') {
        $user = current_user();
        if (!$user) {
            flash('Войдите, чтобы добавить блюдо в корзину.', 'error');
            redirect_to('login', ['next' => 'menu']);
        }

        $productId = (int)($_POST['product_id'] ?? 0);
        if (json_product_by_id($data, $productId)) {
            $uid = (string)$user['id'];
            $data['cart_items'][$uid] = $data['cart_items'][$uid] ?? [];
            $data['cart_items'][$uid][(string)$productId] = (int)($data['cart_items'][$uid][(string)$productId] ?? 0) + 1;
            json_write($data);
        }
        flash('Блюдо добавлено в корзину.');
        redirect_to($_POST['from'] ?? 'menu');
    }

    if ($action === 'cart-update') {
        $user = require_user();
        $uid = (string)$user['id'];
        $productId = (string)(int)($_POST['product_id'] ?? 0);
        $qty = max(1, min(20, (int)($_POST['qty'] ?? 1)));
        if (isset($data['cart_items'][$uid][$productId])) {
            $data['cart_items'][$uid][$productId] = $qty;
            json_write($data);
        }
        redirect_to('cart');
    }

    if ($action === 'cart-remove') {
        $user = require_user();
        unset($data['cart_items'][(string)$user['id']][(string)(int)($_POST['product_id'] ?? 0)]);
        json_write($data);
        flash('Позиция удалена.');
        redirect_to('cart');
    }

    if ($action === 'cart-clear' || $action === 'checkout') {
        $user = require_user();
        $data['cart_items'][(string)$user['id']] = [];
        json_write($data);
        flash($action === 'checkout' ? 'Демо-заказ собран. В реальном продукте здесь был бы следующий шаг оформления.' : 'Корзина очищена.');
        redirect_to('cart');
    }
}

init_storage();
handle_post();
