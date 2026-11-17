<?php
/**
 * GET /api/notes
 * Возвращает список заметок в формате JSON.
 *
 * Формат ответа:
 * [
 *   {
 *     "id": 1,
 *     "title_user": "Конференция ИТ - user25",
 *     "content": "...",
 *     "formatted_date": "15.03.2027"
 *   },
 *   ...
 * ]
 *
 * HTTP-статусы:
 *   200 — успех (в т.ч. пустой массив)
 *   400 — неверный параметр запроса
 *   405 — не GET
 *   500 — ошибка сервера / БД
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// ---------- 1. Проверка метода ----------
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Метод не разрешён. Используйте GET.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ---------- 2. Валидация GET-параметров ----------
// Поддерживаем необязательные фильтры: ?id_user=1&limit=10&offset=0
$idUser = null;
if (isset($_GET['id_user'])) {
    if (!ctype_digit((string)$_GET['id_user'])) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Параметр id_user должен быть целым положительным числом.',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $idUser = (int)$_GET['id_user'];
}

$limit = 100;
if (isset($_GET['limit'])) {
    if (!ctype_digit((string)$_GET['limit']) || (int)$_GET['limit'] < 1) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Параметр limit должен быть целым положительным числом.',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $limit = min((int)$_GET['limit'], 1000);
}

$offset = 0;
if (isset($_GET['offset'])) {
    if (!ctype_digit((string)$_GET['offset'])) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Параметр offset должен быть целым неотрицательным числом.',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $offset = (int)$_GET['offset'];
}

// ---------- 3. Подключение к БД ----------
try {
    $cfg = require __DIR__ . '/../config/db.php';
    $dsn = "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['db']};charset={$cfg['charset']}";
    $pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT            => 5,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Ошибка подключения к базе данных',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ---------- 4. Запрос ----------
try {
    $sql = "SELECT
                n.id,
                n.title,
                n.content,
                n.created_at,
                u.login AS user_login
            FROM notes n
            LEFT JOIN users u ON u.id = n.id_user";

    $params = [];
    if ($idUser !== null) {
        $sql .= " WHERE n.id_user = :id_user";
        $params[':id_user'] = $idUser;
    }

    $sql .= " ORDER BY n.created_at DESC LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v, PDO::PARAM_INT);
    }
    $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $rows = $stmt->fetchAll();

    // ---------- 5. Преобразование данных ----------
    $result = [];
    foreach ($rows as $row) {
        $login = $row['user_login'] ?? 'unknown';

        $result[] = [
            'id'             => (int)$row['id'],
            'title_user'     => $row['title'] . ' - ' . $login,
            'content'        => $row['content'],
            'formatted_date' => date('d.m.Y', strtotime($row['created_at'])),
        ];
    }

    // ---------- 6. Ответ ----------
    // Если заметок нет — возвращаем пустой массив (это НЕ ошибка)
    http_response_code(200);
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Внутренняя ошибка сервера',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}