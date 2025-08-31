<?php
header('Content-Type: text/plain; charset=utf-8');
$cfg = __DIR__.'/../includes/config.php';
$inject = "require_once __DIR__.'/_session_force.php';";

if (!file_exists($cfg)) { http_response_code(404); echo "config.php not found\n"; exit; }
$src = file_get_contents($cfg);

if (strpos($src, "_session_force.php") !== false) {
  echo "_session_force.php already included.\n";
} else {
  // гарантия: открывающий тег есть
  if (stripos($src, '<?php') === false) { $src = "<?php\n".$src; }
  // найдём позицию после первого тега
  if (preg_match('/<\?php\s*/i', $src, $m, PREG_OFFSET_CAPTURE)) {
    $pos = $m[0][1] + strlen($m[0][0]);
    $src = substr($src, 0, $pos) . "\n".$inject."\n" . substr($src, $pos);
    if (!is_writable($cfg)) { echo "config.php is not writable\n"; exit; }
    file_put_contents($cfg, $src);
    echo "Patched: added include of _session_force.php right after <?php\n";
  } else {
    echo "Could not find opening tag to inject.\n";
  }
}

// self-test include
require_once __DIR__.'/../includes/_session_force.php';
require $cfg;
echo "Session status now: ".session_status()." (2 means active).\n";
