<?php

require __DIR__.'/init.php';
require __DIR__.'/../../libs/PHPExcel/PHPExcel.php';

$arr = json_decode($argv[1], true);

$where = "tid={$arr['typeid']}";
$arr['status'] && $where .= " and status={$arr['status']}";
$arr['sttime'] && $where .= " and creattime>=".strtotime($arr['sttime']);
$arr['endtime'] && $where .= " and creattime<".strtotime($arr['endtime']);

$total = $arr['total'];
$limit = $arr['limit'];
$limitMerge = $arr['limitMerge'];
$fileNum = $arr['fileNum'];
$fileInc = $arr['fileInc'];
$maxId = $redis->hGet('data_export_'.$arr['typeid'], 'maxId');
if (!$maxId) {
    $maxId = $arr['firstMaxId'];
}

$res = $mysqli->query("select typename from type where typeid='{$arr['typeid']}'");
$typename = $res->fetch_assoc()['typename'];


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

$k = 2;
for ($i = 0; $i < $limitMerge; $i++) {
    $sql = "select * from typedata where id<" . $maxId . " and $where";
    if (!empty($arr['data_unique'])) {
        $sql .= " group by data";
    }
    $sql .= " order by id desc limit $limit";

    echo $sql . "\n";
    $res = $mysqli->query($sql);
    while ($row = $res->fetch_assoc()) {

        $objActSheet->setCellValue('A' . $k, $row['orderid']);
        $objActSheet->setCellValue('B' . $k, $row['status'] == 1 ? '未提取' : '已提取');
        $objActSheet->setCellValue('C' . $k, $row['tid']);
        $objActSheet->setCellValue('D' . $k, $typename);
        $objActSheet->setCellValue('E' . $k, date('Y-m-d H:i:s', $row['creattime']));
        $objActSheet->setCellValue('F' . $k, $row['updatetime'] ? date('Y-m-d H:i:s', $row['updatetime']) : '');
        $objActSheet->setCellValue('G' . $k, $row['data']);

        if ($arr['image_file']) {
            // 图片生成
            $image_dir = __DIR__ . '/../../public';
            if ($row['img'] && file_exists($image_dir . $row['img'])) {
                $objDrawing[$k] = new \PHPExcel_Worksheet_Drawing();
                $objDrawing[$k]->setPath($image_dir . $row['img']);
                // 设置宽度高度
                $objDrawing[$k]->setWidth(30); //照片宽度
                $objDrawing[$k]->setResizeProportional(true);
                /*设置图片要插入的单元格*/
                $objDrawing[$k]->setCoordinates('H' . $k);
                // 图片偏移距离
                $objDrawing[$k]->setOffsetX(6);
                $objDrawing[$k]->setOffsetY(6);
                $objDrawing[$k]->setWorksheet($objPHPExcel->getActiveSheet());
            } else {
                $objActSheet->setCellValue('H' . $k, '');
            }
            if ($row['img1'] && file_exists($image_dir . $row['img1'])) {
                $objDrawing[$k] = new \PHPExcel_Worksheet_Drawing();
                $objDrawing[$k]->setPath($image_dir . $row['img1']);
                // 设置宽度高度
                $objDrawing[$k]->setWidth(30); //照片宽度
                $objDrawing[$k]->setResizeProportional(true);
                /*设置图片要插入的单元格*/
                $objDrawing[$k]->setCoordinates('I' . $k);
                // 图片偏移距离
                $objDrawing[$k]->setOffsetX(6);
                $objDrawing[$k]->setOffsetY(6);
                $objDrawing[$k]->setWorksheet($objPHPExcel->getActiveSheet());
            } else {
                $objActSheet->setCellValue('I' . $k, '');
            }
        } else {
            $objActSheet->setCellValue('H' . $k, ($row['img'] ? 'http://47.99.122.175' . $row['img'] : ''));
            $objActSheet->setCellValue('I' . $k, ($row['img1'] ? 'http://47.99.122.175' . $row['img1'] : ''));
        }

        // 表格高度
        if ($arr['image_file']) {
            $objActSheet->getRowDimension($k)->setRowHeight(50);
        }

        $k++;

        $maxId = $row['id'];
        $redis->hset('data_export_'.$arr['typeid'], 'maxId', $maxId);

        echo floor((memory_get_peak_usage()) / 1024 / 1024) . "MB" . PHP_EOL;
    }

    $redis->hset('data_export_'.$arr['typeid'], 'percent', round(($fileInc * $i) / $arr['times'], 2) * 100);
}

$file = 'data_'.$arr['typeid'].'_'.$fileInc.'.xlsx';
$dir = __DIR__ . '/../../public/files/';

$objPHPExcel->setActiveSheetIndex(0);
$objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
$objWriter->save($dir.$file);

echo $file."导出完成".PHP_EOL;

$file_arr = [];
if ($fileInc == 1) {
    $redis->hDel('data_export_'.$arr['typeid'], 'files');
}
if ($file_old = $redis->hGet('data_export_'.$arr['typeid'], 'files')) {
    $file_arr = json_decode($file_old, true);
}
$file_arr[] = $file;
$redis->hset('data_export_'.$arr['typeid'], 'files', json_encode($file_arr));

if ($fileInc == $fileNum) {
    $redis->hset('data_export_'.$arr['typeid'], 'lock', 0);
    $redis->hset('data_export_'.$arr['typeid'], 'percent', 100);
    $redis->hDel('data_export_'.$arr['typeid'], 'maxId');
    echo " 导出完成".json_encode($file_arr).PHP_EOL;
}
$redis->close();
$mysqli->close();
