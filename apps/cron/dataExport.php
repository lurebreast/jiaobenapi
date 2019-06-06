<?php

$arr = json_decode($json, true);

$where = "tid={$arr['typeid']}";
$arr['status'] && $where .= " and status={$arr['status']}";
$arr['sttime'] && $where .= " and creattime>=".strtotime($arr['sttime']);
$arr['endtime'] && $where .= " and creattime<".strtotime($arr['endtime']);

// 总数
$sql = "select count(*) as total from typedata where $where";
$res = $mysqli->query($sql);
$total = $res->fetch_assoc()['total'];

// 最大id
$sql = "select id from typedata where $where order by id desc limit 1";
$res = $mysqli->query($sql);
$maxId = (int)$res->fetch_assoc()['id'] + 1; // 包含当前行

$limit = 1000;
$limitMerge = $arr['image_file'] ? 30 : 300; // 导出图片3万一个文件 图片地址30万一个文件 = $limit x $limitMerge

$times = ceil($total / $limit);

$fileNum = ceil($total / ($limit * $limitMerge));

$fileInc = 1;

$arr['total'] = $total;
$arr['firstMaxId'] = $maxId;
$arr['limit'] = $limit;
$arr['limitMerge'] = $limitMerge;
$arr['times'] = $times;
$arr['fileNum'] = $fileNum;
$arr['fileInc'] = $fileInc;
for ($i = $fileInc; $i <= $fileNum; $i++) {
    $arr['fileInc'] = $i;

    exec("php ".__DIR__."/dataExportBatch.php '". json_encode($arr)."'");
    echo "项目id:".$arr['typeid']." ".$arr['fileInc']." 总数{$fileNum}导出完成"."\n";
}
