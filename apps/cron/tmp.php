<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/6/6
 * Time: 16:03
 */
ini_set("display_errors","On");
ini_set('memory_limit','2048M');
error_reporting(E_ALL);

$redis = new Redis();
$redis->connect('127.0.0.1');


$config = include_once dirname(__FILE__).'/../config/config.php';

$mysqli = new mysqli($config['database']['host'].":".$config['database']['port'], $config['database']['username'], $config['database']['password'], $config['database']['dbname']);
$redis = new Redis();
$redis->connect('127.0.0.1');

//如果连接错误
if (mysqli_connect_errno()) {
    echo "连接数据库失败：" . mysqli_connect_error();
    $mysqli = null;
    exit;
}

//构造SQL语句
$result = $mysqli->query("select * from type");
while ($row = $result->fetch_assoc()) {

    $typeid = $row['typeid'];
    $redis->del('tid_orderid_'.$typeid);
    echo $typeid."\n";
}

//die;


$result = $mysqli->query("select count(*) from typedata where status=1");
$total = $result->fetch_row()[0];

$times = ceil($total / 1000);

$id = 0;
for ($i = 0; $i < $times; $i++) {
    //构造SQL语句
    $query = "SELECT id, tid, orderid FROM  typedata where id > $id and status = 1 limit 1000";
    //执行SQL语句

    echo memory_get_usage()."\n";
    $result = $mysqli->query($query);
    while ($row = $result->fetch_assoc()) {
        pushOrderId($row['tid'], $row['orderid']);
        $id = $row['id'];
        echo $id."\n";
    }
    $result->free_result();
}

$redis->close();
$mysqli->close();

function pushOrderId($tid, $orderid)
{
    global $redis;

    if (!$redis->lGet('tid_orderid_'.$tid, $orderid)) {
        $redis->rPush('tid_orderid_'.$tid, $orderid);
    }
}
