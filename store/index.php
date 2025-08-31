<?php
require_once __DIR__.'/../includes/config.php';

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }
function val($row, $keys, $default = null) {
  foreach ((array)$keys as $k) {
    if (isset($row[$k]) && $row[$k] !== '' && $row[$k] !== null) return $row[$k];
  }
  return $default;
}

$rows=[]; $err='';
try {
  $sql="SELECT l.*, u.login AS seller_login
        FROM listings l
        JOIN users u ON u.id=l.seller_id
        WHERE l.status='ACTIVE'
        ORDER BY l.id DESC";
  $st=$pdo->query($sql); 
  $rows=$st->fetchAll();
} catch(Throwable $e){ 
  $err=$e->getMessage(); 
}
?>
<!doctype html>
<html lang="ru"><head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Витрина</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50">
<?php @include __DIR__.'/../includes/topbar.php'; ?>
<main class="max-w-6xl mx-auto px-4 py-6">
  <h1 class="text-2xl font-semibold mb-4">Витрина</h1>

  <?php if ($err): ?>
    <div class="rounded-xl border bg-red-50 text-red-800 p-4 mb-4">
      Ошибка: <?php echo h($err); ?>
    </div>
  <?php endif; ?>

  <?php if (!$err && !$rows): ?>
    <div class="rounded-xl border bg-white p-6 text-slate-600">
      Нет активных предложений.
    </div>
  <?php elseif(!$err): ?>
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-3">
      <?php foreach ($rows as $r):
        $title = val($r,'title','Без названия');
        $url   = val($r,['url','product_url']);
        $cash  = val($r,['cashback','reward','amount'], 0);
        $cash  = (int)preg_replace('~[^\d]~u','',(string)$cash);
        $slots = (int)val($r,['slots','remaining','max_deals'], 0);
        $desc  = val($r,['description','terms','conditions'],'');

        // достаём первую фотку из listing_photos
        $ph = $pdo->prepare("SELECT file_name FROM listing_photos WHERE listing_id=? ORDER BY id ASC LIMIT 1");
        $ph->execute([$r['id']]);
        $thumb = $ph->fetchColumn();
      ?>
      <div class="rounded-xl border bg-white p-4 flex flex-col gap-2">
        <?php if($thumb): ?>
          <a href="/store/view.php?id=<?php echo (int)$r['id']; ?>" class="block">
            <div class="aspect-[4/3] overflow-hidden rounded-xl border bg-slate-100">
              <img src="/uploads/listings/<?php echo h($thumb); ?>" alt="" class="w-full h-full object-cover">
            </div>
          </a>
        <?php endif; ?>

        <div class="text-sm text-slate-500">
          Продавец: <?php echo h($r['seller_login']); ?>
        </div>
        <div class="font-semibold line-clamp-2">
          <?php echo h($title); ?>
        </div>

        <?php if($url): ?>
          <div class="text-sm break-all">
            <a class="underline" target="_blank" href="<?php echo h($url); ?>">
              <?php echo h($url); ?>
            </a>
          </div>
        <?php endif; ?>

        <div class="text-sm">Кэшбэк: <b>₽ <?php echo $cash; ?></b></div>
        <?php if($slots>0): ?>
          <div class="text-xs text-slate-600">
            Доступно слотов: <?php echo $slots; ?>
          </div>
        <?php endif; ?>

        <?php if($desc): ?>
          <div class="text-sm text-slate-700 line-clamp-3 whitespace-pre-line">
            <?php echo h($desc); ?>
          </div>
        <?php endif; ?>

        <div class="mt-2">
          <a class="px-3 py-2 rounded-xl bg-black text-white" 
             href="/store/view.php?id=<?php echo (int)$r['id']; ?>">
            Открыть
          </a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>
</body>
</html>
