<?php
declare(strict_types=1);

define('VIT_PUBLIC_ENTRY', true);

error_reporting(E_ALL);
ini_set('display_errors', '0');

register_shutdown_function(static function (): void {
    $error = error_get_last();
    if (!$error || !in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        return;
    }

    if (headers_sent() === false) {
        http_response_code(500);
        header('Content-Type: text/html; charset=UTF-8');
    }

    echo '<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>Ошибка PHP</title><style>body{margin:0;background:#F8F4E8;color:#1B1B1B;font-family:Arial,sans-serif}main{max-width:820px;margin:8vh auto;padding:28px;background:#fff;border:2px solid #1B1B1B;border-radius:8px;box-shadow:8px 8px 0 #F7BE23}.msg{white-space:pre-wrap;padding:14px;background:#ffe8df;border-radius:8px;color:#9d260f}code{background:#f5f1e7;padding:2px 5px;border-radius:4px}</style></head><body><main>';
    echo '<h1>Фатальная ошибка PHP</h1>';
    echo '<p>Эта диагностическая страница нужна для настройки прод-сервера.</p>';
    echo '<div class="msg">' . htmlspecialchars($error['message'] . "\n" . $error['file'] . ':' . $error['line'], ENT_QUOTES, 'UTF-8') . '</div>';
    echo '</main></body></html>';
});

if (isset($_GET['health'])) {
    header('Content-Type: text/html; charset=UTF-8');
    $root = dirname(__DIR__);
    $defaultDb = $root . '/database/app.sqlite';
    $fallbackDb = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'vkusno-demo-app.sqlite';
    $selectedDb = ((is_dir($root . '/database') || @mkdir($root . '/database', 0775, true)) && is_writable($root . '/database')) ? $defaultDb : $fallbackDb;
    $checks = [
        'PHP version' => PHP_VERSION,
        'SAPI' => PHP_SAPI,
        'public/index.php' => __FILE__,
        'project root' => $root,
        'app/bootstrap.php exists' => is_file($root . '/app/bootstrap.php') ? 'yes' : 'no',
        'storage mode' => (getenv('VIT_FORCE_JSON') === '1' || !extension_loaded('pdo_sqlite')) ? 'json fallback' : 'sqlite',
        'selected sqlite path' => $selectedDb,
        'json fallback path' => rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'vkusno-demo-app.json',
        'selected sqlite dir writable' => is_writable(dirname($selectedDb)) ? 'yes' : 'no',
        'database dir exists' => is_dir($root . '/database') ? 'yes' : 'no',
        'database dir writable' => is_writable($root . '/database') ? 'yes' : 'no',
        'database/app.sqlite exists' => is_file($root . '/database/app.sqlite') ? 'yes' : 'no',
        'database/app.sqlite writable' => is_file($root . '/database/app.sqlite') ? (is_writable($root . '/database/app.sqlite') ? 'yes' : 'no') : 'n/a',
        'pdo_sqlite loaded' => extension_loaded('pdo_sqlite') ? 'yes' : 'no',
        'sqlite3 loaded' => extension_loaded('sqlite3') ? 'yes' : 'no',
        'openssl loaded' => extension_loaded('openssl') ? 'yes' : 'no',
        'mbstring loaded' => extension_loaded('mbstring') ? 'yes' : 'no',
    ];
    echo '<!doctype html><html lang="ru"><head><meta charset="utf-8"><title>VIT health</title><style>body{font-family:Arial,sans-serif;background:#F8F4E8;color:#1B1B1B}main{max-width:900px;margin:40px auto;background:#fff;padding:24px;border:2px solid #1B1B1B;border-radius:8px}td{padding:8px 12px;border-bottom:1px solid #ddd}td:first-child{font-weight:700}</style></head><body><main><h1>VIT health</h1><table>';
    foreach ($checks as $name => $value) {
        echo '<tr><td>' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</td><td>' . htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') . '</td></tr>';
    }
    echo '</table></main></body></html>';
    exit;
}

require __DIR__ . '/../app/bootstrap.php';

