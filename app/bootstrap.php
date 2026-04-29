<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
define('DB_PATH', BASE_PATH . '/database/app.sqlite');
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

    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');
    migrate($pdo);

    return $pdo;
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
    $products = [
        ['Бургеры', 'Биг Спешиал', 'Большой бургер с двумя рублеными бифштексами из говядины, сыром, салатом, луком и фирменным соусом.', 'product-1.png', 299, '340 г', 1],
        ['Бургеры', 'Биг Хит', 'Сочный бургер с двумя говяжьими котлетами, маринованными огурчиками, луком и соусом в мягкой булочке.', 'product-2.png', 186, '228 г', 1],
        ['Бургеры', 'Гранд Де Люкс', 'Бифштекс из говядины, свежие овощи, сыр и насыщенный соус для большого обеда.', 'product-3.png', 219, '251 г', 0],
        ['Бургеры', 'Чикен Премьер', 'Куриная котлета в хрустящей панировке, свежий салат, сыр и нежный соус.', 'product-4.png', 173, '234 г', 1],
        ['Роллы', 'Цезарь Ролл', 'Курица, свежий салат, сыр и соус в мягкой тортилье: легкий формат для быстрого перекуса.', 'product-5.png', 190, '211 г', 0],
        ['Картофель и снеки', 'Картофель Фри', 'Классический золотистый картофель с хрустящей корочкой и мягкой серединкой.', 'product-6.png', 115, '100 г', 1],
        ['Картофель и снеки', 'Наггетсы 9 шт.', 'Куриное филе в хрустящей панировке. Хорошо дружит с сырным, барбекю или кисло-сладким соусом.', 'product-7.png', 199, '156 г', 0],
        ['Бургеры', 'Гранд', 'Говяжий бифштекс, сыр, свежие овощи и соус в мягкой булочке с кунжутом.', 'product-8.png', 193, '202 г', 0],
        ['Напитки', 'Капучино', 'Горячий кофе с молочной пенкой для спокойной паузы между делами.', 'product-9.png', 145, '300 мл', 0],
        ['Десерты', 'Вишневый пирожок', 'Горячий пирожок с яркой вишневой начинкой и хрустящим тестом.', 'product-10.png', 79, '80 г', 1],
        ['Соусы', 'Соус Сырный', 'Нежный сырный соус для картофеля, наггетсов и всего, что хочется сделать еще вкуснее.', 'product-11.png', 45, '25 мл', 0],
        ['Бургеры', 'Двойной Биг Хит', 'Большой бургер с четырьмя говяжьими котлетами, огурчиками, луком и фирменным соусом.', 'product-12.png', 248, '303 г', 0],
    ];

    $stmt = $pdo->prepare('
        INSERT INTO products (category, title, description, image, price, weight, is_featured)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ');

    foreach ($products as $product) {
        $stmt->execute($product);
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
    if ($category) {
        $stmt = db()->prepare('SELECT * FROM products WHERE category = ? ORDER BY id');
        $stmt->execute([$category]);
        return $stmt->fetchAll();
    }
    return db()->query('SELECT * FROM products ORDER BY category, id')->fetchAll();
}

function featured_products(): array
{
    return db()->query('SELECT * FROM products WHERE is_featured = 1 ORDER BY id LIMIT 3')->fetchAll();
}

function categories(): array
{
    return db()->query('SELECT DISTINCT category FROM products ORDER BY category')->fetchAll(PDO::FETCH_COLUMN);
}

function cart_items(int $userId): array
{
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
    $stmt = db()->prepare('SELECT COALESCE(SUM(qty), 0) FROM cart_items WHERE user_id = ?');
    $stmt->execute([(int)$user['id']]);
    return (int)$stmt->fetchColumn();
}

function handle_post(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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

db();
handle_post();
