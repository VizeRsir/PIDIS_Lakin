<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

require __DIR__ . '/../includes/header.php';

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);
?>

<?php if ($flash): ?>
    <div class="alert alert-success"><?= e($flash) ?></div>
<?php endif; ?>

<div class="card" style="background:linear-gradient(135deg,#2563EB,#1E40AF); color:#fff; border:none;">
    <h2 style="color:#fff; margin:0 0 4px;">Добро пожаловать, <?= e($_SESSION['full_name']) ?> 👋</h2>
    <p style="opacity:.9; margin:0;">Панель администратора системы документооборота ЖКХ</p>
</div>

<div class="grid">
    <a class="card link" href="<?= BASE_URL ?>/admin/users.php">
        <div class="card-title">👥 Пользователи</div>
        <p class="card-sub">Добавление, редактирование, снятие блокировки</p>
    </a>
    <a class="card link" href="<?= BASE_URL ?>/admin/charges.php">
        <div class="card-title">💳 Начисления</div>
        <p class="card-sub">Создание и контроль оплаты ЖКУ</p>
    </a>
    <a class="card link" href="<?= BASE_URL ?>/admin/tickets.php">
        <div class="card-title">📨 Обращения</div>
        <p class="card-sub">Обработка запросов граждан</p>
    </a>
    <a class="card link" href="<?= BASE_URL ?>/admin/documents.php">
        <div class="card-title">📄 Документы ЖКХ</div>
        <p class="card-sub">Реестр входящих и исходящих документов</p>
    </a>
    <a class="card link" href="<?= BASE_URL ?>/admin/reports.php">
        <div class="card-title">📊 Отчёты</div>
        <p class="card-sub">Формирование отчётов по документообороту</p>
    </a>
    <a class="card link" href="<?= BASE_URL ?>/admin/objects.php">
        <div class="card-title">🏠 Объекты</div>
        <p class="card-sub">Дома, подъезды, лицевые счета</p>
    </a>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>