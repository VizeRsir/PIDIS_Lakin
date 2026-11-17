<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>Проверка подключения</h2>";
echo "PHP: " . PHP_VERSION . "<br>";
echo "PDO драйверы: " . implode(', ', PDO::getAvailableDrivers()) . "<br><br>";

$hosts = ['134.90.167.42', '127.0.0.1', 'localhost'];
$port  = 10306;
$db    = 'project_Lakin';
$user  = 'Lakin';
$pass  = 'rXTXi5Yn(wHmX92g';

foreach ($hosts as $h) {
    try {
        $dsn = "mysql:host=$h;port=$port;dbname=$db;charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5,
        ]);
        echo "<p style='color:green'>✔ OK: host=$h, port=$port</p>";

        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        echo "Таблицы: " . ($tables ? implode(', ', $tables) : '(пусто)') . "<br>";

        if (!in_array('users', $tables)) {
            echo "<b style='color:#b00'>✖ Таблицы users нет — нужно запустить install.php</b><br>";
        } else {
            echo "<b style='color:green'>✔ Таблица users найдена</b><br>";
            $cnt = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
            echo "Пользователей в базе: $cnt<br>";
        }
        echo "<p><b>Используй host = '$h' в config_path.php</b></p>";
        break;

    } catch (Throwable $e) {
        echo "<p style='color:#b00'>✖ host=$h → " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}