$currentRoute = route();
$user = current_user();
$flash = flash();
$cartCount = cart_count($user);

if (in_array($currentRoute, ['cart', 'profile'], true) && !$user) {
    redirect_to('login', ['next' => $currentRoute]);
}

function active(string $route, string $currentRoute): string
{
    return $route === $currentRoute ? ' class="active"' : '';
}

function product_card(array $product, string $from): void
{
    ?>
    <article class="product-card">
        <div class="product-image-wrap">
            <img src="<?= h(asset_url('images/products/' . $product['image'])) ?>" alt="<?= h($product['title']) ?>" class="product-image">
        </div>
        <div class="product-body">
            <div class="product-meta">
                <span><?= h($product['category']) ?></span>
                <span><?= h($product['weight']) ?></span>
            </div>
            <h3><?= h($product['title']) ?></h3>
            <p><?= h($product['description']) ?></p>
        </div>
        <div class="product-actions">
            <strong><?= (int)$product['price'] ?> ₽</strong>
            <form method="post" action="<?= h(url_for(route())) ?>">
                <input type="hidden" name="action" value="cart-add">
                <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                <input type="hidden" name="from" value="<?= h($from) ?>">
                <button class="btn btn-red" type="submit">В корзину</button>
            </form>
        </div>
    </article>
    <?php
}
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Вкусно — и точка | Демо</title>
    <link rel="stylesheet" href="<?= h(asset_url('style.css')) ?>">
</head>
<body>
<header class="site-header">
    <a href="<?= url_for('home') ?>" class="brand" aria-label="На главную">
        <img src="<?= h(asset_url('images/logo-soft.png')) ?>" alt="Вкусно — и точка">
        <span>вкусно<br>и точка</span>
    </a>
    <nav class="main-nav" aria-label="Основная навигация">
        <a<?= active('home', $currentRoute) ?> href="<?= url_for('home') ?>">Главная</a>
        <a<?= active('menu', $currentRoute) ?> href="<?= url_for('menu') ?>">Меню</a>
        <a<?= active('cart', $currentRoute) ?> href="<?= url_for('cart') ?>">Корзина<?= $cartCount ? ' · ' . $cartCount : '' ?></a>
        <a<?= active('profile', $currentRoute) ?> href="<?= url_for('profile') ?>">Профиль</a>
    </nav>
    <div class="header-actions">
        <?php if ($user): ?>
            <span class="hello"><?= h($user['name'] ?: $user['email']) ?></span>
            <form method="post" action="<?= h(url_for($currentRoute)) ?>">
                <input type="hidden" name="action" value="logout">
                <button class="link-button" type="submit">Выйти</button>
            </form>
        <?php else: ?>
            <a class="btn btn-green btn-small" href="<?= url_for('login') ?>">Войти</a>
        <?php endif; ?>
    </div>
</header>

<?php if ($flash): ?>
    <div class="flash <?= h($flash['type']) ?>"><?= h($flash['message']) ?></div>
<?php endif; ?>

<main>
<?php if ($currentRoute === 'home'): ?>
    <section class="hero">
        <div class="hero-copy">
            <h1>Новый вкусный сайт для теплой компании</h1>
            <p>Демо-версия с меню, корзиной, профилем и дружелюбной визуальной системой: молочный фон, томатный акцент, свежий зеленый и веселые food-стикеры.</p>
            <div class="hero-actions">
                <a class="btn btn-red" href="<?= url_for('menu') ?>">Смотреть меню</a>
                <a class="btn btn-green" href="<?= url_for($user ? 'profile' : 'register') ?>"><?= $user ? 'Мой профиль' : 'Создать профиль' ?></a>
            </div>
        </div>
        <div class="hero-art">
            <img src="<?= h(asset_url('images/logo-framed.png')) ?>" alt="Дружелюбные картофель и бургер">
            <div class="sticker sticker-red">горячо</div>
            <div class="sticker sticker-green">свежее</div>
        </div>
    </section>

    <section class="section-head">
        <h2>Любимое рядом</h2>
        <p>Популярные позиции из демо-меню можно сразу отправить в корзину.</p>
    </section>
    <section class="product-grid featured-grid">
        <?php foreach (featured_products() as $product): ?>
            <?php product_card($product, 'home'); ?>
        <?php endforeach; ?>
    </section>

