<?php
require_once __DIR__.'/../includes/config.php';
check_csrf();

$login = trim($_POST['login'] ?? '');
$password = $_POST['password'] ?? '';

$stmt = $pdo->prepare('SELECT * FROM users WHERE login = ? LIMIT 1');
$stmt->execute([$login]);
$user = $stmt->fetch();
if (!$user || !password_verify($password, $user['pass_hash'])) {
  flash('error','Неверные логин или пароль');
  redirect('/');
}
$pdo->prepare('UPDATE users SET last_login = NOW() WHERE id = ?')->execute([$user['id']]);

$_SESSION['user'] = ['id'=>$user['id'],'login'=>$user['login'],'email'=>$user['email'],'role'=>$user['role']];
redirect('/dashboard/index.php');
