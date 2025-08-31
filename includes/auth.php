<?php
function current_user() { return $_SESSION['user'] ?? null; }
function require_login() { if (!current_user()) redirect('/auth/login.php'); }
function is_admin() { return current_user() && current_user()['role'] === 'ADMIN'; }
function require_admin() { if (!is_admin()) die('Доступ запрещён'); }
