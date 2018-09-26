<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/6/6
 * Time: 16:03
 */

ini_set('memory_limit','4096M');
ini_set('display_errors','On');

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
    die;
}

$json = $redis->lPop('data_export');

//$json = '{"_url":"\/typedata\/outdata","typeid":"43","status":"0","sttime":"","endtime":""}';
if (!$json) {
    $mysqli->close();
    $redis->close();
    die;
}

$arr = json_decode($json, true);

$res = $mysqli->query("select typename from type where typeid='{$arr['typeid']}'");
$typename = $res->fetch_assoc()['typename'];

$where = "tid={$arr['typeid']}";
$arr['status'] && $where .= " and status={$arr['status']}";
$arr['sttime'] && $where .= " and creattime>=".strtotime($arr['sttime']);
$arr['endtime'] && $where .= " and creattime<".strtotime($arr['endtime']);

if (!empty($arr['data_unique'])) {
    $sql = "select count(DISTINCT(data)) as total from typedata where $where";
} else {
    $sql = "select count(*) as total from typedata where $where";
}

$res = $mysqli->query($sql);
$total = $res->fetch_assoc()['total'];

$limit = 1000;
$limitMerge = 300;

$times = ceil($total / $limit);
$fileMax = ceil($total / 20);

$redis->hset('data_export_'.$arr['typeid'], 'complete', $times);

$sql = "select id from typedata where $where";
if (!empty($arr['data_unique'])) {
    $sql .= " group by data";
};

$sql .= " order by id desc";

$res = $mysqli->query($sql);

$maxId = (int)$res->fetch_assoc()['id'] + 1; // 包含当前行

$id = 0;

$fileInc = 0;
$file_arr = [];
for ($i = 0; $i < $times; $i++) {
    if ($i % $limitMerge === 0) {
        $fileInc++;
        $file = 'data_'.$arr['typeid'].'_'.$fileInc.'.csv';
        $dir = __DIR__ . '/../../public/files/';
        $file_arr[] = $file;

        $fp = fopen($dir.$file, 'w');
        fwrite($fp,chr(0xEF).chr(0xBB).chr(0xBF)); //Windows下使用BOM来标记文本文件的编码方式
        fputcsv($fp, [
            '序号',
            '提取',
            '项目id',
            '项目名称',
            '图片',
            '图片1',
            '上传时间',
            '更新时间',
            '数据',
        ]);
    }

    $sql = "select * from typedata where id<".$maxId." and $where";
    if (!empty($arr['data_unique'])) {
        $sql .= " group by data";
    }

    $sql .= " order by id desc limit $limit";

    echo $sql."\n";
    $res = $mysqli->query($sql);
    while($row = $res->fetch_assoc()) {
        fputcsv($fp, [
            $row['orderid'],
            $row['status'] == 1 ? '未提取' : '已提取',
            $row['tid'],
            $typename, //
            $row['img'] ? 'http://47.99.122.175/'.$row['img'] : '',
            $row['img1'] ? 'http://47.99.122.175/'.$row['img1'] : '',
            date('Y-m-d H:i:s', $row['creattime']),
            $row['updatetime'] ? date('Y-m-d H:i:s', $row['updatetime']) : '',
            $row['data'],
        ]);

        $maxId = $row['id'];
    }

    $redis->hset('data_export_'.$arr['typeid'], 'percent', round(($i + 1) / $times, 2) * 100);

    usleep(200000);
//    sleep(1);
}

$redis->hset('data_export_'.$arr['typeid'], 'lock', 0);
$redis->hset('data_export_'.$arr['typeid'], 'files', json_encode($file_arr));
$redis->close();
$mysqli->close();
