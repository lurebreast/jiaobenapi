<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/6/6
 * Time: 16:03
 */


$redis = new Redis();
$redis->connect('127.0.0.1');

if ($json = $redis->lPop('dataadd_files')) {
    $json = json_decode($json, true);

    if (file_exists($json['file'])) {

        $tid = $json['tid'];
        $file = $json['file'];

        $config = include_once dirname(__FILE__).'/../config/config.php';

        $mysqli = new mysqli($config['database']['host'].":".$config['database']['port'], $config['database']['username'], $config['database']['password'], $config['database']['dbname']);
        //如果连接错误
        if (mysqli_connect_errno()) {
            echo "连接数据库失败：" . mysqli_connect_error();
            $mysqli = null;
            exit;
        }


        //构造SQL语句
        $query = "SELECT * FROM  typedata where tid={$tid} order by id desc limit 1";
        //执行SQL语句

        $result = $mysqli->query($query);
        //遍历结果

        $orderid = $result->fetch_array(MYSQLI_BOTH)['orderid'];
        empty($orderid) && $orderid = 1;

        $fp = fopen($file, "r");
        $time = time();

        //输出文本中所有的行，直到文件结束为止。
        while (!feof($fp)) {
            $orderid++;

            $data = trim(fgets($fp));
            if (!$data) {
                continue;
            }

            $encode = mb_detect_encoding($data, array('ASCII', 'UTF-8', 'GB2312', 'GBK'));
            if ($encode != 'UTF-8') {
                $data = iconv($encode, 'UTF-8', $data);
            }

            $mysqli->query("insert into typedata(data, creattime, status, tid, uid, orderid) value('{$data}', '$time', 1, '$tid', '2', '$orderid')");

            echo $mysqli->insert_id . "\n";
        }
        fclose($fp);
        $mysqli->close();
        unlink($file);
    }
}
