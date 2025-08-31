<?php
require_once __DIR__.'/../../includes/config.php';
require_login();
$user = current_user();

if (strtoupper($user['role'] ?? '') !== 'SELLER') {
  http_response_code(403);
  exit('Доступ только для продавцов');
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }

// Проверка существования колонки
function col_exists(PDO $pdo, string $table, string $col): bool {
  $q = $pdo->prepare("SELECT COUNT(*) c
                      FROM INFORMATION_SCHEMA.COLUMNS
                      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
  $q->execute([$table, $col]);
  return (int)($q->fetch()['c'] ?? 0) > 0;
}
function first_existing_col(PDO $pdo, string $table, array $candidates): ?string {
  foreach ($candidates as $c) if (col_exists($pdo, $table, $c)) return $c;
  return null;
}

$table = 'listings';
$urlCol   = first_existing_col($pdo, $table, ['url','product_url','link']);
$cashCol  = first_existing_col($pdo, $table, ['cashback','reward','amount','price','cb','sum']);
$descCol  = first_existing_col($pdo, $table, ['description','terms','conditions']);
$slotsCol = first_existing_col($pdo, $table, ['slots','remaining','max_deals']);
$createdCol = col_exists($pdo,$table,'created_at') ? 'created_at' : null;
$updatedCol = col_exists($pdo,$table,'updated_at') ? 'updated_at' : null;
$statusCol  = col_exists($pdo,$table,'status') ? 'status' : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    check_csrf();

    $title = trim($_POST['title'] ?? '');
    $urlIn = trim($_POST['url'] ?? '');
    $cashIn = trim($_POST['cashback'] ?? '');
    $descIn = trim($_POST['description'] ?? '');
    $slotsIn = trim($_POST['slots'] ?? '');

    if ($title === '' || $urlIn === '' || $cashIn === '' || $slotsIn === '') {
      throw new Exception('Заполните все обязательные поля.');
    }
    if (!filter_var($urlIn, FILTER_VALIDATE_URL)) {
      throw new Exception('Поле «Ссылка на товар» должно содержать корректный URL.');
    }

    $cashVal  = (int)preg_replace('~[^\d]~u', '', $cashIn);
    $slotsVal = (int)preg_replace('~[^\d]~u', '', $slotsIn);
    if ($cashVal <= 0)  throw new Exception('Кэшбэк должен быть положительным числом.');
    if ($slotsVal <= 0) throw new Exception('Количество слотов должно быть положительным.');

    if (!$urlCol)   throw new Exception('В таблице listings отсутствует колонка для ссылки.');
    if (!$cashCol)  throw new Exception('В таблице listings отсутствует колонка для кэшбэка.');
    if (!$descCol)  throw new Exception('В таблице listings отсутствует колонка для условий.');
    if (!$slotsCol) throw new Exception('В таблице listings отсутствует колонка для слотов.');

    $cols = ['seller_id','title', $urlCol, $cashCol, $descCol, $slotsCol];
    $vals = [':seller_id',':title', ':url',   ':cash',  ':desc',  ':slots'];
    $params = [
      ':seller_id' => $user['id'],
      ':title'     => $title,
      ':url'       => $urlIn,
      ':cash'      => $cashVal,
      ':desc'      => $descIn,
      ':slots'     => $slotsVal,
    ];

    if ($statusCol) { $cols[] = $statusCol; $vals[]=':status'; $params[':status']='PENDING'; }
    if ($createdCol){ $cols[] = $createdCol; $vals[]='NOW()'; }
    if ($updatedCol){ $cols[] = $updatedCol; $vals[]='NOW()'; }

    $sql = "INSERT INTO {$table} (".implode(',', $cols).") VALUES (".implode(',', $vals).")";
    $st  = $pdo->prepare($sql);
    $st->execute($params);
    $listingId = (int)$pdo->lastInsertId();

    // === Загрузка фото ===
    $uploadDir = __DIR__.'/../../uploads/listings/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $allowedExt = ['jpg','jpeg','png'];
    $maxSize = 10*1024*1024; // 10 MB

    if (!empty($_FILES['photos']['name'][0])) {
      $files = $_FILES['photos'];
      $count = min(count($files['name']), 5);
      for ($i=0; $i<$count; $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
        $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt)) continue;
        if ($files['size'][$i] > $maxSize) continue;
        $newName = uniqid('photo_', true).'.'.$ext;
        $dest = $uploadDir.$newName;
        if (move_uploaded_file($files['tmp_name'][$i], $dest)) {
          $pdo->prepare("INSERT INTO listing_photos (listing_id,file_name) VALUES (?,?)")
              ->execute([$listingId, $newName]);
        }
      }
    }

    if (function_exists('notify')) {
      notify($pdo, $user['id'], 'Объявление отправлено на модерацию', '/seller/listings/index.php?tab=pending');
    }

    header('Location: /seller/listings/index.php?tab=pending');
    exit;

  } catch (Throwable $e) {
    $error = $e->getMessage();
  }
}
?>
<!doctype html>
<html lang="ru"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Выставить товар</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50">
<?php @include __DIR__.'/../../includes/topbar.php'; ?>
<main class="max-w-3xl mx-auto px-4 py-6">
  <a href="/seller/listings/index.php" class="text-sm">&larr; Мои объявления</a>
  <h1 class="text-2xl font-semibold mt-2">Выставить товар</h1>

  <?php if (!empty($error)): ?>
    <div class="rounded-xl border bg-red-50 text-red-800 p-3 mt-3"><?php echo h($error); ?></div>
  <?php endif; ?>

  <form class="mt-4 space-y-3" method="post" enctype="multipart/form-data" novalidate>
    <input type="hidden" name="csrf_token" value="<?php echo h(csrf_token()); ?>">

    <div>
      <label class="block text-sm">Наименование товара *</label>
      <input name="title" required class="w-full border rounded-xl px-3 py-2">
    </div>

    <div>
      <label class="block text-sm">Ссылка на товар *</label>
      <input name="url" type="url" placeholder="https://..." required class="w-full border rounded-xl px-3 py-2">
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
      <div>
        <label class="block text-sm">Кэшбэк, ₽ *</label>
        <input name="cashback" type="text" placeholder="500" required class="w-full border rounded-xl px-3 py-2">
      </div>
      <div>
        <label class="block text-sm">Количество слотов *</label>
        <input name="slots" type="text" placeholder="10" required class="w-full border rounded-xl px-3 py-2">
      </div>
    </div>

    <div>
      <label class="block text-sm">Условия выкупа (опционально)</label>
      <textarea name="description" rows="5" class="w-full border rounded-xl px-3 py-2"></textarea>
    </div>

    <div>
      <label class="block text-sm">Фотографии товара (1–5 файлов, JPG/PNG, ≤10 МБ каждый)</label>
      <input type="file" name="photos[]" accept=".jpg,.jpeg,.png" multiple required>
    </div>

    <button class="px-4 py-2 rounded-xl bg-black text-white">Отправить на модерацию</button>
  </form>
</main>
</body>
</html>
