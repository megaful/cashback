<?php
// ЧИСТЫЙ тест без вывода до старта сессии.
// 1) Сначала подключаем _session_force.php (он запускает сессию)
// 2) Потом уже печатаем и заголовки.

require_once __DIR__.'/../includes/_session_force.php';

// Теперь можно слать заголовки/вывод
header('Content-Type: text/plain; charset=utf-8');

echo "Session diagnostics #2\n";
echo "PHP: ".PHP_VERSION."\n";
echo "session.save_path (ini_get): ".ini_get('session.save_path')."\n";
echo "Status after _session_force: ".session_status()." (2=active)\n";

if (session_status() !== PHP_SESSION_ACTIVE) {
  echo "Trying session_start() explicitly...\n";
  @session_start();
}
echo "Status now: ".session_status()." (2=active)\n";
$_SESSION['__test2'] = time();
echo "session_id: ".session_id()."\n";
echo "wrote _SESSION['__test2']=" . $_SESSION['__test2'] . "\n";
