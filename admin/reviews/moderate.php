<?php
require_once __DIR__.'/../../includes/config.php';
if (!function_exists('is_admin') || !is_admin()) { http_response_code(403); exit('Доступ запрещён'); }

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }

if ($_SERVER['REQUEST_METHOD']==='POST'){
  try{
    check_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $act = $_POST['action'] ?? '';
    if ($id<=0) throw new Exception('Не указан id');

    if ($act==='publish'){
      $pdo->prepare("UPDATE user_reviews SET status='PUBLISHED' WHERE id=?")->execute([$id]);
    } elseif ($act==='delete'){
      $pdo->prepare("UPDATE user_reviews SET status='DELETED' WHERE id=?")->execute([$id]);
    }
  }catch(Throwable $e){}
  header('Location: /admin/reviews/moderate.php'); exit;
}

$rows = $pdo->query("SELECT r.*, u.login AS user_login, a.login AS author_login
                     FROM user_reviews r
                     JOIN users u ON u.id=r.user_id
                     JOIN users a ON a.id=r.author_id
                     WHERE r.status IN ('PENDING','PUBLISHED')
                     ORDER BY r.status DESC, r.id DESC")->fetchAll();
?>
<!doctype html><html lang="ru"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Модерация отзывов</title>
<script src="https://cdn.tailwindcss.com"></script>
</head><body class="bg-slate-50">
<main class="max-w-5xl mx-auto px-4 py-6">
  <h1 class="text-2xl font-semibold">Отзывы — модерация</h1>
  <div class="mt-4 space-y-3">
    <?php foreach($rows as $r): ?>
      <div class="rounded-xl border bg-white p-4">
        <div class="text-sm text-slate-600">
          Кому: <a class="underline" href="/users/view.php?id=<?= (int)$r['user_id'] ?>">@<?= h($r['user_login']); ?></a> ·
          От: <a class="underline" href="/users/view.php?id=<?= (int)$r['author_id'] ?>">@<?= h($r['author_login']); ?></a> ·
          <?= h($r['created_at']); ?> · Статус: <b><?= h($r['status']); ?></b>
        </div>
        <div class="mt-1 whitespace-pre-line"><?= nl2br(h($r['text'])); ?></div>
        <form method="post" class="mt-3 flex gap-2">
          <?php if(function_exists('csrf_token')): ?><input type="hidden" name="csrf_token" value="<?= h(csrf_token()); ?>"><?php endif; ?>
          <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
          <?php if ($r['status']==='PENDING'): ?>
            <button name="action" value="publish" class="px-3 py-1.5 rounded-lg bg-emerald-600 text-white">Опубликовать</button>
          <?php endif; ?>
          <button name="action" value="delete" class="px-3 py-1.5 rounded-lg bg-red-600 text-white">Удалить</button>
        </form>
      </div>
    <?php endforeach; if (!$rows): ?>
      <div class="text-slate-600">Записей нет.</div>
    <?php endif; ?>
  </div>
</main>
</body></html>
