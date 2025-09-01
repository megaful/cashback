<?php
require_once __DIR__.'/../includes/config.php';

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }
function col_exists(PDO $pdo, string $table, string $col): bool {
  $q=$pdo->prepare("SELECT COUNT(*) c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?");
  $q->execute([$table,$col]); return (int)($q->fetch()['c'] ?? 0) > 0;
}

$userId = (int)($_GET['id'] ?? 0);
if ($userId <= 0) { http_response_code(404); exit('Профиль не найден'); }

$u = $pdo->prepare("SELECT id, login, role, created_at FROM users WHERE id=? LIMIT 1");
$u->execute([$userId]); $profile = $u->fetch();
if (!$profile) { http_response_code(404); exit('Профиль не найден'); }

$me   = function_exists('current_user') ? current_user() : null;
$meId = (int)($me['id'] ?? 0);

/* Дата регистрации (если колонки нет/пустая) */
$registered = $profile['created_at'] ?? null;
if (!$registered) {
  // попытаемся найти первую активность через сделки
  $q = $pdo->prepare("SELECT MIN(created_at) FROM deals WHERE seller_id=? OR buyer_id=?");
  try { $q->execute([$userId,$userId]); $registered = $q->fetchColumn(); } catch(Throwable $e){ $registered = null; }
}

/* Кол-во успешных сделок: считаем участие в сделках со статусами SUCCESS */
$succStatuses = ['ACCEPTED','RESOLVED_ACCEPTED'];
$in = implode(',', array_fill(0,count($succStatuses),'?'));
$sqlSucc = "SELECT COUNT(*) c
            FROM deals
            WHERE status IN ($in) AND (seller_id=? OR buyer_id=?)";
$stSucc = $pdo->prepare($sqlSucc);
$stSucc->execute(array_merge($succStatuses, [$userId,$userId]));
$successDeals = (int)($stSucc->fetch()['c'] ?? 0);

