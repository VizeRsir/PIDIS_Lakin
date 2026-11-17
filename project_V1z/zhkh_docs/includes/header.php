<?php
require_once __DIR__ . '/functions.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ЖКХ • Документооборот</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<header class="app-header">
    <a class="app-brand" href="<?= BASE_URL ?>/">
        <span class="logo-badge">🏢</span>
        <span>ЖКХ • Документооборот</span>
    </a>
    <nav class="app-nav">
        <?php if (!empty($_SESSION['user_id'])): ?>
            <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                <a href="<?= BASE_URL ?>/admin/dashboard.php">Панель</a>
                <a href="<?= BASE_URL ?>/admin/users.php">Пользователи</a>
                <a href="<?= BASE_URL ?>/admin/documents.php">Документы</a>
                <a href="<?= BASE_URL ?>/admin/charges.php">Начисления</a>
                <a href="<?= BASE_URL ?>/admin/tickets.php">Обращения</a>
                <a href="<?= BASE_URL ?>/admin/objects.php">Объекты</a>
                <a href="<?= BASE_URL ?>/admin/reports.php">Отчёты</a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/user/dashboard.php">Кабинет</a>
                <a href="<?= BASE_URL ?>/user/documents.php">Мои документы</a>
                <a href="<?= BASE_URL ?>/user/charges.php">Начисления</a>
                <a href="<?= BASE_URL ?>/user/tickets.php">Обращения</a>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>/logout.php">Выход (<?= e($_SESSION['login'] ?? '') ?>)</a>
        <?php else: ?>
            <a href="<?= BASE_URL ?>/login.php">Войти</a>
        <?php endif; ?>
    </nav>
</header>
<main class="container">
