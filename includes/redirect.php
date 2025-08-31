<?php
// Минимальный безопасный редирект без сторонних зависимостей.
// Используйте safe_redirect('/path'); после успешного действия.
if (!function_exists('safe_redirect')) {
  function safe_redirect(string $url) : void {
    if (!headers_sent()) {
      header('Location: ' . $url, true, 302);
    } else {
      echo '<script>location.href=' . json_encode($url) . ';</script>';
    }
    exit;
  }
}
