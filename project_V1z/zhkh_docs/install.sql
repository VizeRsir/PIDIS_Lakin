<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/includes/config_path.php';

try {
    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT .
           ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        login           VARCHAR(50)  NOT NULL UNIQUE,
        password_hash   VARCHAR(255) NOT NULL,
        full_name       VARCHAR(150) NOT NULL,
        role            ENUM('admin','user') NOT NULL DEFAULT 'user',
        is_blocked      TINYINT(1)   NOT NULL DEFAULT 0,
        failed_attempts TINYINT      NOT NULL DEFAULT 0,
        created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    echo "<p style='color:green'>✔ Таблица users готова</p>";

    // admin / admin123
    $stmt = $pdo->prepare("SELECT id FROM users WHERE login = 'admin'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $ins = $pdo->prepare("INSERT INTO users (login, password_hash, full_name, role)
                              VALUES ('admin', ?, 'Системный администратор', 'admin')");
        $ins->execute([$hash]);
        echo "<p style='color:green'>✔ Создан admin / admin123</p>";
    } else {
        echo "<p>ℹ admin уже существует</p>";
    }

    // тестовый пользователь
    $stmt = $pdo->prepare("SELECT id FROM users WHERE login = 'user1'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $hash = password_hash('user123', PASSWORD_DEFAULT);
        $ins = $pdo->prepare("INSERT INTO users (login, password_hash, full_name, role)
                              VALUES ('user1', ?, 'Иванов Иван Иванович', 'user')");
        $ins->execute([$hash]);
        echo "<p style='color:green'>✔ Создан user1 / user123</p>";
    }

    echo "<p><a href='" . BASE_URL . "/login.php'>→ Перейти к входу</a></p>";

} catch (Throwable $e) {
    echo "<p style='color:#b00'>✖ " . htmlspecialchars($e->getMessage()) . "</p>";
}