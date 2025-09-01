<?php
require_once __DIR__.'/../includes/config.php';

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }
function val($row, $keys, $default = null) {
  foreach ((array)$keys as $k) if (isset($row[$k]) && $row[$k] !== '' && $row[$k] !== null) return $row[$k];
  return $default;
}
function col_exists(PDO $pdo, string $table, string $col): bool {
  $q = $pdo->prepare("SELECT COUNT(*) c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?");
  $q->execute([$table,$col]); return (int)($q->fetch()['c'] ?? 0) > 0;
}

/* входные фильтры */
$q     = trim($_GET['q'] ?? '');
$cat   = trim($_GET['category'] ?? '');
$min   = trim($_GET['min'] ?? '');
$max   = trim($_GET['max'] ?? '');
$min_i = ($min !== '' ? max(0, (int)preg_replace('~[^\d]~', '', $min)) : null);
$max_i = ($max !== '' ? max(0, (int)preg_replace('~[^\d]~', '', $max)) : null);

/* колонки */
$tbl = 'listings';
$hasTitle = col_exists($pdo,$tbl,'title');
$hasDesc  = col_exists($pdo,$tbl,'description');
$urlCol   = col_exists($pdo,$tbl,'product_url') ? 'product_url' : (col_exists($pdo,$tbl,'url') ? 'url' : null);
$hasCat   = col_exists($pdo,$tbl,'category');
$cashCol  = col_exists($pdo,$tbl,'cashback') ? 'cashback' : (col_exists($pdo,$tbl,'cashback_rub') ? 'cashback_rub' : null);

/* фиксированный список категорий */
$categories = [
  'Одежда для мужчин','Одежда для женщин','Одежда для детей',
  'Обувь для мужчин','Обувь для женщин','Обувь для детей',
  'Аксессуары для мужчин','Аксессуары для женщин','Красота и здоровье',
  'Электроника и гаджеты','Компьютеры и периферия','Бытовая техника',
  'Дом и кухня','Мебель и декор','Спорт и активный отдых',
  'Детские товары и игрушки','Зоотовары','Автотовары и автоаксессуары',
  'Сад, дача и инструменты','Путешествия и багаж','Другое',
];

