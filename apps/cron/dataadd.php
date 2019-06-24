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
    echo("文件不存在：" . $arr['file']);
    die;
}

if (!is_numeric($arr['tid'])) {
    $mysqli->close();
    $redis->close();
    echo ("项目id不存在");
    die;
}

$tid = $arr['tid'];
$file = $arr['file'];


$fp = fopen($file, "r");
$time = time();

//输出文本中所有的行，直到文件结束为止。
$arr = [];
$i = 0;

$insert = "INSERT INTO typedata(tid, orderid, status, creattime, updatetime, mobile, account, password, ip, ip_attribution, imei, device_mode, device_version, imsi, sim_id, `name`, id_card) values ";

$pos = fgetcsv($fp);
$pos = array_map(function($v) {
    return trim($v, "\xEF\xBB\xBF"); // 去除bom头
}, $pos);
$pos = array_unique($pos);
$pos = array_flip($pos);

while (!feof($fp)) {
    $i++;
    $data = fgetcsv($fp);
    if ($data == false) {
        continue;
    }

    $fields = [
        $data[$pos['手机号']],
        $data[$pos['账号']],
        $data[$pos['密码']],
        $data[$pos['IP']],
        $data[$pos['IP地址']],
        $data[$pos['IMEI']],
        $data[$pos['设备型号']],
        $data[$pos['设备系统版本']],
        $data[$pos['IMSI']],
        $data[$pos['SimId']],
        $data[$pos['姓名']],
        $data[$pos['身份证']],
    ];

    $fields = array_map(function($v) use ($mysqli) {
        return $mysqli->escape_string(trim($v));
    }, $fields);

    $arr[] = "($tid, ".getOrderId($redis, $mysqli, $tid).", 1, $time, $time, '".implode("','", $fields)."')";
    if ($i % 1000 == 0) {
        $sql =  $insert.implode(', ', $arr);
        if (!$mysqli->query($sql)) {
            echo $mysqli->errno.' ' .$mysqli->error;
        }
        echo (memory_get_usage() / 1024).'kb'."\n";
        $arr = [];
    }
}

if ($arr) {
    $sql =  $insert.implode(', ', $arr);
    if (!$mysqli->query($sql)) {
        echo $mysqli->errno.' ' .$mysqli->error;
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
