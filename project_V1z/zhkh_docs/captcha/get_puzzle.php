<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

// Фрагменты картинки. Правильный порядок сборки — фрагменты идут по порядку 1,2,3,4,
// иначе картинка не сложится. Поэтому правильный ответ фиксирован, а защита
// реализуется через уникальный одноразовый токен и CSRF (см. check_puzzle.php).
$parts   = [1, 2, 3, 4];
$answer  = $parts;
$token   = bin2hex(random_bytes(16)); // уникальный токен головоломки

// Перемешиваем начальный порядок (чтобы не совпадал с правильным)
do {
    $shuffled = $parts;
    shuffle($shuffled);
} while ($shuffled === $answer);

// Сохраняем в сессии (одноразовая головоломка)
$_SESSION['puzzle_answer'] = $answer;
$_SESSION['puzzle_token']  = $token;
$_SESSION['puzzle_used']   = false;

// CSRF-токен для проверки при отправке решения
if (empty($_SESSION['captcha_csrf'])) {
    $_SESSION['captcha_csrf'] = bin2hex(random_bytes(16));
}

echo json_encode([
    'ok'    => true,
    'token' => $token,
    'csrf'  => $_SESSION['captcha_csrf'],
    'parts' => $shuffled,
    'baseUrl' => BASE_URL . '/assets/img/puzzle/part',
]);