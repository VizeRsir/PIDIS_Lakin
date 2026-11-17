<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$msg = '';
$err = '';

// --- Создание объекта ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    $address    = trim($_POST['address'] ?? '');
    $entrance   = trim($_POST['entrance'] ?? '');
    $account    = trim($_POST['account_number'] ?? '');
    $area       = (float)str_replace(',', '.', $_POST['area'] ?? '0');
    $residents  = (int)($_POST['residents_count'] ?? 1);
    $owner      = trim($_POST['owner_name'] ?? '');
    $isActive   = isset($_POST['is_active']) ? 1 : 0;
    if ($address === '' || $account === '' || $area <= 0) {
        $err = 'Заполните адрес, лицевой счёт и площадь.';
    } else {
        try {
            db()->prepare('INSERT INTO objects (address,entrance,account_number,area,residents_count,owner_name,is_active) VALUES (?,?,?,?,?,?,?)')
                ->execute([$address,$entrance,$account,$area,$residents,$owner?:null,$isActive]);
            $msg = 'Объект «' . e($account) . '» добавлен.';
        } catch (Throwable $e) {
            if (strpos($e->getMessage(), 'account_number') !== false) {
                $err = 'Лицевой счёт «' . e($account) . '» уже существует.';
            } else {
                $err = 'Ошибка: ' . $e->getMessage();
            }
        }
    }
}

// --- Обновление объекта ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
    $id       = (int)($_POST['id'] ?? 0);
    $address  = trim($_POST['address'] ?? '');
    $entrance = trim($_POST['entrance'] ?? '');
    $account  = trim($_POST['account_number'] ?? '');
    $area     = (float)str_replace(',', '.', $_POST['area'] ?? '0');
    $residents= (int)($_POST['residents_count'] ?? 1);
    $owner    = trim($_POST['owner_name'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    if ($id > 0 && $address !== '' && $account !== '' && $area > 0) {
        db()->prepare('UPDATE objects SET address=?,entrance=?,account_number=?,area=?,residents_count=?,owner_name=?,is_active=? WHERE id=?')
            ->execute([$address,$entrance,$account,$area,$residents,$owner?:null,$isActive,$id]);
        $msg = 'Объект обновлён.';
    } else {
        $err = 'Некорректные данные объекта.';
    }
}

// --- Удаление объекта ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        db()->prepare('DELETE FROM objects WHERE id=?')->execute([$id]);
        $msg = 'Объект удалён.';
    }
}

// --- Переключение активности ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle') {
    $id      = (int)($_POST['id'] ?? 0);
    $active  = (int)($_POST['is_active'] ?? 0);
    if ($id > 0) {
        db()->prepare('UPDATE objects SET is_active=? WHERE id=?')->execute([$active,$id]);
        $msg = 'Статус объекта изменён.';
    }
}

// Фильтр и поиск
$search   = trim($_GET['search'] ?? '');
$activeFilter = trim($_GET['is_active'] ?? '');
$sql = "SELECT * FROM objects WHERE 1=1";
$params = [];
if ($search !== '') {
    $sql .= " AND (address LIKE ? OR account_number LIKE ? OR owner_name LIKE ?)";
    $like = "%$search%";
    $params[] = $like; $params[] = $like; $params[] = $like;
}
if ($activeFilter !== '') {
    $sql .= " AND is_active = ?";
    $params[] = $activeFilter;
}
$sql .= " ORDER BY address, entrance";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

require __DIR__ . '/../includes/header.php';
?>

<h2>Объекты ЖКХ</h2>
<p class="auth-sub" style="margin-bottom:16px;">Дома, подъезды, лицевые счета и жильцы</p>

<?php if ($msg): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endif; ?>

<div class="card">
    <h3>Добавить объект</h3>
    <form method="post">
        <input type="hidden" name="action" value="create">
        <div class="grid" style="grid-template-columns: repeat(auto-fit,minmax(200px,1fr)); gap:12px;">
            <label>Адрес дома <span class="req">*</span>
                <input type="text" name="address" required placeholder="ул. Ленина, д. 10">
            </label>
            <label>Подъезд
                <input type="text" name="entrance" placeholder="1">
            </label>
            <label>Лицевой счёт <span class="req">*</span>
                <input type="text" name="account_number" required placeholder="ЛС-001001">
            </label>
            <label>Площадь (м²) <span class="req">*</span>
                <input type="text" name="area" required placeholder="54.20">
            </label>
            <label>Проживающих
                <input type="number" name="residents_count" min="1" value="1">
            </label>
            <label>Собственник
                <input type="text" name="owner_name" placeholder="Иванов И.И.">
            </label>
            <label style="align-self:end;">Активный
                <input type="checkbox" name="is_active" value="1" checked style="width:auto; margin:0;">
            </label>
        </div>
        <button type="submit" class="btn btn-primary">+ Добавить объект</button>
    </form>
</div>

