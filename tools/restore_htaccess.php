<?php
header('Content-Type: text/plain; charset=utf-8');
$path = __DIR__.'/../.htaccess';
$minimal = <<<HT
# Minimal .htaccess
Options -Indexes
RewriteEngine On
# (оставьте ваши правила, если они были нужны)
HT;
if (file_put_contents($path, $minimal) !== false) {
  echo ".htaccess restored to minimal version.\n";
} else {
  echo "Failed to write .htaccess (check permissions).\n";
}
