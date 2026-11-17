<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$msg = '';
$err = '';
$adminId = (int)$_SESSION['user_id'];

// --- Создание документа ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    $title = trim($_POST['title'] ?? '');
    $typeId = (int)($_POST['type_id'] ?? 0);
    $assigneeId = (int)($_POST['assignee_id'] ?? 0);
    $status = in_array($_POST['status'] ?? '', ['new','in_progress','approved','rejected'], true) ? $_POST['status'] : 'new';
    $priority = in_array($_POST['priority'] ?? '', ['low','normal','high'], true) ? $_POST['priority'] : 'normal';
    $dueDate = trim($_POST['due_date'] ?? '');
    $description = trim($_POST['description'] ?? '');
    if ($title === '' || $typeId <= 0) {
        $err = 'Заполните название и тип документа.';
    } else {
        try {
            // генерация рег. номера
            $year = date('Y');
            $cnt = (int)db()->query("SELECT COUNT(*) FROM documents WHERE reg_number LIKE 'ЖКХ-$year-%'")->fetchColumn();
            $reg = sprintf('ЖКХ-%s-%04d', $year, $cnt + 1);
            $ins = db()->prepare('INSERT INTO documents (reg_number,title,type_id,author_id,assignee_id,status,priority,description,due_date) VALUES (?,?,?,?,?,?,?,?,?)');
            $ins->execute([$reg,$title,$typeId,$adminId,$assigneeId?:null,$status,$priority,$description?:null,$dueDate?:null]);
            $msg = "Документ \"$reg\" создан.";
        } catch (Throwable $e) { $err = 'Ошибка: '.$e->getMessage(); }
    }
}

// --- Смена статуса ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'status') {
    $id = (int)($_POST['id'] ?? 0);
    $status = in_array($_POST['status'] ?? '', ['new','in_progress','approved','rejected'], true) ? $_POST['status'] : '';
    if ($id>0 && $status!=='') {
        db()->prepare('UPDATE documents SET status=? WHERE id=?')->execute([$status,$id]);
        $msg = 'Статус обновлён.';
    }
}

// --- Назначение исполнителя ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'assign') {
    $id = (int)($_POST['id'] ?? 0);
    $assigneeId = (int)($_POST['assignee_id'] ?? 0);
    if ($id>0) {
        db()->prepare('UPDATE documents SET assignee_id=? WHERE id=?')->execute([$assigneeId?:null,$id]);
        $msg = 'Исполнитель назначен.';
    }
}

// данные
$types = db()->query('SELECT * FROM document_types WHERE is_active=1 ORDER BY name')->fetchAll();
$users = db()->query('SELECT id, full_name, role FROM users ORDER BY full_name')->fetchAll();
$statusFilter = trim($_GET['status'] ?? '');
$allowed = ['new','in_progress','approved','rejected'];
$sql = "SELECT d.*, dt.name AS type_name, ua.full_name AS author_name, uu.full_name AS assignee_name
        FROM documents d
        LEFT JOIN document_types dt ON dt.id=d.type_id
        LEFT JOIN users ua ON ua.id=d.author_id
        LEFT JOIN users uu ON uu.id=d.assignee_id";
if ($statusFilter !== '' && in_array($statusFilter,$allowed,true)) {
    $sql .= ' WHERE d.status = ?';
    $stmt = db()->prepare($sql.' ORDER BY d.created_at DESC');
    $stmt->execute([$statusFilter]);
} else {
    $stmt = db()->query($sql.' ORDER BY d.created_at DESC');
}
$docs = $stmt->fetchAll();

$statusLabels = ['new'=>'Новый','in_progress'=>'В работе','approved'=>'Утверждён','rejected'=>'Отклонён'];
$prioLabels = ['low'=>'Низкий','normal'=>'Обычный','high'=>'Высокий'];

require __DIR__ . '/../includes/header.php';
?>

<h2>Реестр документов</h2>

<?php if ($msg): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endif; ?>

<div class="card">
    <h3>Новый документ</h3>
    <form method="post">
        <input type="hidden" name="action" value="create">
        <label>Название <span class="req">*</span>
            <input type="text" name="title" required maxlength="200" placeholder="Название документа">
        </label>
        <div class="grid" style="grid-template-columns: repeat(auto-fit,minmax(180px,1fr)); gap:12px;">
            <label>Тип <span class="req">*</span>
                <select name="type_id" required>
                    <option value="">— выберите —</option>
                    <?php foreach ($types as $t): ?>
                        <option value="<?= (int)$t['id'] ?>"><?= e($t['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Исполнитель
                <select name="assignee_id">
                    <option value="0">— не назначен —</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= (int)$u['id'] ?>"><?= e($u['full_name']) ?> (<?= e($u['role']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Статус
                <select name="status">
                    <?php foreach ($statusLabels as $k=>$v): ?>
                        <option value="<?= e($k) ?>" <?= $k==='new'?'selected':'' ?>><?= e($v) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Приоритет
                <select name="priority">
                    <?php foreach ($prioLabels as $k=>$v): ?>
                        <option value="<?= e($k) ?>" <?= $k==='normal'?'selected':'' ?>><?= e($v) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Срок исполнения
                <input type="date" name="due_date">
            </label>
        </div>
        <label>Описание
            <textarea name="description" rows="3" placeholder="Описание документа"></textarea>
        </label>
        <button type="submit" class="btn btn-primary">+ Создать документ</button>
    </form>
</div>

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
        <a class="btn btn-sm btn-ghost" href="<?= BASE_URL ?>/admin/documents.php">Сбросить</a>
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
        <th>Автор</th>
        <th>Исполнитель</th>
        <th>Статус</th>
        <th>Приоритет</th>
        <th>Срок</th>
        <th style="text-align:right;">Действия</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($docs as $d): ?>
    <tr>
        <td><strong><?= e($d['reg_number']) ?></strong></td>
        <td><?= e($d['title']) ?></td>
        <td><?= e($d['type_name'] ?? '—') ?></td>
        <td><?= e($d['author_name'] ?? '—') ?></td>
        <td>
            <form method="post" style="display:inline;">
                <input type="hidden" name="action" value="assign">
                <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                <select name="assignee_id" onchange="this.form.submit()" style="padding:4px 6px; font-size:12px;">
                    <option value="0">— не назначен —</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= (int)$u['id'] ?>" <?= ((int)$d['assignee_id']===(int)$u['id'])?'selected':'' ?>><?= e($u['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
        </td>
        <td>
            <form method="post" style="display:inline;">
                <input type="hidden" name="action" value="status">
                <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                <select name="status" onchange="this.form.submit()" style="padding:4px 6px; font-size:12px;">
                    <?php foreach ($statusLabels as $k=>$v): ?>
                        <option value="<?= e($k) ?>" <?= $d['status']===$k?'selected':'' ?>><?= e($v) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
        </td>
        <td><?= e($prioLabels[$d['priority']] ?? $d['priority']) ?></td>
        <td><?= $d['due_date'] ? e($d['due_date']) : '—' ?></td>
        <td style="text-align:right; white-space:nowrap;">
            <span style="font-size:12px; color:var(--text-muted);"><?= e($d['created_at']) ?></span>
        </td>
    </tr>
    <?php if (!empty($d['description'])): ?>
    <tr>
        <td colspan="9" style="background:#F8FAFC; font-size:13px; color:var(--text-muted);">
            <?= e($d['description']) ?>
        </td>
    </tr>
    <?php endif; ?>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
