<?php
require_once __DIR__.'/../includes/config.php';
if (!is_admin()) { http_response_code(403); exit('Доступ запрещён'); }

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM listings WHERE id=?");
$stmt->execute([$id]);
$listing = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$listing) { exit("Объявление не найдено"); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title']);
    $product_url = trim($_POST['product_url']);
    $cashback    = (int)$_POST['cashback'];
    $slots       = (int)$_POST['slots'];
    $description = trim($_POST['description']);

    $upd = $pdo->prepare("UPDATE listings 
                          SET title=?, product_url=?, cashback=?, slots=?, description=?, updated_at=NOW() 
                          WHERE id=?");
    $upd->execute([$title, $product_url, $cashback, $slots, $description, $id]);

    header("Location: /admin/listings.php?tab=active");
    exit;
}
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <title>Редактирование объявления</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="p-6 bg-gray-100">
  <div class="max-w-2xl mx-auto bg-white shadow rounded p-6">
    <h1 class="text-xl font-bold mb-4">Редактировать объявление #<?= $listing['id'] ?></h1>
    <form method="post" class="space-y-4">
      <div>
        <label class="block text-sm font-medium">Название</label>
        <input name="title" value="<?= htmlspecialchars($listing['title']) ?>" class="w-full border rounded px-3 py-2">
      </div>
      <div>
        <label class="block text-sm font-medium">Ссылка на товар</label>
        <input name="product_url" value="<?= htmlspecialchars($listing['product_url']) ?>" class="w-full border rounded px-3 py-2">
      </div>
      <div>
        <label class="block text-sm font-medium">Кэшбэк</label>
        <input type="number" name="cashback" value="<?= (int)$listing['cashback'] ?>" class="w-full border rounded px-3 py-2">
      </div>
      <div>
        <label class="block text-sm font-medium">Слоты</label>
        <input type="number" name="slots" value="<?= (int)$listing['slots'] ?>" class="w-full border rounded px-3 py-2">
      </div>
      <div>
        <label class="block text-sm font-medium">Описание</label>
        <textarea name="description" rows="5" class="w-full border rounded px-3 py-2"><?= htmlspecialchars($listing['description']) ?></textarea>
      </div>
      <div class="flex gap-3">
        <button class="bg-black text-white px-4 py-2 rounded">Сохранить</button>
        <a href="/admin/listings.php?tab=active" class="px-4 py-2 border rounded">Отмена</a>
      </div>
    </form>
  </div>
</body>
</html>
