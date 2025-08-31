<?php

// Переименуйте в config.php и заполните доступы
session_start();

define('APP_NAME', 'Escrow Cashback PHP');
define('APP_URL', ''); // например, https://example.com (не обязательно)

// База данных
define('DB_HOST', 'localhost');
define('DB_NAME', 'cv96936_crowd');
define('DB_USER', 'cv96936_crowd');
define('DB_PASS', 'F93a1c5d!!!');
define('DB_CHARSET', 'utf8mb4');

// Комиссия сервиса
define('SERVICE_COMMISSION_RUB', 100);

// Ограничения загрузки
define('MAX_UPLOAD_BYTES', 10 * 1024 * 1024); // 10 MB
$ALLOWED_MIME = ['image/png','image/jpeg'];

// Подключение БД
try {
  $dsn = 'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset='.DB_CHARSET;
  $pdo = new PDO($dsn, DB_USER, DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
} catch (PDOException $e) {
  die('DB connection failed: ' . htmlspecialchars($e->getMessage()));
}

// CSRF
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
function csrf_token() { return $_SESSION['csrf_token'] ?? ''; }
function check_csrf() {
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $t = $_POST['csrf_token'] ?? '';
    if (!$t || !hash_equals($_SESSION['csrf_token'], $t)) {
      die('CSRF validation failed');
    }
  }
}

// Утилиты
require_once __DIR__.'/utils.php';
require_once __DIR__.'/flash.php';
require_once __DIR__.'/auth.php';
