<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: text/plain; charset=utf-8');

$cfg = require __DIR__ . '/config/db.php';

try {
    $dsn = "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['db']};charset={$cfg['charset']}";
    $pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    echo "✔ Подключение к БД OK\n\n";

    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Таблицы в базе: " . implode(', ', $tables) . "\n\n";

    if (!in_array('notes', $tables)) {
        echo "✖ Таблицы 'notes' НЕТ. Нужно выполнить install.sql\n";
    } else {
        $cnt = $pdo->query("SELECT COUNT(*) FROM notes")->fetchColumn();
        echo "✔ Таблица notes есть, записей: $cnt\n";
    }

    if (!in_array('users', $tables)) {
        echo "✖ Таблицы 'users' НЕТ\n";
    } else {
        $cnt = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        echo "✔ Таблица users есть, записей: $cnt\n";
        $cols = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
        echo "  Колонки users: " . implode(', ', $cols) . "\n";
    }

    if (in_array('notes', $tables) && in_array('users', $tables)) {
        echo "\n--- Пробный JOIN ---\n";
        $rows = $pdo->query(
            "SELECT n.id, n.title, n.content, n.created_at, u.login
             FROM notes n LEFT JOIN users u ON u.id = n.id_user
             ORDER BY n.created_at DESC"
        )->fetchAll(PDO::FETCH_ASSOC);
        echo "Получено строк: " . count($rows) . "\n";
        print_r($rows);
    }

} catch (Throwable $e) {
    echo "✖ ОШИБКА: " . $e->getMessage() . "\n";
}