<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    $userId = (int)($_POST['user_id'] ?? 0);
    $period = trim($_POST['period'] ?? '');
    $service = trim($_POST['service'] ?? '');
    $amount = (float)str_replace(',', '.', $_POST['amount'] ?? '0');
    $dueDate = trim($_POST['due_date'] ?? '');
    if ($userId <= 0 || $period === '' || $service === '' || $amount <= 0) {
        $err = 'Заполните пользователя, период, услугу и сумму.';
    } else {
        db()->prepare('INSERT INTO charges (user_id,period,service,amount,due_date) VALUES (?,?,?,?,?)')
            ->execute([$userId,$period,$service,$amount,$dueDate?:null]);
        $msg = 'Начисление создано.';
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'pay') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        db()->prepare("UPDATE charges SET status='paid', paid_at=NOW() WHERE id=?")->execute([$id]);
        $msg = 'Отмечено как оплаченное.';
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        db()->prepare('DELETE FROM charges WHERE id=?')->execute([$id]);
        $msg = 'Начисление удалено.';
    }
}

$users = db()->query('SELECT id, full_name FROM users WHERE role="user" ORDER BY full_name')->fetchAll();
$rows = db()->query("SELECT c.*, u.full_name, u.login FROM charges c LEFT JOIN users u ON u.id=c.user_id ORDER BY c.created_at DESC")->fetchAll();

require __DIR__ . '/../includes/header.php';
?>

<h2>Начисления (ЖКУ)</h2>

<?php if ($msg): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endif; ?>

<div class="card">
    <h3>Новое начисление</h3>
    <form method="post">
        <input type="hidden" name="action" value="create">
        <div class="grid" style="grid-template-columns: repeat(auto-fit,minmax(180px,1fr)); gap:12px;">
            <label>Пользователь <span class="req">*</span>
                <select name="user_id" required>
                    <option value="">— выберите —</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= (int)$u['id'] ?>"><?= e($u['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Период <span class="req">*</span>
                <input type="date" name="period" required>
            </label>
            <label>Услуга <span class="req">*</span>
                <select name="service" required>
                    <option value="">— выберите услугу —</option>
                    <option>Содержание жилья</option>
                    <option>Холодное водоснабжение</option>
                    <option>Горячее водоснабжение</option>
                    <option>Отопление</option>
                    <option>Электроснабжение</option>
                    <option>Вывоз мусора</option>
                </select>
            </label>
            <label>Сумма (₽) <span class="req">*</span>
                <input type="text" name="amount" required placeholder="1250.50">
            </label>
            <label>Срок оплаты
                <input type="date" name="due_date">
            </label>
        </div>
        <button type="submit" class="btn btn-primary">+ Создать начисление</button>
    </form>
</div>

<?php if (empty($rows)): ?>
    <div class="card"><p style="margin:0; color:var(--text-muted);">Начислений нет.</p></div>
<?php else: ?>
<div class="card" style="padding:0; overflow:auto;">
<table class="table">
    <thead>
    <tr>
        <th>Пользователь</th>
        <th>Период</th>
        <th>Услуга</th>
        <th>Сумма</th>
        <th>Статус</th>
        <th>Срок</th>
        <th style="text-align:right;">Действия</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
    <tr>
        <td><?= e($r['full_name']) ?> <span style="color:var(--text-muted); font-size:12px;">(<?= e($r['login']) ?>)</span></td>
        <td><?= e($r['period']) ?></td>
        <td><?= e($r['service']) ?></td>
        <td><strong><?= number_format((float)$r['amount'],2,',',' ') ?> ₽</strong></td>
        <td>
            <?php if ($r['status']==='paid'): ?>
                <span class="badge badge-ok">Оплачено</span>
            <?php else: ?>
                <span class="badge badge-block">Не оплачено</span>
            <?php endif; ?>
        </td>
        <td><?= $r['due_date'] ? e($r['due_date']) : '—' ?></td>
        <td style="text-align:right; white-space:nowrap;">
            <?php if ($r['status']==='unpaid'): ?>
            <form method="post" style="display:inline;">
                <input type="hidden" name="action" value="pay">
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button type="submit" class="btn btn-sm btn-success">Отметить оплаченным</button>
            </form>
            <?php endif; ?>
            <form method="post" style="display:inline;" onsubmit="return confirm('Удалить начисление?');">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button type="submit" class="btn btn-sm btn-ghost">Удалить</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
