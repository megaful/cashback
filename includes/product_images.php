<?php
/**
 * product_images.php — извлечение фото товара с Wildberries / Ozon (+любой страницы),
 * кэш в таблице listing_images (listing_id, images_json, fetched_at).
 *
 * + Отладка: сохраняет wb_debug.html и wb_debug.json в эту же папку всегда.
 */

function http_get(string $url, int $timeout = 10): ?string {
  if (function_exists('curl_init')) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_MAXREDIRS      => 5,
      CURLOPT_TIMEOUT        => $timeout,
      CURLOPT_CONNECTTIMEOUT => $timeout,
      CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36',
      CURLOPT_SSL_VERIFYPEER => false,
      CURLOPT_SSL_VERIFYHOST => false,
      CURLOPT_HTTPHEADER     => [
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language: ru,en;q=0.9',
      ],
    ]);
    $data = curl_exec($ch);
    if ($data === false) {
      error_log("http_get: curl error=".curl_error($ch));
    }
    curl_close($ch);
    return $data !== false ? $data : null;
  }

  $ctx = stream_context_create([
    'http' => [
      'method'  => 'GET',
      'timeout' => $timeout,
      'header'  => "User-Agent: Mozilla/5.0\r\nAccept-Language: ru,en;q=0.9\r\n",
    ]
  ]);
  $html = @file_get_contents($url, false, $ctx);
  if ($html === false) {
    error_log("http_get: file_get_contents failed for $url");
  }
  return $html !== false ? $html : null;
}

function normalize_url(?string $u): ?string {
  if (!$u) return null;
  $u = trim($u, " \t\n\r\0\x0B\"'");
  if ($u === '') return null;
  if (!preg_match('~^https?://~i', $u)) return null;
  if (stripos($u, 'data:') === 0) return null;
  return $u;
}
function uniq_limit(array $arr, int $limit = 16): array {
  $out = [];
  $seen = [];
  foreach ($arr as $u) {
    $nu = normalize_url($u);
    if (!$nu) continue;
    if (isset($seen[$nu])) continue;
    $seen[$nu] = true;
    $out[] = $nu;
    if (count($out) >= $limit) break;
  }
  return $out;
}

function extract_from_jsonld(string $html): array {
  $res = [];
  if (preg_match_all('~<script[^>]+type=["\']application/ld\+json["\'][^>]*>(.*?)</script>~is', $html, $m)) {
    foreach ($m[1] as $json) {
      $json = html_entity_decode($json, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8');
      $obj  = json_decode($json, true);
      if (!$obj) continue;
      $cands = [];
      if (isset($obj['image'])) {
        $cands = is_array($obj['image']) ? $obj['image'] : [$obj['image']];
      } elseif (isset($obj['@graph']) && is_array($obj['@graph'])) {
        foreach ($obj['@graph'] as $g) {
          if (isset($g['image'])) {
            $cands = array_merge($cands, is_array($g['image']) ? $g['image'] : [$g['image']]);
          }
        }
      }
      foreach ($cands as $u) {
        if (is_array($u)) {
          if (isset($u['url'])) $res[] = $u['url'];
          foreach ($u as $vv) if (is_string($vv)) $res[] = $vv;
        } elseif (is_string($u)) {
          $res[] = $u;
        }
      }
    }
  }
  return $res;
}

function extract_from_og(string $html): array {
  $res = [];
  if (preg_match_all('~<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)["\']~i', $html, $m)) {
    foreach ($m[1] as $u) $res[] = $u;
  }
  if (preg_match_all('~<meta[^>]+name=["\']twitter:image["\'][^>]+content=["\']([^"\']+)["\']~i', $html, $m2)) {
    foreach ($m2[1] as $u) $res[] = $u;
  }
  return $res;
}

function extract_from_img_tags(string $html): array {
  $res = [];
  if (preg_match_all('~<img[^>]+src=["\']([^"\']+)["\']~i', $html, $m)) {
    foreach ($m[1] as $u) $res[] = $u;
  }
  if (preg_match_all('~<img[^>]+srcset=["\']([^"\']+)["\']~i', $html, $mm)) {
    foreach ($mm[1] as $srcset) {
      foreach (preg_split('~\s*,\s*~', $srcset) as $part) {
        $pieces = preg_split('~\s+~', trim($part));
        if (!empty($pieces[0])) $res[] = $pieces[0];
      }
    }
  }
  return $res;
}

function extract_from_big_json(string $html): array {
  $res = [];
  if (preg_match_all('~<script[^>]*>(.*?)</script>~is', $html, $m)) {
    foreach ($m[1] as $block) {
      if (stripos($block, 'image') === false && stripos($block, 'photo') === false) continue;
      if (preg_match_all('~https?://[^\s"\'<>]+\.(?:jpg|jpeg|png|webp)(\?[^\s"\'<>]+)?~i', $block, $mm)) {
        foreach ($mm[0] as $u) $res[] = $u;
      }
    }
  }
  return $res;
}

function extract_images_any(string $html): array {
  $all = [];
  $all = array_merge($all, extract_from_jsonld($html));
  $all = array_merge($all, extract_from_og($html));
  $all = array_merge($all, extract_from_img_tags($html));
  $all = array_merge($all, extract_from_big_json($html));
  $all = array_values(array_filter($all, function($u){
    return (bool)preg_match('~\.(jpg|jpeg|png|webp)(\?|$)~i', $u);
  }));
  return uniq_limit($all, 16);
}

function get_listing_images(PDO $pdo, array $listing, bool $forceRefresh = false): array {
  $lid = (int)($listing['id'] ?? 0);
  if ($lid <= 0) return [];

  error_log("get_listing_images: start for listing_id=$lid");

  $st = $pdo->prepare("SELECT images_json, fetched_at FROM listing_images WHERE listing_id=?");
  $st->execute([$lid]);
  if ($row = $st->fetch()) {
    $age = time() - strtotime($row['fetched_at']);
    $imgs = json_decode($row['images_json'], true);
    if (is_array($imgs) && $age < 86400 && !$forceRefresh) {
      error_log("get_listing_images: returning from cache, count=".count($imgs));
      return $imgs;
    }
  }

  $src = $listing['url'] ?? ($listing['product_url'] ?? null);
  if (!$src) return [];

  $html = http_get($src);
  $len = $html ? strlen($html) : 0;
  error_log("get_listing_images: downloaded len=$len from $src");

  // даже если $html пустой — пишем debug
  file_put_contents(__DIR__.'/wb_debug.html', $html ?: '');
  $imgs = $html ? extract_images_any($html) : [];
  file_put_contents(__DIR__.'/wb_debug.json', json_encode([
    'url'=>$src,
    'html_len'=>$len,
    'imgs_found'=>$imgs
  ], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));

  $json = json_encode($imgs, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
  if ($row ?? false) {
    $upd = $pdo->prepare("UPDATE listing_images SET images_json=?, fetched_at=NOW() WHERE listing_id=?");
    $upd->execute([$json, $lid]);
  } else {
    $ins = $pdo->prepare("INSERT INTO listing_images (listing_id, images_json, fetched_at) VALUES (?, ?, NOW())");
    $ins->execute([$lid, $json]);
  }
  return $imgs;
}
