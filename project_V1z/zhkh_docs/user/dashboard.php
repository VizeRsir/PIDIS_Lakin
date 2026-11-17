<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();

require __DIR__ . '/../includes/header.php';

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);
?>

<?php if ($flash): ?>
    <div class="alert alert-success"><?= e($flash) ?></div>
<?php endif; ?>

<div class="card" style="background:linear-gradient(135deg,#2563EB,#1E40AF); color:#fff; border:none;">
    <h2 style="color:#fff; margin:0 0 4px;">Здравствуйте, <?= e($_SESSION['full_name']) ?>!</h2>
    <p style="opacity:.9; margin:0;">Личный кабинет системы документооборота ЖКХ</p>
</div>

<div class="grid">
    <a class="card link" href="<?= BASE_URL ?>/user/documents.php">
        <div class="card-title">📄 Мои документы</div>
        <p class="card-sub">Заявки, акты, обращения</p>
    </a>
    <a class="card link" href="<?= BASE_URL ?>/user/charges.php">
        <div class="card-title">💳 Начисления</div>
        <p class="card-sub">Квитанции и оплата ЖКУ</p>
    </a>
    <a class="card link" href="<?= BASE_URL ?>/user/tickets.php">
        <div class="card-title">📨 Обращения</div>
        <p class="card-sub">Отправить запрос в управляющую компанию</p>
    </a>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>