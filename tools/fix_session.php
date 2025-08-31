<?php
header('Content-Type: text/plain; charset=utf-8');
$path = __DIR__.'/../includes/config.php';
if (!file_exists($path)) { http_response_code(404); echo "Not found: $path\n"; exit; }
$src = file_get_contents($path);

// Remove UTF-8 BOM and any whitespace before <?php
$src2 = preg_replace('/^\xEF\xBB\xBF/u', '', $src); // remove BOM
$src2 = ltrim($src2); // remove leading whitespace/newlines

// If config begins with <?php already — keep it. Else, prepend.
if (strpos($src2, '<?php') !== 0) {
  $src2 = "<?php\n".$src2;
}

// Ensure early session_start guard (if not already there)
if (strpos($src2, 'session_start()') === false) {
  $sessionGuard = "if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }\n";
  // place right after opening tag
  $src2 = preg_replace('/^<\?php\s*/', "<?php\n".$sessionGuard, $src2, 1);
}

// Also start output buffering to avoid 'headers already sent'
if (strpos($src2, 'ob_start(') === false) {
  $src2 = preg_replace('/^<\?php\n/', "<?php\nob_start();\n", $src2, 1);
}

if ($src2 !== $src) {
  if (!is_writable($path)) { echo "config.php is not writable\n"; exit; }
  file_put_contents($path, $src2);
  echo "config.php sanitized and session guard added.\n";
} else {
  echo "config.php already looks fine (no changes).\n";
}

// Quick check: include and try session
try {
  require $path;
  echo "Included config. Session status = ".session_status()." (2 is active).\n";
} catch (Throwable $e) {
  echo "Include error: ".$e->getMessage()."\n";
}