/* Выигранные/проигранные арбитражи (если есть winner_user_id) */
$hasDisputes = true;
try { $hasDisputes = col_exists($pdo,'disputes','winner_user_id'); } catch(Throwable $e){ $hasDisputes=false; }
$won = $lost = 0;
if ($hasDisputes){
  $w = $pdo->prepare("SELECT COUNT(*) FROM disputes WHERE winner_user_id=?");
  $w->execute([$userId]); $won = (int)$w->fetchColumn();

  // считаем проигрыш, когда арбитраж по сделке закрыт и победитель — не этот пользователь
  $l = $pdo->prepare("SELECT COUNT(*)
                      FROM disputes d
                      JOIN deals dl ON dl.id=d.deal_id
                      WHERE (dl.seller_id=? OR dl.buyer_id=?) AND d.winner_user_id IS NOT NULL AND d.winner_user_id<>?");
  $l->execute([$userId,$userId,$userId]); $lost = (int)$l->fetchColumn();
}

/* Активные объявления на витрине */
$ads = [];
try {
  $adsSt = $pdo->prepare("SELECT id,title,category FROM listings WHERE seller_id=? AND status='ACTIVE' ORDER BY id DESC LIMIT 30");
  $adsSt->execute([$userId]); $ads = $adsSt->fetchAll();
} catch(Throwable $e){ $ads=[]; }

/* Публичные отзывы (PUBLISHED) */
$rev = $pdo->prepare("SELECT r.id, r.text, r.created_at, u.login AS author_login, u.id AS author_id
                      FROM user_reviews r
                      JOIN users u ON u.id=r.author_id
                      WHERE r.user_id=? AND r.status='PUBLISHED'
                      ORDER BY r.id DESC");
$rev->execute([$userId]); $reviews = $rev->fetchAll();

/* Флэшки (если есть flash()) */
function flash_safe($key){
  if (function_exists('flash')) return flash($key);
  return null;
}
?>
<!doctype html>
<html lang="ru"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Профиль — <?= h($profile['login']) ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
  :root{ --g1:#8A00FF; --g2:#005BFF; }
  body{
    background:linear-gradient(180deg,#f5ecff,#eef4ff 220px),
               linear-gradient(180deg,var(--g1),var(--g2)) fixed;
  }
  .card{border:1px solid #e6e8f0;border-radius:20px}
  .glass{background:rgba(255,255,255,.86);backdrop-filter:saturate(140%) blur(6px);}
  .btn-grad{background:linear-gradient(90deg,#8A00FF,#005BFF); color:#fff}
</style>
</head>
<body class="text-slate-900">
<?php @include __DIR__.'/../includes/topbar.php'; ?>

<main class="max-w-5xl mx-auto px-4 py-6 md:py-8">
  <a href="/dashboard/index.php" class="text-sm">← Назад</a>

  <section class="card glass p-5 md:p-6 mt-3">
    <div class="flex items-center justify-between gap-3">
      <h1 class="text-xl md:text-2xl font-bold">@<?= h($profile['login']); ?></h1>
      <span class="text-xs px-2 py-1 rounded-full border bg-white/70 text-slate-700">Роль: <?= h($profile['role'] ?? ''); ?></span>
    </div>
    <div class="mt-2 grid gap-3 sm:grid-cols-3">
      <div class="rounded-xl border bg-white/70 p-3">
        <div class="text-xs text-slate-500">Дата регистрации</div>
        <div class="font-medium"><?= h($registered ?: '—'); ?></div>
      </div>
      <div class="rounded-xl border bg-white/70 p-3">
        <div class="text-xs text-slate-500">Успешных сделок</div>
        <div class="font-medium"><?= (int)$successDeals; ?></div>
      </div>
      <div class="rounded-xl border bg-white/70 p-3">
        <div class="text-xs text-slate-500">Арбитраж (win / lose)</div>
        <div class="font-medium"><?= (int)$won; ?> / <?= (int)$lost; ?></div>
      </div>
    </div>
  </section>

  <section class="card glass p-5 md:p-6 mt-4">
    <h2 class="text-lg font-semibold">Активные объявления</h2>
    <?php if (!$ads): ?>
      <div class="mt-2 text-slate-600 text-sm">Нет активных объявлений.</div>
    <?php else: ?>
      <div class="mt-3 grid gap-3 md:grid-cols-2">
        <?php foreach($ads as $a): ?>
          <a href="/store/view.php?id=<?= (int)$a['id'] ?>" class="rounded-xl border bg-white/70 p-4 hover:bg-white">
            <div class="text-sm text-slate-600">#<?= (int)$a['id'] ?> · <?= h($a['category'] ?? 'Другое'); ?></div>
            <div class="font-medium mt-1 line-clamp-2"><?= h($a['title'] ?: 'Без названия'); ?></div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <section class="card glass p-5 md:p-6 mt-4">
    <div class="flex items-center justify-between">
      <h2 class="text-lg font-semibold">Отзывы</h2>
      <div class="text-xs text-slate-500">Публикуются после модерации</div>
    </div>

    <?php if ($m = flash_safe('ok')): ?>
      <div class="mt-3 rounded-xl border bg-green-50 text-green-800 p-3"><?= h($m) ?></div>
    <?php endif; ?>
    <?php if ($m = flash_safe('error')): ?>
      <div class="mt-3 rounded-xl border bg-red-50 text-red-800 p-3"><?= h($m) ?></div>
    <?php endif; ?>

    <?php if ($reviews): ?>
      <div class="mt-3 space-y-3">
        <?php foreach($reviews as $r): ?>
          <div class="rounded-xl border bg-white/70 p-3">
            <div class="text-sm text-slate-600">
              От: <a class="underline" href="/users/view.php?id=<?= (int)$r['author_id'] ?>">@<?= h($r['author_login']); ?></a>
              · <?= h($r['created_at']); ?>
            </div>
            <div class="mt-1 whitespace-pre-line"><?= nl2br(h($r['text'])); ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="mt-2 text-slate-600 text-sm">Пока нет отзывов.</div>
    <?php endif; ?>

    <?php if ($meId && $meId !== (int)$profile['id']): ?>
      <form class="mt-4 space-y-2" method="post" action="/reviews/submit.php">
        <?php if(function_exists('csrf_token')): ?><input type="hidden" name="csrf_token" value="<?= h(csrf_token()); ?>"><?php endif; ?>
        <input type="hidden" name="user_id" value="<?= (int)$profile['id'] ?>">
        <label class="block text-sm">Оставить отзыв (без оценки):</label>
        <textarea name="text" required rows="4" class="w-full rounded-xl border p-3" placeholder="Ваш опыт взаимодействия..."></textarea>
        <button class="px-4 py-2 rounded-full btn-grad">Отправить на модерацию</button>
      </form>
    <?php endif; ?>
  </section>
</main>
</body>
</html>
