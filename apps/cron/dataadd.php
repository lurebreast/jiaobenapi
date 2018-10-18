<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/6/6
 * Time: 16:03
 */

$arr = json_decode($json, true);

if (!file_exists($arr['file'])) {
    $mysqli->close();
    $redis->close();
    error_log("文件不存在：" . $arr['file']);
    die;
}

if (!is_numeric($arr['tid'])) {
    $mysqli->close();
    $redis->close();
    error_log("项目id不存在");
    die;
}

$tid = $arr['tid'];
$file = $arr['file'];

/*$file = __DIR__.'/aa.txt';
$tid = 42;*/

$fp = fopen($file, "r");
$time = time();

//输出文本中所有的行，直到文件结束为止。
$arr = [];
$i = 0;

while (!feof($fp)) {
    $i++;
    $data = trim(fgets($fp));
    if (!$data) {
        continue;
    }

    $encode = mb_detect_encoding($data, array('ASCII', 'UTF-8', 'GB2312', 'GBK'));
    if ($encode != 'UTF-8') {
        $data = iconv($encode, 'UTF-8', $data);
    }

    $arr[] = "($tid, ".getOrderId($redis, $mysqli, $tid).", 1, '".$mysqli->escape_string($data)."', $time)";
    if ($i % 1000 == 0) {

        $values = implode(', ', $arr);
        $sql = "INSERT INTO typedata(tid, orderid, status, data, creattime) VALUES $values";

        echo $sql;
        if (!$mysqli->query($sql)) {
            error_log($mysqli->errno.' ' .$mysqli->error);
        }
        echo (memory_get_usage() / 1024).'kb'."\n";
        $arr = [];
    }
}

if ($arr) {
    $values = implode(', ', $arr);
    $sql = "INSERT INTO typedata(tid, orderid, status, data, creattime) VALUES $values";

    if (!$mysqli->query($sql)) {
        error_log($mysqli->errno.' ' .$mysqli->error);
    }
    echo (memory_get_usage() / 1024).'kb'."\n";
}

fclose($fp);
$mysqli->close();
$redis->close();
unlink($file);

function getOrderId(Redis $redis, Mysqli $mysqli, $tid)
{
    $key = 'increment_order_id_'.$tid.'_2';
    if (!$redis->exists($key)) {

        //构造SQL语句
        $query = "SELECT * FROM  typedata where tid={$tid} order by id desc limit 1";
        //执行SQL语句

        $result = $mysqli->query($query);
        //遍历结果

        $orderid = $result->fetch_array(MYSQLI_BOTH)['orderid'];
        empty($orderid) && $orderid = 0;

        $redis->set($key, $orderid);
    }

    $orderid = $redis->incr($key);
    $redis->rPush('tid_orderid_'.$tid, $orderid);

    return $orderid;
}
