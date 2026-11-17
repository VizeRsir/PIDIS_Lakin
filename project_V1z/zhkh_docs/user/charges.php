<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();

$uid = (int)$_SESSION['user_id'];

// Демо-оплата
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'pay') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $upd = db()->prepare("UPDATE charges SET status='paid', paid_at=NOW() WHERE id=? AND user_id=? AND status='unpaid'");
        $upd->execute([$id, $uid]);
        $_SESSION['flash'] = 'Начисление отмечено как оплаченное.';
        header('Location: ' . BASE_URL . '/user/charges.php');
        exit;
    }
}

$charges = db()->prepare("SELECT * FROM charges WHERE user_id=? ORDER BY created_at DESC");
$charges->execute([$uid]);
$rows = $charges->fetchAll();

$totalUnpaid = 0;
foreach ($rows as $r) if ($r['status']==='unpaid') $totalUnpaid += (float)$r['amount'];

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

require __DIR__ . '/../includes/header.php';
?>

<h2>Начисления</h2>
<p class="auth-sub">Квитанции и оплата жилищно-коммунальных услуг.</p>

<?php if ($flash): ?><div class="alert alert-success"><?= e($flash) ?></div><?php endif; ?>

<div class="card" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
    <div>
        <div style="font-size:13px; color:var(--text-muted);">К оплате</div>
        <div style="font-size:22px; font-weight:700; color:var(--danger);"><?= number_format($totalUnpaid,2,',',' ') ?> ₽</div>
    </div>
    <div style="font-size:13px; color:var(--text-muted);">Всего начислений: <?= count($rows) ?></div>
</div>

<?php if (empty($rows)): ?>
    <div class="card"><p style="margin:0; color:var(--text-muted);">Начислений не найдено.</p></div>
<?php else: ?>
<div class="card" style="padding:0; overflow:auto;">
<table class="table">
    <thead>
    <tr>
        <th>Период</th>
        <th>Услуга</th>
        <th>Сумма</th>
        <th>Статус</th>
        <th>Срок оплаты</th>
        <th style="text-align:right;">Действие</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
    <tr>
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
        <td style="text-align:right;">
            <?php if ($r['status']==='unpaid'): ?>
            <form method="post" style="display:inline;">
                <input type="hidden" name="action" value="pay">
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button type="submit" class="btn btn-sm btn-success">Оплатить</button>
            </form>
            <?php else: ?>
                <span style="font-size:12px; color:var(--text-muted);"><?= e($r['paid_at']) ?></span>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
