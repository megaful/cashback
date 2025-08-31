<?php
header('Content-Type: text/plain; charset=utf-8');
$cfg = __DIR__.'/../includes/config.php';
if (!file_exists($cfg)) { http_response_code(404); echo "config.php not found\n"; exit; }
$src = file_get_contents($cfg);

// Remove BOM + leading whitespace
$src2 = preg_replace('/^\xEF\xBB\xBF/u', '', $src);
$src2 = ltrim($src2);

// Comment out direct session_start/ob_start calls in the first ~50 lines to avoid duplicate warnings
$lines = preg_split('/\R/', $src2);
for ($i=0; $i < min(count($lines), 50); $i++) {
  if (preg_match('/\b(session_start|ob_start)\s*\(/', $lines[$i])) {
    $lines[$i] = '// patched by strip_session_from_config: '.$lines[$i];
  }
}
$src2 = implode(PHP_EOL, $lines);

// Ensure opening tag present
if (stripos($src2, '<?php') !== 0) $src2 = "<?php\n".$src2;

if ($src2 !== $src) {
  if (!is_writable($cfg)) { echo "config.php not writable\n"; exit; }
  file_put_contents($cfg, $src2);
  echo "Patched config.php: commented direct session_start/ob_start and normalized header.\n";
} else {
  echo "No changes were necessary.\n";
}
