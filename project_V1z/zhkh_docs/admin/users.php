<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$msg = '';
$err = '';

// --- Добавление пользователя ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $login    = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    $fullName = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $role     = ($_POST['role'] ?? 'user') === 'admin' ? 'admin' : 'user';

    if ($login === '' || $password === '' || $fullName === '') {
        $err = 'Все обязательные поля должны быть заполнены.';
    } else {
        try {
            $check = db()->prepare('SELECT id FROM users WHERE login = ?');
            $check->execute([$login]);

            if ($check->fetch()) {
                $err = "Пользователь с логином «{$login}» уже существует в базе данных.";
            } else {
                $ins = db()->prepare(
                    'INSERT INTO users (login, password_hash, full_name, email, role)
                     VALUES (?,?,?,?,?)'
                );
                $ins->execute([
                    $login,
                    password_hash($password, PASSWORD_DEFAULT),
                    $fullName,
                    $email ?: null,
                    $role,
                ]);
                $msg = 'Пользователь успешно добавлен.';
            }
        } catch (Throwable $e) {
            $err = 'Ошибка при добавлении: ' . $e->getMessage();
        }
    }
}

// --- Снятие блокировки ---
if (($_GET['unblock'] ?? '') !== '') {
    try {
        $upd = db()->prepare('UPDATE users SET is_blocked = 0, failed_attempts = 0 WHERE id = ?');
        $upd->execute([(int)$_GET['unblock']]);
        $msg = 'Блокировка снята.';
    } catch (Throwable $e) {
        $err = 'Ошибка: ' . $e->getMessage();
    }
}

$users = db()->query('SELECT * FROM users ORDER BY id')->fetchAll();

require __DIR__ . '/../includes/header.php';
?>

<h2>Управление пользователями</h2>

<?php if ($msg): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endif; ?>

<div class="card">
    <h3>Добавить пользователя</h3>
    <form method="post">
        <input type="hidden" name="action" value="add">

        <label>Логин <span class="req">*</span>
            <input type="text" name="login" required maxlength="50" placeholder="ivanov">
        </label>

        <label>Пароль <span class="req">*</span>
            <input type="password" name="password" required minlength="4" placeholder="••••••">
        </label>

        <label>ФИО <span class="req">*</span>
            <input type="text" name="full_name" required maxlength="150" placeholder="Иванов Иван Иванович">
        </label>

        <label>Email
            <input type="email" name="email" maxlength="120" placeholder="ivanov@zhkh.local">
        </label>

        <label>Роль
            <select name="role">
                <option value="user">Пользователь</option>
                <option value="admin">Администратор</option>
            </select>
        </label>

        <button class="btn btn-primary" type="submit">+ Добавить пользователя</button>
    </form>
</div>

<h3>Список пользователей</h3>
<table class="table">
    <thead>
    <tr>
        <th>ID</th>
        <th>Логин</th>
        <th>ФИО</th>
        <th>Роль</th>
        <th>Статус</th>
        <th style="text-align:right;">Действия</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($users as $u): ?>
        <tr>
            <td>#<?= (int)$u['id'] ?></td>
            <td><strong><?= e($u['login']) ?></strong></td>
            <td><?= e($u['full_name']) ?></td>
            <td>
                <span class="badge <?= $u['role']==='admin' ? 'badge-admin' : 'badge-user' ?>">
                    <?= $u['role']==='admin' ? 'Администратор' : 'Пользователь' ?>
                </span>
            </td>
            <td>
                <?php if ($u['is_blocked']): ?>
                    <span class="badge badge-block">Заблокирован</span>
                <?php else: ?>
                    <span class="badge badge-ok">Активен</span>
                <?php endif; ?>
            </td>
            <td style="text-align:right; white-space:nowrap;">
                <a class="btn btn-sm btn-ghost"
                   href="<?= BASE_URL ?>/admin/user_edit.php?id=<?= (int)$u['id'] ?>">Изменить</a>
                <?php if ($u['is_blocked']): ?>
                    <a class="btn btn-sm btn-success"
                       href="?unblock=<?= (int)$u['id'] ?>">Разблокировать</a>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php require __DIR__ . '/../includes/footer.php'; ?>