<?php
error_reporting(E_ALL); ini_set('display_errors','1');
require_once __DIR__.'/../includes/config.php';
if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }
header('Content-Type: text/plain; charset=utf-8');
echo "session_status=".session_status()." (2=active)\n";
echo "session_id=".session_id()."\n";
$u = function_exists('current_user') ? current_user() : null;
echo "user=".json_encode($u, JSON_UNESCAPED_UNICODE)."\n";
