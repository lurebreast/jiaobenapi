<?php

error_reporting(E_ALL);
ini_set('memory_limit','4096M');
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);
define('EOL',(PHP_SAPI == 'cli') ? PHP_EOL : '<br />');

$redis = new Redis();
$redis->pconnect('127.0.0.1');

$config = include_once __DIR__.'/../config/config.php';
$host = $config['database']['host'].":".$config['database']['port'];
$user = $config['database']['username'];
$pwd = $config['database']['password'];
$db = $config['database']['dbname'];

$mysqli = new mysqli($host, $user, $pwd, $db);

//如果连接错误
if (mysqli_connect_errno()) {
    error_log("连接数据库失败：" . mysqli_connect_error());
    $mysqli = null;
    $redis->close();
    die;
}