<?php elseif ($currentRoute === 'menu'): ?>
    <?php
    $selectedCategory = $_GET['category'] ?? null;
    $items = products($selectedCategory);
    ?>
    <section class="page-title menu-title">
        <div>
            <h1>Меню</h1>
            <p>Официальный ассортимент в компактной демо-подборке: бургеры, снеки, напитки, десерты и соусы.</p>
        </div>
        <img src="<?= h(asset_url('images/logo-soft.png')) ?>" alt="">
    </section>
    <div class="category-tabs">
        <a class="<?= $selectedCategory ? '' : 'active' ?>" href="<?= url_for('menu') ?>">Все</a>
        <?php foreach (categories() as $category): ?>
            <a class="<?= $selectedCategory === $category ? 'active' : '' ?>" href="<?= url_for('menu', ['category' => $category]) ?>"><?= h($category) ?></a>
        <?php endforeach; ?>
    </div>
    <section class="product-grid">
        <?php foreach ($items as $product): ?>
            <?php product_card($product, 'menu'); ?>
        <?php endforeach; ?>
    </section>

<?php elseif ($currentRoute === 'cart'): ?>
    <?php $user = require_user(); $items = cart_items((int)$user['id']); $total = cart_total($items); ?>
    <section class="page-title">
        <div>
            <h1>Корзина</h1>
            <p>Это демо-корзина: позиции сохраняются в SQLite и привязаны к вашему профилю.</p>
        </div>
    </section>
    <?php if (!$items): ?>
        <section class="empty-state">
            <img src="<?= h(asset_url('images/logo-soft.png')) ?>" alt="">
            <h2>Корзина пока пустая</h2>
            <p>Добавьте бургер, картофель или напиток из меню.</p>
            <a class="btn btn-red" href="<?= url_for('menu') ?>">Перейти в меню</a>
        </section>
    <?php else: ?>
        <section class="cart-layout">
            <div class="cart-list">
                <?php foreach ($items as $item): ?>
                    <article class="cart-row">
                        <img src="<?= h(asset_url('images/products/' . $item['image'])) ?>" alt="<?= h($item['title']) ?>">
                        <div>
                            <h3><?= h($item['title']) ?></h3>
                            <p><?= h($item['weight']) ?> · <?= (int)$item['price'] ?> ₽</p>
                        </div>
                        <form method="post" action="<?= h(url_for('cart')) ?>" class="qty-form">
                            <input type="hidden" name="action" value="cart-update">
                            <input type="hidden" name="product_id" value="<?= (int)$item['id'] ?>">
                            <input type="number" name="qty" min="1" max="20" value="<?= (int)$item['qty'] ?>" aria-label="Количество">
                            <button class="btn btn-small btn-green" type="submit">OK</button>
                        </form>
                        <strong><?= (int)$item['price'] * (int)$item['qty'] ?> ₽</strong>
                        <form method="post" action="<?= h(url_for('cart')) ?>">
                            <input type="hidden" name="action" value="cart-remove">
                            <input type="hidden" name="product_id" value="<?= (int)$item['id'] ?>">
                            <button class="icon-button" type="submit" aria-label="Удалить">×</button>
                        </form>
                    </article>
                <?php endforeach; ?>
            </div>
            <aside class="cart-summary">
                <h2>Итого</h2>
                <div class="summary-line"><span>Блюд</span><strong><?= $cartCount ?></strong></div>
                <div class="summary-line total"><span>Сумма</span><strong><?= $total ?> ₽</strong></div>
                <form method="post" action="<?= h(url_for('cart')) ?>">
                    <input type="hidden" name="action" value="checkout">
                    <button class="btn btn-red full" type="submit">Собрать демо-заказ</button>
                </form>
                <form method="post" action="<?= h(url_for('cart')) ?>">
                    <input type="hidden" name="action" value="cart-clear">
                    <button class="link-button full-link" type="submit">Очистить корзину</button>
                </form>
            </aside>
        </section>
    <?php endif; ?>

