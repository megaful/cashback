<?php
require_once __DIR__.'/../../includes/config.php';
require_login();
$user = current_user();
if (strtoupper($user['role']??'')!=='SELLER' && !is_admin()) { http_response_code(403); exit('Доступ запрещён'); }

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }
function val($row,$keys,$d=null){ foreach((array)$keys as $k){ if(array_key_exists($k,$row) && $row[$k]!=='' && $row[$k]!==null) return $row[$k]; } return $d; }
function status_ru_local($s){ return ['PENDING'=>'На модерации','ACTIVE'=>'Активно','REJECTED'=>'Отклонено','ARCHIVED'=>'Архив'][$s]??$s; }

$tab=$_GET['tab']??'all';
$uid=(int)$user['id'];

$counts=['all'=>0,'active'=>0,'pending'=>0,'rejected'=>0,'archived'=>0];
$st=$pdo->prepare("SELECT status,COUNT(*) c FROM listings WHERE seller_id=? GROUP BY status");
$st->execute([$uid]); while($r=$st->fetch()){ $counts['all']+=(int)$r['c']; $k=strtolower($r['status']); if(isset($counts[$k])) $counts[$k]=(int)$r['c']; }

$params=[$uid]; $where="seller_id=?";
if($tab==='active')   {$where.=" AND status='ACTIVE'";}
elseif($tab==='pending') {$where.=" AND status='PENDING'";}
elseif($tab==='rejected'){$where.=" AND status='REJECTED'";}
elseif($tab==='archived'){$where.=" AND status='ARCHIVED'";}

$sql="SELECT * FROM listings WHERE $where ORDER BY id DESC";
$q=$pdo->prepare($sql); $q->execute($params); $rows=$q->fetchAll();
?>
<!doctype html>
<html lang="ru"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Мои объявления — Cashback-Market</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
  :root{ --g1:#8A00FF; --g2:#005BFF; }
  body{background:linear-gradient(180deg,#f5ecff,#eef4ff 220px),linear-gradient(180deg,var(--g1),var(--g2)) fixed;}
  .card{border:1px solid #e6e8f0;border-radius:20px}
  .chip{display:inline-flex;align-items:center;gap:8px;border:1px solid #e5e7eb;border-radius:9999px;padding:8px 14px;background:#fff}
  .chip.active{background:linear-gradient(90deg,#8A00FF,#005BFF);color:#fff;border-color:transparent}
  .btn-grad{background:linear-gradient(90deg,#8A00FF,#005BFF);color:#fff}
</style>
</head>
<body class="text-slate-900">
<?php @include __DIR__.'/../../includes/topbar.php'; ?>

<main class="max-w-6xl mx-auto px-4 py-5 md:py-8">
  <section class="card bg-white px-4 py-4 md:px-6 md:py-6 mb-4 md:mb-6">
    <div class="flex items-start justify-between gap-3">
      <div>
        <h1 class="text-2xl md:text-3xl font-bold">Мои объявления</h1>
        <p class="mt-1 text-slate-600">Управляйте карточками товаров и их статусами.</p>
      </div>
      <a class="chip" href="/seller/listings/create.php">🛍️ Выставить товар</a>
    </div>
  </section>

  <div class="card bg-white p-4 md:p-6">
    <div class="flex gap-2 flex-wrap mb-4">
      <a class="chip <?= $tab==='all'?'active':'' ?>"      href="?tab=all">Все (<?= (int)$counts['all'] ?>)</a>
      <a class="chip <?= $tab==='active'?'active':'' ?>"   href="?tab=active">Активные (<?= (int)$counts['active'] ?>)</a>
      <a class="chip <?= $tab==='pending'?'active':'' ?>"  href="?tab=pending">На модерации (<?= (int)$counts['pending'] ?>)</a>
      <a class="chip <?= $tab==='rejected'?'active':'' ?>" href="?tab=rejected">Отклонённые (<?= (int)$counts['rejected'] ?>)</a>
      <a class="chip <?= $tab==='archived'?'active':'' ?>" href="?tab=archived">Архив (<?= (int)$counts['archived'] ?>)</a>
      <span class="grow"></span>
      <a class="chip" href="/seller/listings/create.php">➕ Новое объявление</a>
    </div>

    <?php if(!$rows): ?>
      <div class="card p-6 bg-slate-50 text-slate-600">Объявлений нет.</div>
    <?php else: ?>
      <div class="grid md:grid-cols-2 gap-4 md:gap-6">
        <?php foreach($rows as $r):
          $title = val($r,'title','Без названия');
          $url   = val($r,['url','product_url']);
          $cash  = (int)preg_replace('~[^\d]~u','', (string)val($r,['cashback','reward','amount'],0));
          $slots = (int)val($r,['slots','remaining','max_deals'],0);
          $reason = trim((string)val($r,'reason',''));
        ?>
        <div class="card bg-white p-4 flex flex-col gap-2">
          <div class="text-sm text-slate-500">#<?= (int)$r['id']; ?> · статус: <b><?= h(status_ru_local($r['status'])) ?></b></div>
          <div class="font-semibold"><?= h($title) ?></div>
          <?php if($url): ?><a class="underline text-sm break-all" target="_blank" href="<?= h($url) ?>"><?= h($url) ?></a><?php endif; ?>
          <div class="text-sm">Кэшбэк: <b>₽ <?= $cash ?></b></div>
          <div class="text-xs text-slate-600">Доступно слотов: <?= $slots ?></div>

          <?php if(($r['status']==='REJECTED' || $r['status']==='ARCHIVED') && $reason!==''): ?>
            <div class="rounded-lg bg-rose-50 text-rose-800 px-3 py-2 text-sm">Причина: <?= h($reason) ?></div>
          <?php endif; ?>

          <div class="mt-1">
            <a class="chip" target="_blank" href="/store/view.php?id=<?= (int)$r['id'] ?>">↗ Открыть на витрине</a>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</main>
</body></html>
