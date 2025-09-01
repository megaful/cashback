<?php
require_once __DIR__.'/../includes/config.php';
require_login();
$user = current_user();

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }
function val($row, $keys, $default = null){ foreach((array)$keys as $k){ if(isset($row[$k]) && $row[$k]!=='' && $row[$k]!==null) return $row[$k]; } return $default; }

$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare("SELECT l.*, u.login AS seller_login FROM listings l JOIN users u ON u.id=l.seller_id WHERE l.id=?");
$st->execute([$id]); $ad = $st->fetch();
if (!$ad) { http_response_code(404); exit('Объявление не найдено'); }

$isSellerAccount = ((int)$ad['seller_id'] === (int)$user['id']);
$isActive = ($ad['status'] === 'ACTIVE');
$isBuyer  = (strtoupper($user['role']??'')==='BUYER');

$title = val($ad,'title','Без названия');
$url   = val($ad,['url','product_url']);
$cash  = (int)preg_replace('~[^\d]~u','',(string)val($ad,['cashback','reward','amount'],0));
$slots = (int)val($ad,['slots','remaining','max_deals'],0);
$desc  = val($ad,['description','terms','conditions'],'');
$cat   = val($ad,'category','Другое');

/* фото */
$photos = $pdo->prepare("SELECT file_name FROM listing_photos WHERE listing_id=? ORDER BY id ASC");
$photos->execute([$ad['id']]);
$images = $photos->fetchAll(PDO::FETCH_COLUMN);
?>
<!doctype html>
<html lang="ru"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($title) ?> — Витрина</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
  :root{ --g1:#8A00FF; --g2:#005BFF; }
  body{background:linear-gradient(180deg,#f5ecff,#eef4ff 220px),linear-gradient(180deg,var(--g1),var(--g2)) fixed;}
  .card{border:1px solid #e6e8f0;border-radius:20px}
  .btn-grad{background:linear-gradient(90deg,#8A00FF,#005BFF);color:#fff}
  .btn-grad:hover{filter:brightness(.95)}
</style>
</head>
<body class="text-slate-900">
<?php @include __DIR__.'/../includes/topbar.php'; ?>

<main class="max-w-3xl mx-auto px-4 py-5 md:py-8">
  <a href="/store/index.php" class="text-sm">← Витрина</a>

  <div class="mt-3 card bg-white p-5">
    <div class="text-sm text-slate-500">Продавец: <?= h($ad['seller_login']) ?></div>
    <h1 class="text-2xl md:text-3xl font-bold mt-1"><?= h($title) ?></h1>

    <div class="mt-2 text-xs inline-flex items-center gap-2 rounded-full border px-2 py-1 bg-slate-50">
      Категория: <b class="text-slate-800"><?= h($cat ?: 'Другое') ?></b>
    </div>

    <?php if ($images): ?>
      <div class="mt-4">
        <div id="slider" class="relative overflow-hidden rounded-2xl border bg-black/5">
          <div id="track" class="flex transition-transform duration-300 ease-out">
            <?php foreach ($images as $u): ?>
              <div class="min-w-full aspect-[3/4] bg-white">
                <img src="/uploads/listings/<?= h($u) ?>" alt="" class="w-full h-full object-contain">
              </div>
            <?php endforeach; ?>
          </div>
          <button id="prev" type="button" class="absolute left-2 top-1/2 -translate-y-1/2 px-3 py-2 rounded-xl bg-white/95 border shadow">‹</button>
          <button id="next" type="button" class="absolute right-2 top-1/2 -translate-y-1/2 px-3 py-2 rounded-xl bg-white/95 border shadow">›</button>
          <div id="dots" class="absolute bottom-2 left-0 right-0 flex justify-center gap-2">
            <?php foreach ($images as $i=>$u): ?>
              <button class="w-2.5 h-2.5 rounded-full border bg-white/80" data-i="<?= $i ?>"></button>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <script>
        (function(){
          const track=document.getElementById('track');
          const prev=document.getElementById('prev');
          const next=document.getElementById('next');
          const dots=[...document.querySelectorAll('#dots button')];
          const total=<?= count($images) ?>;
          if(!track||total<1) return; let i=0;
          function update(){ track.style.transform='translateX('+(-i*100)+'%)'; dots.forEach((d,k)=>d.style.opacity=k===i?'1':'.45'); }
          prev?.addEventListener('click',()=>{ i=(i-1+total)%total; update(); });
          next?.addEventListener('click',()=>{ i=(i+1)%total; update(); });
          dots.forEach(d=> d.addEventListener('click',()=>{ i=parseInt(d.dataset.i||'0',10); update(); }));
          update();
        })();
      </script>
    <?php endif; ?>

    <?php if($url): ?>
      <div class="text-sm break-all mt-3">Товар: <a class="underline" target="_blank" href="<?= h($url) ?>"><?= h($url) ?></a></div>
    <?php endif; ?>

    <div class="text-sm mt-1">Кэшбэк: <b>₽ <?= $cash ?></b></div>
    <?php if($slots>0): ?><div class="text-xs text-slate-600 mt-1">Доступно слотов: <?= $slots ?></div><?php endif; ?>

    <?php if($desc): ?>
      <div class="mt-3 text-slate-800 whitespace-pre-line">
        <b>Условия выкупа</b>
        <div class="mt-1"><?= h($desc) ?></div>
      </div>
    <?php endif; ?>

    <div class="mt-4 flex flex-wrap gap-2">
      <?php if (!$isActive): ?>
        <div class="rounded-xl border bg-amber-50 text-amber-900 p-3">Объявление недоступно.</div>
      <?php elseif ($isSellerAccount): ?>
        <form method="post" action="/seller/listings/archive.php" onsubmit="return confirm('Деактивировать объявление?');">
          <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
          <input type="hidden" name="id" value="<?= (int)$ad['id'] ?>">
          <button class="px-4 py-2 rounded-full bg-amber-600 text-white">Деактивировать</button>
        </form>
      <?php elseif (!$isBuyer): ?>
        <div class="rounded-xl border bg-slate-50 text-slate-700 p-3">Отклик доступен только для роли «Покупатель».</div>
      <?php else: ?>
        <form class="flex gap-2" method="post" action="/store/respond.php">
          <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
          <input type="hidden" name="id" value="<?= (int)$ad['id'] ?>">
          <button class="px-4 py-2 rounded-full btn-grad">Откликнуться и создать заявку</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</main>
</body></html>
