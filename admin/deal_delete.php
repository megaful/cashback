<?php
require_once __DIR__.'/../includes/config.php';
require_login(); require_admin();

if ($_SERVER['REQUEST_METHOD']!=='POST') { redirect('/admin/index.php'); }
check_csrf();

$id = (int)($_POST['id'] ?? 0);
$tab = preg_replace('~[^a-z]~','', $_POST['tab'] ?? 'active');

if ($id) {
  try {
    $pdo->prepare('DELETE FROM deals WHERE id=?')->execute([$id]);
    flash('ok', 'Сделка удалена');
  } catch (Throwable $e) {
    flash('error', 'Не удалось удалить сделку: ' . $e->getMessage());
  }
}
redirect('/admin/index.php?tab='.$tab);
