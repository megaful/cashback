<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
header('Content-Type: text/plain; charset=utf-8');

echo "Session diagnostics\n";
echo "PHP: ".PHP_VERSION."\n";
echo "session.save_path (ini_get): ".ini_get('session.save_path')."\n";

require_once __DIR__.'/../includes/_session_force.php';
echo "After _session_force: status=".session_status()." (2=active)\n";
echo "session.save_path (effective): ".ini_get('session.save_path')."\n";

if (session_status() !== PHP_SESSION_ACTIVE) {
  echo "Trying session_start() explicitly...\n";
  session_start();
}
echo "Status now: ".session_status()." (2=active)\n";
$_SESSION['__test'] = time();
echo "session_id: ".session_id()."\n";
echo "wrote _SESSION['__test']=". $_SESSION['__test'] ."\n";
