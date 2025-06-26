<?php
// config.php - минимальная рабочая версия

// ======================
// БАЗОВАЯ КОНФИГУРАЦИЯ
// ======================
define('APP_NAME', 'Gloss Clinic');
define('APP_VERSION', '2.0.0');
define('DEBUG_MODE', true);

// ======================
// ПОДКЛЮЧЕНИЕ К БАЗЕ ДАННЫХ
// ======================
$db_config = [
    'host' => 'localhost',
    'dbname' => 'gloss_clinic',
    'username' => 'root',
    'password' => 'root',
    'charset' => 'utf8mb4'
];

try {
    $pdo = new PDO(
        "mysql:host={$db_config['host']};dbname={$db_config['dbname']};charset={$db_config['charset']}",
        $db_config['username'],
        $db_config['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    
    // Установка параметров соединения
    $pdo->exec("SET NAMES '{$db_config['charset']}'");
    $pdo->exec('SET time_zone = "+00:00"');
    
} catch (PDOException $e) {
    // Простое логирование ошибок
    error_log('['.date('Y-m-d H:i:s').'] DB Error: '.$e->getMessage());
    die("Database connection error. Please try again later.");
}

// ======================
// БЕЗОПАСНАЯ СЕССИЯ (минимальный вариант)
// ======================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ======================
// ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ
// ======================
function safe_output($data) {
    return htmlspecialchars($data ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect($url, $status = 302) {
    header("Location: $url", true, $status);
    exit;
}

// Автозагрузка классов (если нужно)
spl_autoload_register(function($class) {
    $file = __DIR__.'/classes/'.str_replace('\\', '/', $class).'.php';
    if (file_exists($file)) {
        require $file;
    }
});