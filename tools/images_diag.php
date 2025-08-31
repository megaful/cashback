<?php
require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/../includes/product_images.php';

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }

$lid = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$url = trim($_GET['url'] ?? '');

$listing = null;
if ($lid > 0) {
  $st = $pdo->prepare("SELECT * FROM listings WHERE id=?");
  $st->execute([$lid]);
  $listing = $st->fetch();
  if (!$listing) { echo "listing id $lid not found"; exit; }
} elseif ($url) {
  // подставим фейковый листинг, чтобы не ломать интерфейс
  $listing = ['id'=>-1, 'url'=>$url];
} else {
  echo "Usage: /tools/images_diag.php?id=LISTING_ID  или  /tools/images_diag.php?url=ENCODED_URL";
  exit;
}

$force = isset($_GET['force']) ? true : false;
$imgs = get_listing_images($pdo, $listing, $force);

header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html><meta charset="utf-8">
<style>
  body{font:14px/1.4 system-ui,Segoe UI,Arial,Helvetica}
  .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px}
  .card{border:1px solid #ddd;border-radius:10px;padding:10px}
  .thumb{aspect-ratio:4/3;background:#f5f5f5;display:flex;align-items:center;justify-content:center;overflow:hidden;border-radius:8px}
  img{max-width:100%;max-height:100%}
</style>
<h2>images_diag</h2>
<p>listing id: <b><?php echo h($listing['id']); ?></b> &middot; url: <a href="<?php echo h($listing['url'] ?? ($listing['product_url'] ?? '')); ?>" target="_blank"><?php echo h($listing['url'] ?? ($listing['product_url'] ?? '')); ?></a></p>
<p>force refresh: <?php echo $force ? 'yes' : 'no'; ?></p>
<p>found images: <b><?php echo count($imgs); ?></b></p>

<div class="grid">
  <?php foreach ($imgs as $u): ?>
    <div class="card">
      <div class="thumb"><img src="<?php echo h($u); ?>"></div>
      <div style="word-break:break-all;margin-top:8px;font-size:12px"><?php echo h($u); ?></div>
    </div>
  <?php endforeach; ?>
</div>

<p style="margin-top:12px">
  <a href="?<?php echo $lid>0 ? 'id='.$lid : 'url='.urlencode($url); ?>&force=1">Force refresh</a>
</p>