<?php elseif ($currentRoute === 'login' || $currentRoute === 'register'): ?>
    <?php $isRegister = $currentRoute === 'register'; $next = $_GET['next'] ?? ($_POST['next'] ?? 'menu'); ?>
    <section class="auth-shell">
        <div class="auth-card">
            <h1><?= $isRegister ? 'Создать профиль' : 'Войти' ?></h1>
            <p><?= $isRegister ? 'Профиль нужен для корзины, адреса и смены пароля.' : 'Войдите, чтобы продолжить собирать корзину.' ?></p>
            <form method="post" action="<?= h(url_for($currentRoute)) ?>" class="form-stack">
                <input type="hidden" name="action" value="<?= $isRegister ? 'register' : 'login' ?>">
                <input type="hidden" name="next" value="<?= h($next) ?>">
                <?php if ($isRegister): ?>
                    <label>Имя
                        <input name="name" placeholder="Например, Аня">
                    </label>
                <?php endif; ?>
                <label>Email
                    <input type="email" name="email" required placeholder="you@example.com">
                </label>
                <label>Пароль
                    <input type="password" name="password" required minlength="6" placeholder="Минимум 6 символов">
                </label>
                <button class="btn btn-red full" type="submit"><?= $isRegister ? 'Зарегистрироваться' : 'Войти' ?></button>
            </form>
            <p class="auth-switch">
                <?= $isRegister ? 'Уже есть профиль?' : 'Еще нет профиля?' ?>
                <a href="<?= url_for($isRegister ? 'login' : 'register', ['next' => $next]) ?>"><?= $isRegister ? 'Войти' : 'Создать' ?></a>
            </p>
        </div>
        <img src="<?= h(asset_url('images/logo-framed.png')) ?>" alt="">
    </section>

<?php elseif ($currentRoute === 'profile'): ?>
    <?php $user = require_user(); ?>
    <section class="page-title profile-title">
        <div>
            <h1>Профиль</h1>
            <p>Примитивный личный кабинет для демо: контакты, адрес и смена пароля.</p>
        </div>
    </section>
    <section class="profile-grid">
        <form method="post" action="<?= h(url_for('profile')) ?>" class="panel form-stack">
            <input type="hidden" name="action" value="profile">
            <h2>Контакты</h2>
            <label>Email
                <input value="<?= h($user['email']) ?>" disabled>
            </label>
            <label>Имя
                <input name="name" value="<?= h($user['name']) ?>" placeholder="Ваше имя">
            </label>
            <label>Телефон
                <input name="phone" value="<?= h($user['phone']) ?>" placeholder="+7 900 000-00-00">
            </label>
            <label>Город
                <input name="city" value="<?= h($user['city']) ?>" placeholder="Москва">
            </label>
            <label>Адрес
                <textarea name="address" rows="3" placeholder="Улица, дом, подъезд"><?= h($user['address']) ?></textarea>
            </label>
            <button class="btn btn-green" type="submit">Сохранить профиль</button>
        </form>
        <form method="post" action="<?= h(url_for('profile')) ?>" class="panel form-stack">
            <input type="hidden" name="action" value="password">
            <h2>Смена пароля</h2>
            <label>Текущий пароль
                <input type="password" name="current_password" required>
            </label>
            <label>Новый пароль
                <input type="password" name="new_password" minlength="6" required>
            </label>
            <button class="btn btn-red" type="submit">Обновить пароль</button>
        </form>
    </section>

<?php else: ?>
    <section class="empty-state">
        <h1>Страница не найдена</h1>
        <a class="btn btn-red" href="<?= url_for('home') ?>">На главную</a>
    </section>
<?php endif; ?>
</main>

<footer class="site-footer">
    <span>Демо нового сайта «Вкусно — и точка»</span>
    <span>PHP · SQLite · JWT cookie</span>
</footer>
</body>
</html>
