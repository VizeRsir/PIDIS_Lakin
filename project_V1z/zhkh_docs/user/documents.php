<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();

$uid = (int)$_SESSION['user_id'];
$statusFilter = trim($_GET['status'] ?? '');
$allowedStatuses = ['new','in_progress','approved','rejected'];

$where = '(d.author_id = ? OR d.assignee_id = ?)';
$params = [$uid, $uid];
if ($statusFilter !== '' && in_array($statusFilter, $allowedStatuses, true)) {
    $where .= ' AND d.status = ?';
    $params[] = $statusFilter;
}

$sql = "SELECT d.*, dt.name AS type_name,
               ua.full_name AS author_name, ua.login AS author_login,
               uu.full_name AS assignee_name
        FROM documents d
        LEFT JOIN document_types dt ON dt.id = d.type_id
        LEFT JOIN users ua ON ua.id = d.author_id
        LEFT JOIN users uu ON uu.id = d.assignee_id
        WHERE $where
        ORDER BY d.created_at DESC";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$docs = $stmt->fetchAll();

$statusLabels = [
    'new' => 'Новый', 'in_progress' => 'В работе',
    'approved' => 'Утверждён', 'rejected' => 'Отклонён',
];
$prioLabels = ['low'=>'Низкий','normal'=>'Обычный','high'=>'Высокий'];

require __DIR__ . '/../includes/header.php';
?>

<h2>Мои документы</h2>
<p class="auth-sub">Документы, в которых вы являетесь автором или исполнителем.</p>

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
        <a class="btn btn-sm btn-ghost" href="<?= BASE_URL ?>/user/documents.php">Сбросить</a>
    <?php endif; ?>
</form>

<?php if (empty($docs)): ?>
    <div class="card"><p style="margin:0; color:var(--text-muted);">Документов не найдено.</p></div>
<?php else: ?>
<div class="card" style="padding:0; overflow:auto;">
<table class="table">
    <thead>
    <tr>
        <th>Рег. №</th>
        <th>Название</th>
        <th>Тип</th>
        <th>Статус</th>
        <th>Приоритет</th>
        <th>Срок</th>
        <th>Создан</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($docs as $d): ?>
    <tr>
        <td><strong><?= e($d['reg_number']) ?></strong></td>
        <td><?= e($d['title']) ?></td>
        <td><?= e($d['type_name'] ?? '—') ?></td>
        <td><span class="badge <?= $d['status']==='approved'?'badge-ok':($d['status']==='rejected'?'badge-block':'badge-user') ?>"><?= e($statusLabels[$d['status']] ?? $d['status']) ?></span></td>
        <td><?= e($prioLabels[$d['priority']] ?? $d['priority']) ?></td>
        <td><?= $d['due_date'] ? e($d['due_date']) : '—' ?></td>
        <td><?= e($d['created_at']) ?></td>
    </tr>
    <?php if (!empty($d['description'])): ?>
    <tr>
        <td colspan="7" style="background:#F8FAFC; font-size:13px; color:var(--text-muted);">
            <strong>Описание:</strong> <?= e($d['description']) ?>
            <?php if (!empty($d['assignee_name'])): ?> · <strong>Исполнитель:</strong> <?= e($d['assignee_name']) ?><?php endif; ?>
        </td>
    </tr>
    <?php endif; ?>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