<div class="card" style="margin-top:18px;">
    <form method="get" style="display:flex; gap:8px; align-items:flex-end; flex-wrap:wrap; margin-bottom:12px;">
        <input type="text" name="search" placeholder="Поиск по адресу, счёту, владельцу" value="<?= e($search) ?>" style="min-width:260px;">
        <select name="is_active" style="min-width:150px;">
            <option value=""><?= $activeFilter==='' ? 'Все' : '' ?></option>
            <option value="1" <?= $activeFilter==='1'?'selected':'' ?>>Активные</option>
            <option value="0" <?= $activeFilter==='0'?'selected':'' ?>>Архивные</option>
        </select>
        <button type="submit" class="btn btn-sm btn-primary">Поиск</button>
        <?php if ($search!=='' || $activeFilter!==''): ?>
            <a class="btn btn-sm btn-ghost" href="<?= BASE_URL ?>/admin/objects.php">Сбросить</a>
        <?php endif; ?>
    </form>

    <?php if (empty($rows)): ?>
        <div class="card"><p style="margin:0; color:var(--text-muted);">Объектов не найдено.</p></div>
    <?php else: ?>
    <div class="card" style="padding:0; overflow:auto;">
    <table class="table">
        <thead>
        <tr>
            <th>Адрес</th>
            <th>Подъезд</th>
            <th>Лицевой счёт</th>
            <th>Площадь</th>
            <th>Жителей</th>
            <th>Собственник</th>
            <th>Статус</th>
            <th style="text-align:right;">Действия</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $o): ?>
        <tr>
            <td><?= e($o['address']) ?></td>
            <td><?= e($o['entrance'] ?: '—') ?></td>
            <td><strong><?= e($o['account_number']) ?></strong></td>
            <td><?= number_format((float)$o['area'],2,',',' ') ?> м²</td>
            <td><?= (int)$o['residents_count'] ?></td>
            <td><?= e($o['owner_name'] ?: '—') ?></td>
            <td>
                <?php if ((int)$o['is_active']===1): ?>
                    <span class="badge badge-ok">Активно</span>
                <?php else: ?>
                    <span class="badge badge-block">В архиве</span>
                <?php endif; ?>
            </td>
            <td style="text-align:right; white-space:nowrap;">
                <button type="button" class="btn btn-sm btn-ghost" onclick="openEdit(<?= (int)$o['id'] ?>, <?= json_encode(e($o['address'])) ?>, <?= json_encode(e($o['entrance'])) ?>, <?= json_encode(e($o['account_number'])) ?>, <?= (float)$o['area'] ?>, <?= (int)$o['residents_count'] ?>, <?= json_encode(e($o['owner_name'])) ?>, <?= (int)$o['is_active'] ?>)">Редактировать</button>
                <form method="post" style="display:inline;" onsubmit="return confirm('Удалить объект «<?= e($o['account_number']) ?>»?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int)$o['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-ghost" style="color:var(--danger);">Удалить</button>
                </form>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="action" value="toggle">
                    <input type="hidden" name="id" value="<?= (int)$o['id'] ?>">
                    <input type="hidden" name="is_active" value="<?= (int)$o['is_active']===1 ? 0 : 1 ?>">
                    <button type="submit" class="btn btn-sm btn-ghost"><?= (int)$o['is_active']===1 ? 'В архив' : 'Восстановить' ?></button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<!-- Редактирование (модальное) -->
<div id="editModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:1000; align-items:center; justify-content:center;">
    <div style="background:#fff; padding:24px; border-radius:var(--radius-md); min-width:420px; box-shadow:var(--shadow-md);">
        <h3 style="margin-top:0;">Редактировать объект</h3>
        <form method="post" style="display:grid; gap:12px;">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="editId">
            <label>Адрес <span class="req">*</span><input type="text" name="address" id="editAddress" required></label>
            <label>Подъезд <input type="text" name="entrance" id="editEntrance"></label>
            <label>Лицевой счёт <span class="req">*</span><input type="text" name="account_number" id="editAccount" required></label>
            <label>Площадь (м²) <span class="req">*</span><input type="text" name="area" id="editArea" required></label>
            <label>Жителей <input type="number" name="residents_count" id="editResidents" min="1"></label>
            <label>Собственник <input type="text" name="owner_name" id="editOwner"></label>
            <label style="display:flex; align-items:center; gap:8px;">
                <input type="checkbox" name="is_active" id="editActive" value="1">
                Активный
            </label>
            <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:8px;">
                <button type="button" class="btn btn-ghost" id="closeEdit">Отмена</button>
                <button type="submit" class="btn btn-primary">Сохранить</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEdit(id, address, entrance, account, area, residents, owner, active){
    document.getElementById('editId').value = id;
    document.getElementById('editAddress').value = address;
    document.getElementById('editEntrance').value = entrance;
    document.getElementById('editAccount').value = account;
    document.getElementById('editArea').value = area;
    document.getElementById('editResidents').value = residents;
    document.getElementById('editOwner').value = owner;
    document.getElementById('editActive').checked = active===1;
    document.getElementById('editModal').style.display = 'flex';
}
document.getElementById('closeEdit').onclick = () => {
    document.getElementById('editModal').style.display = 'none';
};
window.onclick = (e) => {
    if (e.target === document.getElementById('editModal')) document.getElementById('editModal').style.display = 'none';
};
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
