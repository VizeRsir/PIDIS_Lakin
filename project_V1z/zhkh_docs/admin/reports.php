<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$periodFrom = trim($_GET['from'] ?? date('Y-m-01'));
$periodTo   = trim($_GET['to']   ?? date('Y-m-t'));
$serviceFilter = trim($_GET['service'] ?? '');

// --- Отчёт по начислениям ---
$chargesSql = "SELECT c.*, u.full_name, u.login FROM charges c LEFT JOIN users u ON u.id=c.user_id WHERE 1=1";
$chargesParams = [];
if ($periodFrom !== '') { $chargesSql .= " AND c.period >= ?"; $chargesParams[] = $periodFrom; }
if ($periodTo   !== '') { $chargesSql .= " AND c.period <= ?"; $chargesParams[] = $periodTo; }
if ($serviceFilter !== '') { $chargesSql .= " AND c.service = ?"; $chargesParams[] = $serviceFilter; }
$chargesSql .= " ORDER BY c.period DESC, c.created_at DESC";
$stmt = db()->prepare($chargesSql);
$stmt->execute($chargesParams);
$charges = $stmt->fetchAll();

// Итоги по начислениям
$totalCharged = 0.0; $totalPaid = 0.0; $totalUnpaid = 0.0;
foreach ($charges as $c) {
    $totalCharged += (float)$c['amount'];
    if ($c['status'] === 'paid') $totalPaid += (float)$c['amount'];
    else $totalUnpaid += (float)$c['amount'];
}

// --- Отчёт по объектам ---
$objects = db()->query("SELECT * FROM objects ORDER BY address, entrance")->fetchAll();
$totalArea   = 0.0; $totalResidents = 0; $activeObjects = 0;
foreach ($objects as $o) {
    $totalArea += (float)$o['area'];
    $totalResidents += (int)$o['residents_count'];
    if ((int)$o['is_active']===1) $activeObjects++;
}

// --- Отчёт по пользователям ---
$users = db()->query("SELECT id, login, full_name, role, is_blocked, created_at FROM users ORDER BY full_name")->fetchAll();
$totalUsers   = count($users);
$activeUsers  = 0; $blockedUsers = 0; $adminUsers = 0;
foreach ($users as $u) {
    if ((int)$u['is_blocked']===1) $blockedUsers++;
    else $activeUsers++;
    if ($u['role'] === 'admin') $adminUsers++;
}

// --- Отчёт по оплате по периодам ---
$periods = db()->query("SELECT period, SUM(amount) as summa, COUNT(*) as cnt FROM charges GROUP BY period ORDER BY period DESC")->fetchAll();

// --- Список услуг для фильтра ---
$services = db()->query("SELECT DISTINCT service FROM charges ORDER BY service")->fetchAll(PDO::FETCH_COLUMN);

require __DIR__ . '/../includes/header.php';
?>

<h2>Отчёты</h2>
<p class="auth-sub" style="margin-bottom:20px;">Аналитика начислений, объектов и пользователей</p>

<!-- Фильтр периода -->
<form method="get" class="card" style="padding:18px; display:flex; gap:10px; align-items:flex-end; flex-wrap:wrap;">
    <label style="margin:0; font-size:14px;">Период с
        <input type="month" name="from" value="<?= substr($periodFrom,0,7) ?>" style="padding:8px 10px; font-size:14px;">
    </label>
    <label style="margin:0; font-size:14px;">по
        <input type="month" name="to" value="<?= substr($periodTo,0,7) ?>" style="padding:8px 10px; font-size:14px;">
    </label>
    <label style="margin:0; font-size:14px;">Услуга
        <select name="service" style="padding:8px 10px; font-size:14px;">
            <option value="">Все</option>
            <?php foreach ($services as $s): ?>
                <option value="<?= e($s) ?>" <?= $serviceFilter===$s?'selected':'' ?>><?= e($s) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <button type="submit" class="btn btn-primary">Показать</button>
    <a class="btn btn-sm btn-ghost" href="<?= BASE_URL ?>/admin/reports.php">Сброс</a>
</form>

<!-- Краткие итоги -->
<div class="grid" style="grid-template-columns: repeat(auto-fit,minmax(210px,1fr)); gap:14px; margin-bottom:22px;">
    <div class="card" style="text-align:center;">
        <div style="font-size:12px; color:var(--text-muted); text-transform:uppercase; letter-spacing:.05em;">Начислено всего</div>
        <div style="font-size:28px; font-weight:700; color:var(--text);"><?= number_format($totalCharged,2,',',' ') ?> ₽</div>
    </div>
    <div class="card" style="text-align:center;">
        <div style="font-size:12px; color:var(--text-muted); text-transform:uppercase; letter-spacing:.05em;">Оплачено</div>
        <div style="font-size:28px; font-weight:700; color:var(--success);"><?= number_format($totalPaid,2,',',' ') ?> ₽</div>
    </div>
    <div class="card" style="text-align:center;">
        <div style="font-size:12px; color:var(--text-muted); text-transform:uppercase; letter-spacing:.05em;">Задолженность</div>
        <div style="font-size:28px; font-weight:700; color:var(--danger);"><?= number_format($totalUnpaid,2,',',' ') ?> ₽</div>
    </div>
    <div class="card" style="text-align:center;">
        <div style="font-size:12px; color:var(--text-muted); text-transform:uppercase; letter-spacing:.05em;">Объектов</div>
        <div style="font-size:28px; font-weight:700; color:var(--primary);"><?= count($objects) ?> (<span style="color:var(--success);"><?= $activeObjects ?> активных</span>)</div>
    </div>
    <div class="card" style="text-align:center;">
        <div style="font-size:12px; color:var(--text-muted); text-transform:uppercase; letter-spacing:.05em;">Площадь общая</div>
        <div style="font-size:28px; font-weight:700; color:var(--primary);"><?= number_format($totalArea,2,',',' ') ?> м²</div>
    </div>
    <div class="card" style="text-align:center;">
        <div style="font-size:12px; color:var(--text-muted); text-transform:uppercase; letter-spacing:.05em;">Пользователей</div>
        <div style="font-size:28px; font-weight:700; color:var(--primary);"><?= $totalUsers ?> (<span style="color:var(--danger);"><?= $blockedUsers ?> заблокировано</span>)</div>
    </div>
