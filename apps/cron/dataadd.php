<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/6/6
 * Time: 16:03
 */

ini_set('memory_limit','4096M');

$redis = new Redis();
$redis->connect('127.0.0.1');

if ($json = $redis->lPop('dataadd_files')) {
    $json = json_decode($json, true);

    if (file_exists($json['file'])) {

        $tid = $json['tid'];
        $file = $json['file'];

        $config = include_once __DIR__.'/../config/config.php';

        $host = $config['database']['host'].":".$config['database']['port'];
        $user = $config['database']['username'];
        $pwd = $config['database']['password'];
        $db = $config['database']['dbname'];

        $mysqli = new mysqli($host, $user, $pwd, $db);
        //如果连接错误
        if (mysqli_connect_errno()) {
            echo "连接数据库失败：" . mysqli_connect_error();
            $mysqli = null;
            exit;
        }

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

            $arr[] = "($tid, ".getOrderId($tid).", 1, '".$mysqli->escape_string($data)."', $time)1";
            if ($i % 1000 == 0) {

                $values = implode(', ', $arr);
                $sql = "INSERT INTO typedata(tid, orderid, status, data, creattime) VALUES $values";

                if (!$mysqli->query($sql)) {
                    error_log($mysqli->errno.' ' .$mysqli->error);
                }
                echo (memory_get_usage() / 1024).'kb'."\n";
                $arr = [];
            }
        }

        fclose($fp);
        $mysqli->close();
        unlink($file);
    }
}

function getOrderId($tid)
{
    global $mysqli;

    $redis = new Redis();
    $redis->connect('127.0.0.1');

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
