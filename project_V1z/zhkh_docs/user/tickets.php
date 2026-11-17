<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();

$uid = (int)$_SESSION['user_id'];
$err = '';
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $priority = in_array($_POST['priority'] ?? '', ['low','normal','high'], true) ? $_POST['priority'] : 'normal';
    if ($subject === '' || $message === '') {
        $err = 'Тема и текст обращения обязательны.';
    } else {
        $ins = db()->prepare('INSERT INTO tickets (user_id, subject, message, priority) VALUES (?,?,?,?)');
        $ins->execute([$uid, $subject, $message, $priority]);
        $msg = 'Обращение отправлено. Мы свяжемся с вами.';
    }
}

$tickets = db()->prepare('SELECT * FROM tickets WHERE user_id=? ORDER BY created_at DESC');
$tickets->execute([$uid]);
$rows = $tickets->fetchAll();

$statusLabels = ['new'=>'Новое','in_progress'=>'В работе','answered'=>'Отвечено','closed'=>'Закрыто'];
$prioLabels = ['low'=>'Низкий','normal'=>'Обычный','high'=>'Высокий'];

require __DIR__ . '/../includes/header.php';
?>

<h2>Обращения</h2>
<p class="auth-sub">Отправить запрос в управляющую компанию и отследить статус.</p>

<?php if ($msg): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endif; ?>

<div class="card">
    <h3>Новое обращение</h3>
    <form method="post">
        <input type="hidden" name="action" value="create">
        <label>Тема <span class="req">*</span>
            <input type="text" name="subject" required maxlength="200" placeholder="Кратко опишите проблему">
        </label>
        <label>Приоритет
            <select name="priority">
                <option value="normal">Обычный</option>
                <option value="high">Высокий</option>
                <option value="low">Низкий</option>
            </select>
        </label>
        <label>Текст обращения <span class="req">*</span>
            <textarea name="message" required rows="4" placeholder="Подробно опишите ситуацию"></textarea>
        </label>
        <button type="submit" class="btn btn-primary">Отправить</button>
    </form>
</div>

<h3>Мои обращения</h3>
<?php if (empty($rows)): ?>
    <div class="card"><p style="margin:0; color:var(--text-muted);">Обращений пока нет.</p></div>
<?php else: ?>
<?php foreach ($rows as $t): ?>
<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px; margin-bottom:8px;">
        <strong><?= e($t['subject']) ?></strong>
        <span class="badge <?= $t['status']==='closed'?'badge-ok':($t['status']==='new'?'badge-block':'badge-user') ?>"><?= e($statusLabels[$t['status']] ?? $t['status']) ?></span>
    </div>
    <p style="margin:0 0 8px; white-space:pre-wrap;"><?= e($t['message']) ?></p>
    <div style="font-size:12px; color:var(--text-muted);">
        Приоритет: <?= e($prioLabels[$t['priority']]) ?> · Создано: <?= e($t['created_at']) ?>
    </div>
    <?php if (!empty($t['response'])): ?>
    <div style="margin-top:10px; padding:10px 12px; background:#F0FDF4; border-left:3px solid var(--success); border-radius:6px;">
        <div style="font-size:12px; font-weight:600; color:#166534; margin-bottom:4px;">Ответ управляющей компании</div>
        <div style="white-space:pre-wrap;"><?= e($t['response']) ?></div>
    </div>
    <?php endif; ?>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