/* выборка */
$rows = []; $err = '';
try {
  $where = ["l.status='ACTIVE'"]; $params = [];
  if ($q !== '') {
    $like = '%'.$q.'%'; $parts = [];
    if ($hasTitle) { $parts[]='l.title LIKE ?'; $params[]=$like; }
    if ($hasDesc)  { $parts[]='l.description LIKE ?'; $params[]=$like; }
    if ($urlCol)   { $parts[]="l.`$urlCol` LIKE ?"; $params[]=$like; }
    if ($parts) $where[] = '('.implode(' OR ', $parts).')';
  }
  if ($hasCat && $cat!==''){ $where[]='l.category=?'; $params[]=$cat; }
  if ($cashCol && $min_i!==null){ $where[] = "l.`$cashCol`>=?"; $params[]=$min_i; }
  if ($cashCol && $max_i!==null){ $where[] = "l.`$cashCol`<=?"; $params[]=$max_i; }

  $sql = "SELECT l.*, u.login AS seller_login
          FROM listings l JOIN users u ON u.id=l.seller_id
          ".($where?'WHERE '.implode(' AND ',$where):'')."
          ORDER BY l.id DESC";
  $st=$pdo->prepare($sql); $st->execute($params); $rows=$st->fetchAll();
}catch(Throwable $e){ $err=$e->getMessage(); }
?>
<!doctype html>
<html lang="ru"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Витрина — Cashback-Market</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
  :root{ --g1:#8A00FF; --g2:#005BFF; }
  body{background:linear-gradient(180deg,#f5ecff,#eef4ff 220px),linear-gradient(180deg,var(--g1),var(--g2)) fixed;}
  .glass{background:rgba(255,255,255,.88);backdrop-filter:saturate(140%) blur(6px);}
  .card{border:1px solid #e6e8f0;border-radius:20px}
  .chip{display:inline-flex;align-items:center;gap:8px;border:1px solid #e5e7eb;border-radius:9999px;padding:8px 14px;background:#fff}
  .btn-grad{background:linear-gradient(90deg,#8A00FF,#005BFF);color:#fff}
  .btn-grad:hover{filter:brightness(.95)}
</style>
</head>
<body class="text-slate-900">
<?php @include __DIR__.'/../includes/topbar.php'; ?>

<main class="max-w-6xl mx-auto px-4 py-5 md:py-8">
  <section class="card glass px-4 py-4 md:px-6 md:py-6 mb-4 md:mb-6">
    <h1 class="text-2xl md:text-3xl font-bold">Витрина</h1>
    <p class="mt-1 text-slate-600">Актуальные предложения с кэшбэком и понятными условиями.</p>
  </section>

  <section class="card glass p-4 md:p-6 mb-4 md:mb-6">
    <form method="get" class="grid grid-cols-1 md:grid-cols-6 gap-3">
      <div class="md:col-span-3">
        <label class="block text-xs text-slate-600 mb-1">Поиск</label>
        <input type="search" name="q" value="<?= h($q) ?>" placeholder="Название, описание, ссылка…"
               class="w-full border rounded-xl px-3 py-2">
      </div>
      <div class="md:col-span-2">
        <label class="block text-xs text-slate-600 mb-1">Категория</label>
        <select name="category" class="w-full border rounded-xl px-3 py-2">
          <option value="">Все категории</option>
          <?php foreach($categories as $c): ?>
            <option value="<?= h($c) ?>" <?= $c===$cat?'selected':'' ?>><?= h($c) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-xs text-slate-600 mb-1">Кэшбэк от, ₽</label>
          <input type="number" min="0" name="min" value="<?= h($min_i ?? '') ?>" class="w-full border rounded-xl px-3 py-2">
        </div>
        <div>
          <label class="block text-xs text-slate-600 mb-1">Кэшбэк до, ₽</label>
          <input type="number" min="0" name="max" value="<?= h($max_i ?? '') ?>" class="w-full border rounded-xl px-3 py-2">
        </div>
      </div>
      <div class="md:col-span-6 flex gap-2">
        <button class="px-4 py-2 rounded-full btn-grad">Применить</button>
        <?php if ($q!=='' || $cat!=='' || $min_i!==null || $max_i!==null): ?>
          <a href="/store/index.php" class="px-4 py-2 rounded-full chip">Сбросить</a>
        <?php endif; ?>
      </div>
    </form>
  </section>

  <?php if ($err): ?>
    <div class="card glass p-4 text-rose-800 bg-rose-50/80 border-rose-200">Ошибка: <?= h($err) ?></div>
  <?php elseif(!$rows): ?>
    <div class="card glass p-6 text-slate-600">Под ваши критерии ничего не найдено.</div>
  <?php else: ?>
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
      <?php foreach ($rows as $r):
        $title = val($r,'title','Без названия');
        $url   = val($r,['url','product_url']);
        $cash  = (int)preg_replace('~[^\d]~u','',(string)val($r,['cashback','cashback_rub','reward','amount'],0));
        $slots = (int)val($r,['slots','remaining','max_deals'],0);
        $desc  = val($r,['description','terms','conditions'],'');
        $catV  = val($r,'category','Другое');
        $ph = $pdo->prepare("SELECT file_name FROM listing_photos WHERE listing_id=? ORDER BY id ASC LIMIT 1");
        $ph->execute([$r['id']]); $thumb=$ph->fetchColumn();
      ?>
      <div class="card bg-white p-4 flex flex-col">
        <?php if($thumb): ?>
          <a href="/store/view.php?id=<?= (int)$r['id'] ?>" class="block">
            <div class="aspect-[3/4] overflow-hidden rounded-xl border bg-white">
              <img src="/uploads/listings/<?= h($thumb) ?>" alt="" class="w-full h-full object-contain">
            </div>
          </a>
        <?php endif; ?>

        <div class="mt-2 text-xs inline-flex w-fit items-center gap-2 rounded-full border px-2 py-1 bg-slate-50">
          Категория: <b class="text-slate-800"><?= h($catV ?: 'Другое') ?></b>
        </div>
        <div class="mt-1 text-sm text-slate-500">Продавец: <?= h($r['seller_login']) ?></div>

        <div class="mt-1 font-semibold line-clamp-2"><?= h($title) ?></div>

        <?php if($url): ?>
          <div class="text-sm break-all mt-1">
            <a class="underline" target="_blank" href="<?= h($url) ?>"><?= h($url) ?></a>
          </div>
        <?php endif; ?>

        <div class="mt-1 text-sm">Кэшбэк: <b>₽ <?= $cash ?></b></div>
        <?php if($slots>0): ?><div class="text-xs text-slate-600">Доступно слотов: <?= $slots ?></div><?php endif; ?>

        <?php if($desc): ?>
          <div class="mt-2 text-sm text-slate-700">
            <b>Условия выкупа</b>
            <div class="mt-1 line-clamp-3"><?= h($desc) ?></div>
          </div>
        <?php endif; ?>

        <div class="mt-3">
          <a class="inline-flex items-center gap-2 rounded-full px-4 py-2 btn-grad" href="/store/view.php?id=<?= (int)$r['id'] ?>">↗ Открыть</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>
</body></html>
