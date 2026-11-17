<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    require __DIR__ . '/../includes/header.php';
    echo '<div class="alert alert-error">Пользователь не найден.</div>';
    echo '<a class="btn" href="' . BASE_URL . '/admin/users.php">← Назад к списку</a>';
    require __DIR__ . '/../includes/footer.php';
    exit;
}

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName  = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $role      = ($_POST['role'] ?? 'user') === 'admin' ? 'admin' : 'user';
    $isBlocked = isset($_POST['is_blocked']) ? 1 : 0;
    $newPass   = $_POST['password'] ?? '';

    if ($fullName === '') {
        $err = 'Поле «ФИО» обязательно.';
    } else {
        try {
            if ($newPass !== '') {
                $upd = db()->prepare(
                    'UPDATE users
                     SET full_name=?, email=?, role=?, is_blocked=?,
                         failed_attempts=0, password_hash=?
                     WHERE id=?'
                );
                $upd->execute([
                    $fullName, $email ?: null, $role, $isBlocked,
                    password_hash($newPass, PASSWORD_DEFAULT), $id
                ]);
            } else {
                $upd = db()->prepare(
                    'UPDATE users
                     SET full_name=?, email=?, role=?, is_blocked=?
                     WHERE id=?'
                );
                $upd->execute([$fullName, $email ?: null, $role, $isBlocked, $id]);
            }

            $msg = 'Изменения сохранены.';

            $stmt->execute([$id]);
            $user = $stmt->fetch();
        } catch (Throwable $e) {
            $err = 'Ошибка: ' . $e->getMessage();
        }
    }
}

require __DIR__ . '/../includes/header.php';
?>

<h2>Редактирование: <?= e($user['login']) ?></h2>

<?php if ($msg): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endif; ?>

<div class="card" style="max-width:600px;">
    <form method="post">
        <label>Логин
            <input type="text" value="<?= e($user['login']) ?>" disabled>
        </label>

        <label>ФИО <span class="req">*</span>
            <input type="text" name="full_name" required value="<?= e($user['full_name']) ?>">
        </label>

        <label>Email
            <input type="email" name="email" value="<?= e($user['email'] ?? '') ?>">
        </label>

        <label>Роль
            <select name="role">
                <option value="user"  <?= $user['role']==='user'  ? 'selected' : '' ?>>Пользователь</option>
                <option value="admin" <?= $user['role']==='admin' ? 'selected' : '' ?>>Администратор</option>
            </select>
        </label>

        <label>Новый пароль (оставьте пустым, чтобы не менять)
            <input type="password" name="password" minlength="4" placeholder="••••••">
        </label>

        <label style="display:flex; align-items:center; gap:8px;">
            <input type="checkbox" name="is_blocked" style="width:auto;"
                   <?= $user['is_blocked'] ? 'checked' : '' ?>>
            Заблокирован
        </label>

        <div style="display:flex; gap:10px;">
            <button class="btn btn-primary" type="submit">Сохранить</button>
            <a class="btn btn-ghost" href="<?= BASE_URL ?>/admin/users.php">← Назад</a>
        </div>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>