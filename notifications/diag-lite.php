<?php
// Мини-диагностика без зависимостей шаблонов.
// Показывает любую PHP-ошибку прямо на странице.
error_reporting(E_ALL);
ini_set('display_errors', '1');
header('Content-Type: text/plain; charset=utf-8');

echo "diag-lite running\n";

try {
  require_once __DIR__.'/../includes/config.php';
  echo "config included\n";
  if (!function_exists('current_user')) {
    echo "current_user() not found\n";
  } else {
    if (session_status() !== PHP_SESSION_ACTIVE) @session_start();
    $u = current_user();
    echo "current_user: ".json_encode($u, JSON_UNESCAPED_UNICODE)."\n";
  }

  // Проверяем доступность БД и таблицы notifications
  if (isset($pdo)) {
    echo "pdo alive\n";
    $st = $pdo->query('SELECT 1');
    echo "sql SELECT 1 ok\n";
    $st2 = $pdo->query('SHOW TABLES');
    $tables = $st2->fetchAll(PDO::FETCH_COLUMN);
    echo "tables: ".implode(',', $tables)."\n";
    // попробуем узнать структуру notifications
    try {
      $st3 = $pdo->query('DESCRIBE notifications');
      $cols = $st3->fetchAll(PDO::FETCH_ASSOC);
      echo "notifications columns:\n";
      foreach ($cols as $c) {
        echo " - {$c['Field']} ({$c['Type']})\n";
      }
    } catch (Throwable $e) {
      echo "DESCRIBE notifications failed: ".$e->getMessage()."\n";
    }
  } else {
    echo "$pdo not defined\n";
  }

  echo "done\n";
} catch (Throwable $e) {
  echo "FATAL: ".$e->getMessage()."\n".$e->getFile().":".$e->getLine()."\n";
  if (function_exists('xdebug_get_function_stack')) {
    var_export(xdebug_get_function_stack());
  }
}
