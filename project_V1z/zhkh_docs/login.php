<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/includes/functions.php';

$error = '';

// CSRF-токен для капчи (инициализируем, если ещё не создан)
if (empty($_SESSION['captcha_csrf'])) {
    $_SESSION['captcha_csrf'] = bin2hex(random_bytes(16));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login     = trim($_POST['login'] ?? '');
    $password  = $_POST['password'] ?? '';
    $captchaOk = ($_POST['captcha_ok'] ?? '0') === '1';

    if ($login === '' || $password === '') {
        $error = 'Поля «Логин» и «Пароль» обязательны для заполнения.';
    } elseif (!$captchaOk) {
        $error = 'Капча не пройдена. Соберите пазл правильно и повторите попытку.';
    } else {
        try {
            $stmt = db()->prepare('SELECT * FROM users WHERE login = ?');
            $stmt->execute([$login]);
            $user = $stmt->fetch();

            if (!$user) {
                $error = 'Вы ввели неверный логин или пароль. Пожалуйста проверьте ещё раз введенные данные';
            } elseif ($user['is_blocked']) {
                $error = 'Вы заблокированы. Обратитесь к администратору';
            } elseif (!password_verify($password, $user['password_hash'])) {
                $attempts = (int)$user['failed_attempts'] + 1;
                $blocked  = $attempts >= 3 ? 1 : 0;

                $upd = db()->prepare('UPDATE users SET failed_attempts = ?, is_blocked = ? WHERE id = ?');
                $upd->execute([$attempts, $blocked, $user['id']]);

                $error = $blocked
                    ? 'Вы заблокированы. Обратитесь к администратору'
                    : 'Вы ввели неверный логин или пароль. Пожалуйста проверьте ещё раз введенные данные';
            } else {
                $upd = db()->prepare('UPDATE users SET failed_attempts = 0 WHERE id = ?');
                $upd->execute([$user['id']]);

                $_SESSION['user_id']   = $user['id'];
                $_SESSION['login']     = $user['login'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role']      = $user['role'];
                $_SESSION['flash']     = 'Вы успешно авторизовались';

                header('Location: ' . BASE_URL .
                    ($user['role'] === 'admin' ? '/admin/dashboard.php' : '/user/dashboard.php'));
                exit;
            }
        } catch (Throwable $e) {
            $error = 'Ошибка сервера: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e($_SESSION['captcha_csrf'] ?? '') ?>">
    <title>Вход • ЖКХ</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="auth-wrap">
    <div class="auth-card">
        <a class="auth-logo" href="<?= BASE_URL ?>/">
            <span class="logo-badge">🏢</span>
            <div>
                <h1>ЖКХ • Документооборот</h1>
                <p>Информационная система управления</p>
            </div>
        </a>

        <h2 class="auth-title">Вход в систему</h2>
        <p class="auth-sub">Введите логин, пароль и соберите пазл</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" id="loginForm" autocomplete="off">
            <label>Логин <span class="req">*</span>
                <input type="text" name="login" required maxlength="50"
                       placeholder="Введите логин"
                       value="<?= e($_POST['login'] ?? '') ?>">
            </label>

            <label>Пароль <span class="req">*</span>
                <input type="password" name="password" required minlength="4"
                       placeholder="Введите пароль">
            </label>

            <fieldset class="captcha-block">
                <legend>Проверка «Я не робот»</legend>
                <p class="hint">Перетащите фрагменты так, чтобы получилась целая картинка.</p>
                <div id="puzzle" class="puzzle"></div>
                <div>
                    <button type="button" id="checkPuzzleBtn" class="btn btn-sm">Проверить пазл</button>
                    <button type="button" id="reloadPuzzleBtn" class="btn btn-sm" style="margin-left:6px;">Обновить</button>
                    <span id="puzzleStatus" class="puzzle-status"></span>
                </div>
                <input type="hidden" name="captcha_ok" id="captchaOk" value="0">
            </fieldset>

            <button type="submit" class="btn btn-primary btn-block">Войти</button>
        </form>
    </div>
</div>

<script>window.BASE_URL = '<?= BASE_URL ?>';</script>
<script src="<?= BASE_URL ?>/assets/js/captcha.js"></script>

<!-- Окно с тестовыми данными для входа -->
<div class="test-creds" id="testCreds">
    <div class="test-creds__head">
        <h3 class="test-creds__title">Тестовые данные для входа</h3>
        <button type="button" class="test-creds__close" id="testCredsClose" aria-label="Закрыть">&times;</button>
    </div>
    <p class="test-creds__hint">Нажмите на строку, чтобы подставить логин и пароль в форму.</p>
    <ul class="test-creds__list">
        <li class="test-creds__item" data-login="admin" data-password="admin123">
            <span class="test-creds__role test-creds__role--admin">Админ</span>
            <span class="test-creds__creds"><b>admin</b> / admin123</span>
        </li>
        <li class="test-creds__item" data-login="user1" data-password="user1">
            <span class="test-creds__role test-creds__role--user">Пользователь</span>
            <span class="test-creds__creds"><b>user1</b> / user1</span>
        </li>
    </ul>
</div>

<script>
(function(){
    var box = document.getElementById('testCreds');
    if (!box) return;
    var closeBtn = document.getElementById('testCredsClose');
    if (closeBtn) closeBtn.addEventListener('click', function(){ box.classList.add('is-hidden'); });
    box.querySelectorAll('.test-creds__item').forEach(function(item){
        item.addEventListener('click', function(){
            var loginInput = document.querySelector('input[name="login"]');
            var passInput = document.querySelector('input[name="password"]');
            if (loginInput) loginInput.value = item.getAttribute('data-login') || '';
            if (passInput) passInput.value = item.getAttribute('data-password') || '';
        });
    });
})();
</script>
<footer class="app-footer">
    © 1488 ЖКХ • Документооборот. Все права защищены.
</footer>
</body>
</html>