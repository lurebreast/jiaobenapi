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
    die;
}

$json = $redis->lPop('data_export');

//$json = '{"_url":"\/typedata\/outdata","typeid":"3448","status":"0","sttime":"","endtime":"","image_file":"1"}';
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
$limitMerge = $arr['image_file'] ? 30 : 300; // 导出图片3万一个文件 图片地址30万一个文件 = $limit x $limitMerge

$times = ceil($total / $limit);


//$limit = 1;
//$times = 2;

$redis->hset('data_export_'.$arr['typeid'], 'complete', $times);

$sql = "select id from typedata where $where";
if (!empty($arr['data_unique'])) {
    $sql .= " group by data";
};

$sql .= " order by id desc";

$res = $mysqli->query($sql);

$maxId = (int)$res->fetch_assoc()['id'] + 1; // 包含当前行

$id = 0;

$fileInc = 1;
$file_arr = [];
for ($i = 0; $i < $times; $i++) {
    if ($i % $limitMerge === 0) {
        var_dump(__LINE__);
        $k = 1;

        $objPHPExcel = new \PHPExcel();
        $objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);
        $objActSheet = $objPHPExcel->getActiveSheet();

        $cacheMethod = \PHPExcel_CachedObjectStorageFactory::cache_to_discISAM;
        if (!\PHPExcel_Settings::setCacheStorageMethod($cacheMethod)) {
            die($cacheMethod . " 缓存方法不可用" . EOL);
        }

        $objActSheet->setCellValue('A1', '序号');
        $objActSheet->setCellValue('B1', '提取');
        $objActSheet->setCellValue('C1', '项目id');
        $objActSheet->setCellValue('D1', '项目名称');
        $objActSheet->setCellValue('E1', '更新时间');
        $objActSheet->setCellValue('F1', '上传时间');
        $objActSheet->setCellValue('G1', '数据');
        $objActSheet->setCellValue('H1', '图片');
        $objActSheet->setCellValue('I1', '图片1');
        // 设置个表格宽度
        $objActSheet->getColumnDimension('E')->setWidth(19);
        $objActSheet->getColumnDimension('F')->setWidth(19);
        $objActSheet->getColumnDimension('G')->setWidth(50);

        if ($arr['image_file']) {
            $objActSheet->getColumnDimension('H')->setWidth(6);
            $objActSheet->getColumnDimension('I')->setWidth(6);
        } else {
            $objActSheet->getColumnDimension('H')->setWidth(44);
            $objActSheet->getColumnDimension('I')->setWidth(44);
        }

        $objActSheet->getStyle('H')->getAlignment()->setWrapText(true);
        $objActSheet->getStyle('G')->getAlignment()->setWrapText(true);
        $objActSheet->getStyle('I')->getAlignment()->setWrapText(true);
    }

    $sql = "select * from typedata where id<".$maxId." and $where";
    if (!empty($arr['data_unique'])) {
        $sql .= " group by data";
    }

    $sql .= " order by id desc limit $limit";

    echo $sql."\n";
    $res = $mysqli->query($sql);
    while($row = $res->fetch_assoc()) {
        $k +=1;

        $objActSheet->setCellValue('A'.$k, $row['orderid']);
        $objActSheet->setCellValue('B'.$k, $row['status'] == 1 ? '未提取' : '已提取');
        $objActSheet->setCellValue('C'.$k, $row['tid']);
        $objActSheet->setCellValue('D'.$k, $typename);
        $objActSheet->setCellValue('E'.$k, date('Y-m-d H:i:s', $row['creattime']));
        $objActSheet->setCellValue('F'.$k, $row['updatetime'] ? date('Y-m-d H:i:s', $row['updatetime']) : '');
        $objActSheet->setCellValue('G'.$k, $row['data']);

        //        $row['img'] = $row['img1'] = '';
        if ($arr['image_file']) {
            // 图片生成

            $image_dir = __DIR__ . '/../../public';
            if ($row['img'] && file_exists($image_dir.$row['img'])) {
                $objDrawing[$k] = new \PHPExcel_Worksheet_Drawing();
                $objDrawing[$k]->setPath($image_dir.$row['img']);
                // 设置宽度高度
                $objDrawing[$k]->setWidth(30); //照片宽度
                $objDrawing[$k]->setResizeProportional(true);
                /*设置图片要插入的单元格*/
                $objDrawing[$k]->setCoordinates('H'.$k);
                // 图片偏移距离
                $objDrawing[$k]->setOffsetX(6);
                $objDrawing[$k]->setOffsetY(6);
                $objDrawing[$k]->setWorksheet($objPHPExcel->getActiveSheet());
            } else {
                $objActSheet->setCellValue('H'.$k, '');
            }
            if ($row['img1'] && file_exists($image_dir.$row['img1'])) {
                $objDrawing[$k] = new \PHPExcel_Worksheet_Drawing();
                $objDrawing[$k]->setPath($image_dir.$row['img1']);
                // 设置宽度高度
                $objDrawing[$k]->setWidth(30); //照片宽度
                $objDrawing[$k]->setResizeProportional(true);
                /*设置图片要插入的单元格*/
                $objDrawing[$k]->setCoordinates('I'.$k);
                // 图片偏移距离
                $objDrawing[$k]->setOffsetX(6);
                $objDrawing[$k]->setOffsetY(6);
                $objDrawing[$k]->setWorksheet($objPHPExcel->getActiveSheet());
            } else {
                $objActSheet->setCellValue('I'.$k, '');
            }
        } else {
            $objActSheet->setCellValue('H'.$k, ($row['img'] ? 'http://47.99.122.175'.$row['img'] : ''));
            $objActSheet->setCellValue('I'.$k, ($row['img1'] ? 'http://47.99.122.175'.$row['img1'] : ''));
        }

        // 表格高度
        if ($arr['image_file']) {
            $objActSheet->getRowDimension($k)->setRowHeight(50);
        }

        $maxId = $row['id'];

        echo floor((memory_get_peak_usage())/1024/1024)."MB".PHP_EOL;
    }

    $next = $i + 1;
    if ($times == 1 || $next % $limitMerge === 0 || $next == $times) { // 只有一次 正常多次 最后一次

        $file = 'data_'.$arr['typeid'].'_'.$fileInc.'.xlsx';
        $dir = __DIR__ . '/../../public/files/';
        $file_arr[] = $file;

        $objPHPExcel->setActiveSheetIndex(0);
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save($dir.$file);

        $fileInc++;
    }

    $redis->hset('data_export_'.$arr['typeid'], 'percent', round(($i + 1) / $times, 2) * 100);

    usleep(200000);
}

//var_dump($file_arr);
echo " 导出完成".json_encode($file_arr).PHP_EOL;

$redis->hset('data_export_'.$arr['typeid'], 'lock', 0);
$redis->hset('data_export_'.$arr['typeid'], 'files', json_encode($file_arr));
$redis->close();
$mysqli->close();
