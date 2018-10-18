<?php

error_reporting(E_ALL);
ini_set('memory_limit','4096M');
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);
define('EOL',(PHP_SAPI == 'cli') ? PHP_EOL : '<br />');

require __DIR__.'/../../libs/PHPExcel/PHPExcel.php';

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

$json = $redis->lPop('data_export');
//$json = '{"_url":"\/typedata\/outdata","typeid":"3448","status":"0","sttime":"","endtime":"","image_file":"1"}';
if ($json) {
    require __DIR__.'/dataExport.php';
    die;
}

$json = $redis->lPop('dataadd_files');
if ($json) {
    require __DIR__.'/dataadd.php';
    die;
}

$tid = $redis->lPop('tid_status1');
if ($tid) {
    require __DIR__.'/updateTypeDataStatus.php';
    die;
}

$mysqli->close();
$redis->close();