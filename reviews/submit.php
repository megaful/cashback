<?php
require_once __DIR__.'/../includes/config.php';
require_login();
$me = current_user();

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }

try{
  check_csrf();
  $user_id = (int)($_POST['user_id'] ?? 0);
  $text    = trim($_POST['text'] ?? '');

  if ($user_id <= 0 || $text==='') throw new Exception('Заполните отзыв.');
  if ($user_id === (int)$me['id'])    throw new Exception('Нельзя оставлять отзыв самому себе.');

  // Ограничим флуд: не чаще одного PENDING на одного адресата от автора
  $dup = $pdo->prepare("SELECT COUNT(*) FROM user_reviews WHERE user_id=? AND author_id=? AND status='PENDING'");
  $dup->execute([$user_id, (int)$me['id']]);
  if ((int)$dup->fetchColumn() > 0) throw new Exception('У вас уже есть отзыв на модерации для этого пользователя.');

  $st = $pdo->prepare("INSERT INTO user_reviews (user_id, author_id, text, status) VALUES (?,?,?,'PENDING')");
  $st->execute([$user_id, (int)$me['id'], $text]);

  if (function_exists('notify_admin')) {
    @notify_admin($pdo, 'Новый отзыв на модерацию', '/admin/reviews/moderate.php');
  }

  if (function_exists('flash')) flash('ok','Отзыв отправлен на модерацию.');
  header('Location: /users/view.php?id='.$user_id);
  exit;

} catch(Throwable $e){
  if (function_exists('flash')) flash('error', $e->getMessage());
  $back = '/users/view.php?id='.(int)($_POST['user_id'] ?? 0);
  header('Location: '.$back);
  exit;
}
