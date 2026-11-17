<?php
require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

$response = ['ok' => false, 'error' => ''];

// Только POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$token     = $_POST['token']     ?? '';
$orderRaw  = $_POST['order']     ?? '';
$csrf      = $_POST['csrf']      ?? '';

// Проверка CSRF-токена
if (empty($_SESSION['captcha_csrf']) || !hash_equals((string)$_SESSION['captcha_csrf'], $csrf)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'CSRF-токен неверен или истёк. Обновите страницу.']);
    exit;
}

// Головоломка должна быть выдана и не использована
$answer   = $_SESSION['puzzle_answer'] ?? null;
$puzzleToken = $_SESSION['puzzle_token'] ?? null;
$used     = $_SESSION['puzzle_used'] ?? true;

if ($answer === null || $puzzleToken === null || $used) {
    echo json_encode(['ok' => false, 'error' => 'Головоломка не найдена или уже использована. Обновите страницу.']);
    exit;
}

// Проверка токена головоломки
if (!hash_equals($puzzleToken, $token)) {
    echo json_encode(['ok' => false, 'error' => 'Неверный токен головоломки.']);
    exit;
}

// Разбор порядка фрагментов
$order = array_map('intval', explode(',', $orderRaw));
if ($order === [] || count($order) !== count($answer)) {
    echo json_encode(['ok' => false, 'error' => 'Неверный формат ответа.']);
    exit;
}

$correct = ($order === $answer);

// Головоломка становится использованной после успешного прохождения,
// чтобы нельзя было отправить одно решение несколько раз.
if ($correct) {
    $_SESSION['puzzle_used'] = true;
    echo json_encode(['ok' => true, 'message' => 'Капча пройдена']);
} else {
    // При неудаче оставляем головоломку активной, чтобы можно было попробовать ещё раз.
    echo json_encode(['ok' => false, 'error' => 'Собрано неверно. Попробуйте ещё раз.']);
}