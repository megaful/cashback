<?php
require_once __DIR__.'/../includes/config.php';
require_login();
$user = current_user();

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }
function val($row, $keys, $default = null) {
  foreach ((array)$keys as $k) if (isset($row[$k]) && $row[$k] !== '' && $row[$k] !== null) return $row[$k];
  return $default;
}

$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare("SELECT l.*, u.login AS seller_login FROM listings l JOIN users u ON u.id=l.seller_id WHERE l.id=?");
$st->execute([$id]); $ad = $st->fetch();
if (!$ad) { http_response_code(404); exit('Объявление не найдено'); }

$isSellerAccount = ((int)$ad['seller_id'] === (int)$user['id']);
$isActive = ($ad['status'] === 'ACTIVE');
$isBuyer  = (strtoupper($user['role']??'')==='BUYER');

$title = val($ad,'title','Без названия');
$url   = val($ad,['url','product_url']);
$cash  = val($ad,['cashback','reward','amount'], 0);
$cash  = (int)preg_replace('~[^\d]~u','',(string)$cash);
$slots = (int)val($ad,['slots','remaining','max_deals'], 0);
$desc  = val($ad,['description','terms','conditions'],'');

// Загружаем фото из БД
$photos = $pdo->prepare("SELECT file_name FROM listing_photos WHERE listing_id=? ORDER BY id ASC");
$photos->execute([$ad['id']]);
$images = $photos->fetchAll(PDO::FETCH_COLUMN);
?>
<!doctype html>
<html lang="ru"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo h($title); ?></title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50">
<?php @include __DIR__.'/../includes/topbar.php'; ?>
<main class="max-w-3xl mx-auto px-4 py-6">
  <a href="/store/index.php" class="text-sm">&larr; Витрина</a>
  <div class="mt-3 rounded-2xl border bg-white p-5">
    <div class="text-sm text-slate-500">Продавец: <?php echo h($ad['seller_login']); ?></div>
    <h1 class="text-2xl font-semibold mt-1"><?php echo h($title); ?></h1>

    <?php if ($images): ?>
      <div class="mt-4">
        <div id="slider" class="relative overflow-hidden rounded-2xl border bg-black/5">
          <div id="track" class="flex transition-transform duration-300 ease-out">
            <?php foreach ($images as $u): ?>
              <div class="min-w-full aspect-[4/3] bg-slate-100">
                <img src="/uploads/listings/<?php echo h($u); ?>" alt="" class="w-full h-full object-contain bg-white">
              </div>
            <?php endforeach; ?>
          </div>
          <button id="prev" type="button" class="absolute left-2 top-1/2 -translate-y-1/2 px-3 py-2 rounded-xl bg-white/90 border">‹</button>
          <button id="next" type="button" class="absolute right-2 top-1/2 -translate-y-1/2 px-3 py-2 rounded-xl bg-white/90 border">›</button>
          <div id="dots" class="absolute bottom-2 left-0 right-0 flex justify-center gap-2">
            <?php foreach ($images as $i=>$u): ?>
              <button class="w-2.5 h-2.5 rounded-full border bg-white/70" data-i="<?php echo $i; ?>"></button>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <script>
        (function(){
          const track = document.getElementById('track');
          const prev  = document.getElementById('prev');
          const next  = document.getElementById('next');
          const dots  = Array.from(document.querySelectorAll('#dots button'));
          const total = <?php echo count($images); ?>;
          if (!track || total < 1) return;
          let i = 0;
          function update(){
            track.style.transform = 'translateX(' + (-i*100) + '%)';
            dots.forEach((d,idx)=>{ d.style.opacity = idx===i ? '1' : '.45'; });
          }
          if (prev) prev.addEventListener('click', ()=>{ i = (i-1+total)%total; update(); });
          if (next) next.addEventListener('click', ()=>{ i = (i+1)%total; update(); });
          dots.forEach(d=> d.addEventListener('click', ()=>{ i = parseInt(d.dataset.i,10)||0; update(); }));
          update();
        })();
      </script>
    <?php endif; ?>

    <?php if($url): ?>
      <div class="text-sm break-all mt-3">Товар: <a class="underline" target="_blank" href="<?php echo h($url); ?>"><?php echo h($url); ?></a></div>
    <?php endif; ?>

    <div class="text-sm mt-1">Кэшбэк: <b>₽ <?php echo $cash; ?></b></div>
    <?php if($slots>0): ?>
      <div class="text-xs text-slate-600 mt-1">Доступно слотов: <?php echo $slots; ?></div>
    <?php endif; ?>

    <?php if($desc): ?>
      <div class="mt-3 text-slate-800 whitespace-pre-line"><?php echo h($desc); ?></div>
    <?php endif; ?>

    <div class="mt-4 flex flex-wrap gap-2">
      <?php if (!$isActive): ?>
        <div class="rounded-xl border bg-amber-50 text-amber-900 p-3">Объявление недоступно.</div>
      <?php elseif ($isSellerAccount): ?>
        <form method="post" action="/seller/listings/archive.php"
              onsubmit="return confirm('Деактивировать объявление?');">
          <input type="hidden" name="csrf_token" value="<?php echo h(csrf_token()); ?>">
          <input type="hidden" name="id" value="<?php echo (int)$ad['id']; ?>">
          <button class="px-4 py-2 rounded-xl bg-amber-600 text-white">Деактивировать</button>
        </form>
      <?php elseif (!$isBuyer): ?>
        <div class="rounded-xl border bg-slate-50 text-slate-700 p-3">Отклик доступен только для роли «Покупатель».</div>
      <?php else: ?>
        <form class="flex gap-2" method="post" action="/store/respond.php">
          <input type="hidden" name="csrf_token" value="<?php echo h(csrf_token()); ?>">
          <input type="hidden" name="id" value="<?php echo (int)$ad['id']; ?>">
          <button class="px-4 py-2 rounded-xl bg-black text-white">Откликнуться и создать заявку</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</main>
</body>
</html>
