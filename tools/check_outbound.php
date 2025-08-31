<?php
echo "curl: ".(function_exists('curl_init')?'YES':'NO')."<br>";
echo "allow_url_fopen: ".(ini_get('allow_url_fopen')?'YES':'NO')."<br>";
require_once __DIR__.'/../includes/product_images.php';
$html = http_get('https://www.wildberries.ru/catalog/275025912/detail.aspx');
echo $html ? 'html len='.strlen($html) : 'html = NULL';
