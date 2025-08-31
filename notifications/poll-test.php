<?php
// Проверка, добегает ли до PHP и нет ли кэширования/500 на простом эндпойнте
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
echo json_encode(['ok'=>true,'ts'=>time(),'rand'=>mt_rand(1,9999)]);
