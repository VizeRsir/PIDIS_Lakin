<?php
require_once __DIR__ . '/includes/functions.php';

// Главная страница: редирект authorised пользователей на дашборд,
// остальных — на страницу входа.
if (!empty($_SESSION['user_id'])) {
    $role = $_SESSION['role'] ?? 'user';
    $target = ($role === 'admin') ? '/admin/dashboard.php' : '/user/dashboard.php';
    header('Location: ' . BASE_URL . $target);
    exit;
}

header('Location: ' . BASE_URL . '/login.php');
exit;
