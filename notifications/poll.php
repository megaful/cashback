<?php
// SAFE STUB: temporary rollback for notifications polling.
// Always returns empty list so the site works without sessions/DB touching here.
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
echo json_encode(['ok'=>true,'count'=>0,'items'=>[]], JSON_UNESCAPED_UNICODE);
