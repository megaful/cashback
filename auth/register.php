<?php
require_once __DIR__.'/../includes/config.php';
check_csrf();

$login = trim($_POST['login'] ?? '');
$password = $_POST['password'] ?? '';
$email = trim($_POST['email'] ?? '');
$role = $_POST['role'] ?? '';

if (!$login || !$password || !$email || !in_array($role, ['SELLER','BUYER'])) {
  flash('error', 'Заполните все поля');
  redirect('/');
}

$stmt = $pdo->prepare('SELECT id FROM users WHERE login = :login OR email = :email');
$stmt->execute([':login'=>$login, ':email'=>$email]);
if ($stmt->fetch()) {
  flash('error', 'Логин или email уже заняты');
  redirect('/');
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$pdo->prepare('INSERT INTO users (login,email,pass_hash,role) VALUES (?,?,?,?)')
    ->execute([$login,$email,$hash,$role]);
$userId = (int)$pdo->lastInsertId();
$pdo->prepare('INSERT INTO profiles (user_id) VALUES (?)')->execute([$userId]);
$pdo->prepare('INSERT INTO balances (user_id,balance) VALUES (?,0)')->execute([$userId]);

$_SESSION['user'] = ['id'=>$userId,'login'=>$login,'email'=>$email,'role'=>$role];
redirect('/dashboard/index.php');
