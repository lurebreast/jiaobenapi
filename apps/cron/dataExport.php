<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/6/6
 * Time: 16:03
 */

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
    die;
}

$json = $redis->lPop('data_export');

//$json = '{"_url":"\/typedata\/outdata","typeid":"47","status":"0","sttime":"","endtime":""}';
if (!$json) {
    $mysqli->close();
    $redis->close();
    echo '没有需要导出的数据'.PHP_EOL;
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
        $objPHPExcel = new \PHPExcel();
        $objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);
        $objActSheet = $objPHPExcel->getActiveSheet();

// 水平居中（位置很重要，建议在最初始位置）
        $objPHPExcel->setActiveSheetIndex(0)->getStyle('A')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->setActiveSheetIndex(0)->getStyle('B1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->setActiveSheetIndex(0)->getStyle('C')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->setActiveSheetIndex(0)->getStyle('D')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->setActiveSheetIndex(0)->getStyle('E')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->setActiveSheetIndex(0)->getStyle('F')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->setActiveSheetIndex(0)->getStyle('G')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->setActiveSheetIndex(0)->getStyle('H')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->setActiveSheetIndex(0)->getStyle('I')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        $objActSheet->setCellValue('A1', '序号');
        $objActSheet->setCellValue('B1', '提取');
        $objActSheet->setCellValue('C1', '项目id');
        $objActSheet->setCellValue('D1', '项目名称');
        $objActSheet->setCellValue('E1', '图片');
        $objActSheet->setCellValue('F1', '图片1');
        $objActSheet->setCellValue('G1', '上传时间');
        $objActSheet->setCellValue('H1', '更新时间');
        $objActSheet->setCellValue('I1', '数据');
// 设置个表格宽度
        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(16);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(16);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(16);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(16);
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(30);
        $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(30);
        $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(30);

// 垂直居中
        $objPHPExcel->getActiveSheet()->getStyle('A')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('B')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('C')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('D')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('E')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('F')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('G')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('H')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('I')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
    }

    $sql = "select * from typedata where id<".$maxId." and $where";
    if (!empty($arr['data_unique'])) {
        $sql .= " group by data";
    }

    $sql .= " order by id desc limit $limit";

    echo $sql."\n";
    $k = 0;
    $res = $mysqli->query($sql);
    while($row = $res->fetch_assoc()) {
        $k +=2;
        $objActSheet->setCellValue('A'.$k, $row['orderid']);
        $objActSheet->setCellValue('B'.$k, $row['status'] == 1 ? '未提取' : '已提取');
        $objActSheet->setCellValue('C'.$k, $row['tid']);
        $objActSheet->setCellValue('D'.$k, $typename);

        // 图片生成
        $image_dir = __DIR__ . '/../../public';
        if ($row['img'] && file_exists($image_dir.$row['img'])) {
            $objDrawing[$k] = new \PHPExcel_Worksheet_Drawing();
            $objDrawing[$k]->setPath($image_dir.$row['img']);
            // 设置宽度高度
            $objDrawing[$k]->setHeight(400);//照片高度
            $objDrawing[$k]->setWidth(200); //照片宽度
            /*设置图片要插入的单元格*/
            $objDrawing[$k]->setCoordinates('E'.$k);
            // 图片偏移距离
            $objDrawing[$k]->setOffsetX(6);
            $objDrawing[$k]->setOffsetY(6);
            $objDrawing[$k]->setWorksheet($objPHPExcel->getActiveSheet());
        } else {
            $objActSheet->setCellValue('E'.$k, '');
        }
        if ($row['img1'] && file_exists($image_dir.$row['img1'])) {
            $objDrawing[$k] = new \PHPExcel_Worksheet_Drawing();
            $objDrawing[$k]->setPath($image_dir.$row['img1']);
            // 设置宽度高度
            $objDrawing[$k]->setHeight(400);//照片高度
            $objDrawing[$k]->setWidth(200); //照片宽度
            /*设置图片要插入的单元格*/
            $objDrawing[$k]->setCoordinates('F'.$k);
            // 图片偏移距离
            $objDrawing[$k]->setOffsetX(6);
            $objDrawing[$k]->setOffsetY(6);
            $objDrawing[$k]->setWorksheet($objPHPExcel->getActiveSheet());
        } else {
            $objActSheet->setCellValue('F'.$k, '');
        }

        $objActSheet->setCellValue('G'.$k, date('Y-m-d H:i:s', $row['creattime']));
        $objActSheet->setCellValue('H'.$k, $row['updatetime'] ? date('Y-m-d H:i:s', $row['updatetime']) : '');
        $objActSheet->setCellValue('I'.$k, $row['data']);

        // 表格高度
        $objActSheet->getRowDimension($k)->setRowHeight(300);

        $maxId = $row['id'];
    }

    $fileInc++;
    $file = 'data_'.$arr['typeid'].'_'.$fileInc.'.xls';
    $dir = __DIR__ . '/../../public/files/';
    $file_arr[] = $file;

    $objPHPExcel->setActiveSheetIndex(0);
    $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
    $objWriter->save($dir.$file);

    $redis->hset('data_export_'.$arr['typeid'], 'percent', round(($i + 1) / $times, 2) * 100);

    usleep(200000);
}

$redis->hset('data_export_'.$arr['typeid'], 'lock', 0);
$redis->hset('data_export_'.$arr['typeid'], 'files', json_encode($file_arr));
$redis->close();
$mysqli->close();
