<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$msg = '';
$err = '';
$adminId = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'respond') {
    $id = (int)($_POST['id'] ?? 0);
    $response = trim($_POST['response'] ?? '');
    $status = in_array($_POST['status'] ?? '', ['new','in_progress','answered','closed'], true) ? $_POST['status'] : 'answered';
    if ($id > 0) {
        db()->prepare('UPDATE tickets SET response=?, status=?, responded_by=? WHERE id=?')
            ->execute([$response?:null, $status, $adminId, $id]);
        $msg = 'Ответ сохранён.';
    }
}

$statusFilter = trim($_GET['status'] ?? '');
$allowed = ['new','in_progress','answered','closed'];
$sql = "SELECT t.*, u.full_name, u.login FROM tickets t LEFT JOIN users u ON u.id=t.user_id";
if ($statusFilter !== '' && in_array($statusFilter,$allowed,true)) {
    $sql .= ' WHERE t.status = ?';
    $stmt = db()->prepare($sql.' ORDER BY t.created_at DESC');
    $stmt->execute([$statusFilter]);
} else {
    $stmt = db()->query($sql.' ORDER BY t.created_at DESC');
}
$rows = $stmt->fetchAll();

$statusLabels = ['new'=>'Новое','in_progress'=>'В работе','answered'=>'Отвечено','closed'=>'Закрыто'];
$prioLabels = ['low'=>'Низкий','normal'=>'Обычный','high'=>'Высокий'];

require __DIR__ . '/../includes/header.php';
?>

<h2>Обращения граждан</h2>

<?php if ($msg): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endif; ?>

<form method="get" style="margin-bottom:16px; display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
    <label style="margin:0;">Статус
        <select name="status" onchange="this.form.submit()">
            <option value="">Все</option>
            <?php foreach ($statusLabels as $k=>$v): ?>
                <option value="<?= e($k) ?>" <?= $statusFilter===$k?'selected':'' ?>><?= e($v) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <?php if ($statusFilter !== ''): ?>
        <a class="btn btn-sm btn-ghost" href="<?= BASE_URL ?>/admin/tickets.php">Сбросить</a>
    <?php endif; ?>
</form>

<?php if (empty($rows)): ?>
    <div class="card"><p style="margin:0; color:var(--text-muted);">Обращений нет.</p></div>
<?php else: ?>
<?php foreach ($rows as $t): ?>
<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px; margin-bottom:8px;">
        <div>
            <strong><?= e($t['subject']) ?></strong>
            <span style="color:var(--text-muted); font-size:12px; margin-left:8px;">
                от <?= e($t['full_name']) ?> (<?= e($t['login']) ?>) · <?= e($t['created_at']) ?>
            </span>
        </div>
        <span class="badge <?= $t['status']==='closed'?'badge-ok':($t['status']==='new'?'badge-block':'badge-user') ?>"><?= e($statusLabels[$t['status']] ?? $t['status']) ?></span>
    </div>
    <p style="margin:0 0 8px; white-space:pre-wrap;"><?= e($t['message']) ?></p>
    <div style="font-size:12px; color:var(--text-muted); margin-bottom:10px;">
        Приоритет: <?= e($prioLabels[$t['priority']]) ?>
    </div>
    <form method="post">
        <input type="hidden" name="action" value="respond">
        <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
        <label>Ответ
            <textarea name="response" rows="3" placeholder="Текст ответа заявителю"><?= e($t['response'] ?? '') ?></textarea>
        </label>
        <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
            <label style="margin:0;">Статус
                <select name="status">
                    <?php foreach ($statusLabels as $k=>$v): ?>
                        <option value="<?= e($k) ?>" <?= ($t['status']===$k)?'selected':'' ?>><?= e($v) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button type="submit" class="btn btn-primary btn-sm">Сохранить ответ</button>
        </div>
    </form>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