</div>

<!-- Таблица начислений -->
<div class="card">
    <h3 style="margin-top:0;">Начисления за период</h3>
    <?php if (empty($charges)): ?>
        <p style="margin:0; color:var(--text-muted);">Начислений не найдено.</p>
    <?php else: ?>
    <div style="overflow:auto;">
    <table class="table">
        <thead>
        <tr>
            <th>Период</th><th>Услуга</th><th>Пользователь</th><th>Сумма</th><th>Статус</th><th>Создано</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($charges as $c): ?>
        <tr>
            <td><?= e($c['period']) ?></td>
            <td><?= e($c['service']) ?></td>
            <td><?= e($c['full_name'] ?? '—') ?> <span style="color:var(--text-muted); font-size:12px;">(<?= e($c['login'] ?? '—') ?>)</span></td>
            <td><strong><?= number_format((float)$c['amount'],2,',',' ') ?> ₽</strong></td>
            <td>
                <?php if ($c['status']==='paid'): ?><span class="badge badge-ok">Оплачено</span>
                <?php else: ?><span class="badge badge-block">Не оплачено</span><?php endif; ?>
            </td>
            <td style="font-size:13px; color:var(--text-muted);"><?= e($c['created_at']) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
        <tr>
            <td colspan="3" style="text-align:right;"><strong>Итого:</strong></td>
            <td><strong><?= number_format($totalCharged,2,',',' ') ?> ₽</strong></td>
            <td colspan="2"></td>
        </tr>
        </tfoot>
    </table>
    </div>
    <?php endif; ?>
</div>

<!-- Отчёт по объектам -->
<div class="card" style="margin-top:22px;">
    <h3 style="margin-top:0;">Объекты и жильцы</h3>
    <p style="font-size:13px; color:var(--text-muted); margin:0 0 10px;">Всего объектов: <strong><?= count($objects) ?></strong>, из них активных: <strong><?= $activeObjects ?></strong>, жильцов: <strong><?= $totalResidents ?></strong>, общая площадь: <strong><?= number_format($totalArea,2,',',' ') ?> м²</strong></p>
    <?php if (empty($objects)): ?>
        <p style="margin:0; color:var(--text-muted);">Объектов нет.</p>
    <?php else: ?>
    <div style="overflow:auto;">
    <table class="table">
        <thead>
        <tr><th>Адрес</th><th>Подъезд</th><th>Лицевой счёт</th><th>Площадь</th><th>Жителей</th><th>Собственник</th><th>Статус</th></tr>
        </thead>
        <tbody>
        <?php foreach ($objects as $o): ?>
        <tr>
            <td><?= e($o['address']) ?></td>
            <td><?= e($o['entrance'] ?: '—') ?></td>
            <td><strong><?= e($o['account_number']) ?></strong></td>
            <td><?= number_format((float)$o['area'],2,',',' ') ?> м²</td>
            <td><?= (int)$o['residents_count'] ?></td>
            <td><?= e($o['owner_name'] ?: '—') ?></td>
            <td><?= (int)$o['is_active']===1 ? '<span class="badge badge-ok">Активно</span>' : '<span class="badge badge-block">В архиве</span>' ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<!-- Отчёт по пользователям -->
<div class="card" style="margin-top:22px;">
    <h3 style="margin-top:0;">Пользователи системы</h3>
    <p style="font-size:13px; color:var(--text-muted); margin:0 0 10px;">Всего: <strong><?= $totalUsers ?></strong>, активных: <strong><?= $activeUsers ?></strong>, заблокировано: <strong style="color:var(--danger);"><?= $blockedUsers ?></strong>, администраторов: <strong><?= $adminUsers ?></strong></p>
    <?php if (empty($users)): ?>
        <p style="margin:0; color:var(--text-muted);">Пользователей нет.</p>
    <?php else: ?>
    <div style="overflow:auto;">
    <table class="table">
        <thead>
        <tr><th>Логин</th><th>Имя</th><th>Роль</th><th>Статус</th><th>Зарегистрирован</th></tr>
        </thead>
        <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
            <td><strong><?= e($u['login']) ?></strong></td>
            <td><?= e($u['full_name']) ?></td>
            <td><?= $u['role']==='admin' ? '<span class="badge badge-admin">Админ</span>' : '<span class="badge badge-user">Пользователь</span>' ?></td>
            <td><?= (int)$u['is_blocked']===1 ? '<span class="badge badge-block">Заблокирован</span>' : '<span class="badge badge-ok">Активен</span>' ?></td>
            <td style="font-size:13px; color:var(--text-muted);"><?= e($u['created_at']) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
