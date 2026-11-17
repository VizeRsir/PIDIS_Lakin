#!/bin/bash
# Нагрузочное тестирование ЖКХ • Документооборот
# Требует: curl, ab (apache2-utils), PHP

set -e

PROJECT_DIR="/home/student/Документы/project_V1z/zhkh_docs"
REPORT="$PROJECT_DIR/load_test_report.txt"
PORT=8080
BASE="http://127.0.0.1:$PORT"

echo "=============================================="
echo " НАГРУЗОЧНОЕ ТЕСТИРОВАНИЕ ЖКХ • Документооборот"
echo "=============================================="
echo ""
echo "Отчёт будет сохранён в: $REPORT"
echo ""

# Проверка инструментов
for tool in curl ab php; do
    if ! command -v $tool &>/dev/null; then
        echo "ОШИБКА: $tool не найден в системе."
        exit 1
    fi
done

# Запуск встроенного PHP-сервера, если ещё не запущен
if ! curl -s "$BASE/login.php" &>/dev/null; then
    echo "Запуск PHP-сервера на порту $PORT..."
    cd "$PROJECT_DIR"
    php -S 127.0.0.1:$PORT > /tmp/php_server.log 2>&1 &
    PHP_PID=$!
    sleep 2
    # Проверка, что сервер поднялся
    if ! curl -s "$BASE/login.php" &>/dev/null; then
        echo "ОШИБКА: не удалось запустить PHP-сервер. См. /tmp/php_server.log"
        cat /tmp/php_server.log
        exit 1
    fi
    echo "PHP-сервер запущен (PID $PHP_PID)"
else
    echo "PHP-сервер уже доступен на $BASE"
fi

# Функция записи секции отчёта
write_section() {
    echo "" >> "$REPORT"
    echo "==============================================" >> "$REPORT"
    echo "$1" >> "$REPORT"
    echo "==============================================" >> "$REPORT"
}

# Очистка старого отчёта
> "$REPORT"

echo "ЖКХ • Документооборот — Отчёт о нагрузочном тестировании" > "$REPORT"
echo "Дата: $(date '+%Y-%m-%d %H:%M:%S')" >> "$REPORT"
echo "URL: $BASE" >> "$REPORT"

echo ""
echo "--- Тест 1: GET /login.php (страница входа) ---"
ab -n 100 -c 10 "$BASE/login.php" 2>&1 | tee -a "$REPORT"

echo ""
echo "--- Тест 2: GET /captcha/get_puzzle.php (генерация капчи) ---"
ab -n 100 -c 10 "$BASE/captcha/get_puzzle.php" 2>&1 | tee -a "$REPORT"

echo ""
echo "--- Тест 3: GET /captcha/check_puzzle.php без POST (должен быть 405) ---"
ab -n 50 -c 5 "$BASE/captcha/check_puzzle.php" 2>&1 | tee -a "$REPORT"

echo ""
echo "--- Тест 4: POST /captcha/check_puzzle.php (проверка капчи) ---"
# Подготовка валидного POST-запроса для проверки капчи
# Сначала получаем токен
TOKEN_RESPONSE=$(curl -s "$BASE/captcha/get_puzzle.php")
TOKEN=$(echo "$TOKEN_RESPONSE" | grep -o '"token":"[^"]*"' | head -1 | cut -d'"' -f4)
CSRF=$(echo "$TOKEN_RESPONSE" | grep -o '"csrf":"[^"]*"' | head -1 | cut -d'"' -f4)

if [ -z "$TOKEN" ]; then
    echo "Не удалось получить токен капчи, пропускаем тест 4"
else
    # Создаём временный файл с POST данными
    TMPPOST=$(mktemp)
    echo "token=$TOKEN&order=1,2,3,4&csrf=$CSRF" > "$TMPPOST"
    ab -n 100 -c 10 -p "$TMPPOST" -T "application/x-www-form-urlencoded" "$BASE/captcha/check_puzzle.php" 2>&1 | tee -a "$REPORT"
    rm -f "$TMPPOST"
fi

echo ""
echo "--- Тест 5: POST /login.php (авторизация admin/admin123) ---"
TMPLOGIN=$(mktemp)
echo "login=admin&password=admin123&captcha_ok=1" > "$TMPLOGIN"
ab -n 50 -c 5 -p "$TMPLOGIN" -T "application/x-www-form-urlencoded" "$BASE/login.php" 2>&1 | tee -a "$REPORT"
rm -f "$TMPLOGIN"

echo ""
echo "--- Тест 6: Повышенная нагрузка на страницу входа (1000 запросов, 50 параллельных) ---"
ab -n 1000 -c 50 "$BASE/login.php" 2>&1 | tee -a "$REPORT"

echo ""
echo "--- Тест 7: Повышенная нагрузка на генерацию капчи (500 запросов, 30 параллельных) ---"
ab -n 500 -c 30 "$BASE/captcha/get_puzzle.php" 2>&1 | tee -a "$REPORT"

# Остановка PHP-сервера, если мы его запустили
if [ -n "$PHP_PID" ]; then
    kill $PHP_PID 2>/dev/null || true
    echo "PHP-сервер остановлен."
fi

echo ""
echo "=============================================="
echo " ВСЕ ТЕСТЫ ЗАВЕРШЕНЫ"
echo "Отчёт: $REPORT"
echo "=============================================="
