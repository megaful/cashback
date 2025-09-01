<?php
require_once __DIR__.'/../../includes/config.php';
require_login();
$user = current_user();

if (strtoupper($user['role'] ?? '') !== 'SELLER') { http_response_code(403); exit('Доступ только для продавцов'); }

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }

/* категории витрины */
$LISTING_CATEGORIES = [
  'Одежда для мужчин','Одежда для женщин','Одежда для детей',
  'Обувь для мужчин','Обувь для женщин','Обувь для детей',
  'Аксессуары для мужчин','Аксессуары для женщин','Красота и здоровье',
  'Электроника и гаджеты','Компьютеры и периферия','Бытовая техника',
  'Дом и кухня','Мебель и декор','Спорт и активный отдых',
  'Детские товары и игрушки','Зоотовары','Автотовары и автоаксессуары',
  'Сад, дача и инструменты','Путешествия и багаж','Другое',
];

function col_exists(PDO $pdo, string $table, string $col): bool {
  $q = $pdo->prepare("SELECT COUNT(*) c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
  $q->execute([$table, $col]); return (int)($q->fetch()['c'] ?? 0) > 0;
}
function first_existing_col(PDO $pdo, string $table, array $candidates): ?string {
  foreach ($candidates as $c) if (col_exists($pdo,$table,$c)) return $c; return null;
}

$table = 'listings';
$urlCol     = first_existing_col($pdo, $table, ['url','product_url','link']);
$cashCol    = first_existing_col($pdo, $table, ['cashback','reward','amount','price','cb','sum']);
$descCol    = first_existing_col($pdo, $table, ['description','terms','conditions']);
$slotsCol   = first_existing_col($pdo, $table, ['slots','remaining','max_deals']);
$createdCol = col_exists($pdo,$table,'created_at') ? 'created_at' : null;
$updatedCol = col_exists($pdo,$table,'updated_at') ? 'updated_at' : null;
$statusCol  = col_exists($pdo,$table,'status') ? 'status' : null;
$categoryCol= col_exists($pdo,$table,'category') ? 'category' : null;

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    check_csrf();
    $title = trim($_POST['title'] ?? '');
    $urlIn = trim($_POST['url'] ?? '');
    $cashIn= trim($_POST['cashback'] ?? '');
    $descIn= trim($_POST['description'] ?? '');
    $slotsIn=trim($_POST['slots'] ?? '');
    $category=trim($_POST['category'] ?? '');

    if ($title==='' || $urlIn==='' || $cashIn==='' || $slotsIn==='') throw new Exception('Заполните все обязательные поля.');
    if (!filter_var($urlIn, FILTER_VALIDATE_URL)) throw new Exception('Поле «Ссылка на товар» должно содержать корректный URL.');
    if ($descIn==='') throw new Exception('Заполните поле «Условия выкупа».');
    if ($category==='' || !in_array($category,$LISTING_CATEGORIES,true)) throw new Exception('Выберите корректную категорию.');

    $cashVal  = (int)preg_replace('~[^\d]~u','',$cashIn);
    $slotsVal = (int)preg_replace('~[^\d]~u','',$slotsIn);
    if ($cashVal<=0)  throw new Exception('Кэшбэк должен быть положительным числом.');
    if ($slotsVal<=0) throw new Exception('Количество слотов должно быть положительным.');

    if (!$urlCol || !$cashCol || !$descCol || !$slotsCol) throw new Exception('В таблице listings отсутствуют нужные колонки.');

    $cols=['seller_id','title',$urlCol,$cashCol,$descCol,$slotsCol]; $vals=[':seller_id',':title',':url',':cash',':desc',':slots'];
    $params=[':seller_id'=>$user['id'],':title'=>$title,':url'=>$urlIn,':cash'=>$cashVal,':desc'=>$descIn,':slots'=>$slotsVal];
    if ($categoryCol){ $cols[]=$categoryCol; $vals[]=':category'; $params[':category']=$category; }
    if ($statusCol){ $cols[]=$statusCol; $vals[]=':status'; $params[':status']='PENDING'; }
    if ($createdCol){ $cols[]=$createdCol; $vals[]='NOW()'; }
    if ($updatedCol){ $cols[]=$updatedCol; $vals[]='NOW()'; }

    $sql="INSERT INTO {$table} (".implode(',',$cols).") VALUES (".implode(',',$vals).")";
    $st=$pdo->prepare($sql); $st->execute($params); $listingId=(int)$pdo->lastInsertId();

    // фото
    $uploadDir = __DIR__.'/../../uploads/listings/'; if (!is_dir($uploadDir)) mkdir($uploadDir,0777,true);
    $allowedExt=['jpg','jpeg','png']; $maxSize=10*1024*1024;
    if (!empty($_FILES['photos']['name'][0])) {
      $files=$_FILES['photos']; $count=min(count($files['name']),5);
      for($i=0;$i<$count;$i++){
        if($files['error'][$i]!==UPLOAD_ERR_OK) continue;
        $ext=strtolower(pathinfo($files['name'][$i],PATHINFO_EXTENSION));
        if(!in_array($ext,$allowedExt)||$files['size'][$i]>$maxSize) continue;
        $new=uniqid('photo_',true).'.'.$ext; $dest=$uploadDir.$new;
        if (move_uploaded_file($files['tmp_name'][$i],$dest)){
          $pdo->prepare("INSERT INTO listing_photos (listing_id,file_name) VALUES (?,?)")->execute([$listingId,$new]);
        }
      }
    }

    if (function_exists('notify')) notify($pdo,$user['id'],'Объявление отправлено на модерацию','/seller/listings/index.php?tab=pending');
    header('Location: /seller/listings/index.php?tab=pending'); exit;
  } catch(Throwable $e){ $error=$e->getMessage(); }
}
?>
<!doctype html>
<html lang="ru"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Выставить товар — Cashback-Market</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
  :root{ --g1:#8A00FF; --g2:#005BFF; }
  body{background:linear-gradient(180deg,#f5ecff,#eef4ff 220px),linear-gradient(180deg,var(--g1),var(--g2)) fixed;}
  .card{border:1px solid #e6e8f0;border-radius:20px}
  .btn-grad{background:linear-gradient(90deg,#8A00FF,#005BFF);color:#fff}
</style>
</head>
<body class="text-slate-900">
<?php @include __DIR__.'/../../includes/topbar.php'; ?>

<main class="max-w-3xl mx-auto px-4 py-5 md:py-8">
  <a href="/seller/listings/index.php" class="text-sm">← Мои объявления</a>
  <h1 class="text-2xl md:text-3xl font-bold mt-2">Выставить товар</h1>

  <?php if (!empty($error)): ?>
    <div class="card p-3 mt-3 bg-rose-50 text-rose-900 border-rose-200"><?= h($error) ?></div>
  <?php endif; ?>

  <form class="card bg-white p-4 md:p-6 mt-4 space-y-3" method="post" enctype="multipart/form-data" novalidate>
    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">

    <div>
      <label class="block text-sm">Наименование товара *</label>
      <input name="title" required class="w-full border rounded-xl px-3 py-2" value="<?= h($_POST['title'] ?? '') ?>">
    </div>

    <div>
      <label class="block text-sm">Ссылка на товар *</label>
      <input name="url" type="url" placeholder="https://…" required class="w-full border rounded-xl px-3 py-2" value="<?= h($_POST['url'] ?? '') ?>">
    </div>

    <div>
      <label class="block text-sm">Категория *</label>
      <select name="category" required class="w-full border rounded-xl px-3 py-2">
        <option value="" disabled <?= empty($_POST['category'])?'selected':''; ?>>Выберите категорию…</option>
        <?php foreach ($LISTING_CATEGORIES as $cat): ?>
          <option value="<?= h($cat) ?>" <?= (($_POST['category'] ?? '')===$cat)?'selected':''; ?>><?= h($cat) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
      <div>
        <label class="block text-sm">Кэшбэк, ₽ *</label>
        <input name="cashback" type="text" placeholder="500" required class="w-full border rounded-xl px-3 py-2" value="<?= h($_POST['cashback'] ?? '') ?>">
      </div>
      <div>
        <label class="block text-sm">Количество слотов *</label>
        <input name="slots" type="text" placeholder="10" required class="w-full border rounded-xl px-3 py-2" value="<?= h($_POST['slots'] ?? '') ?>">
      </div>
    </div>

    <div>
      <label class="block text-sm">Условия выкупа *</label>
      <textarea name="description" rows="5" required class="w-full border rounded-xl px-3 py-2"><?= h($_POST['description'] ?? '') ?></textarea>
    </div>

    <div>
      <label class="block text-sm">Фотографии товара (1–5 файлов, JPG/PNG, ≤10 МБ каждый)</label>
      <input type="file" name="photos[]" accept=".jpg,.jpeg,.png" multiple required>
    </div>

    <button class="px-4 py-2 rounded-full btn-grad">Отправить на модерацию</button>
  </form>
</main>
</body></html>
