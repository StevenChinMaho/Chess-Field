<?php 
// 資料庫基本設定
define('DB_HOST', 'localhost');
define('DB_USER', 'user');
define('DB_PASS', 'password');
define('DB_NAME', 'chess_db');

// 其他常數與設定值
define('EXPIRATION_TIME_SECONDS', 180);
date_default_timezone_set('Asia/Taipei');

// 建立PDO物件連線資料庫
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . "; dbname=" . DB_NAME . ";charset=utf8mb4", 
        DB_USER, 
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]
    );
} catch ( PDOException $e ) {
    error_log("西洋棋資料庫連線失敗: " . $e->getMessage());
    http_response_code(503);
    die("503");
}
?>