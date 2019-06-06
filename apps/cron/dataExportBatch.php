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
$objActSheet->setCellValue('B1', '项目id');
$objActSheet->setCellValue('C1', '项目名称');
$objActSheet->setCellValue('D1', '上传时间');
$objActSheet->setCellValue('E1', '更新时间');
$objActSheet->setCellValue('F1', '图片');
$objActSheet->setCellValue('G1', '图片1');
$objActSheet->setCellValue('H1', '手机号');
$objActSheet->setCellValue('I1', '账号');
$objActSheet->setCellValue('J1', '密码');
$objActSheet->setCellValue('K1', 'IP');
$objActSheet->setCellValue('L1', 'IP地址');
$objActSheet->setCellValue('M1', 'IMEI');
$objActSheet->setCellValue('N1', '姓名');
$objActSheet->setCellValue('O1', '身份证');
$objActSheet->setCellValue('P1', '设备型号');
$objActSheet->setCellValue('Q1', '设备系统版本');
$objActSheet->setCellValue('R1', 'IMSI');
$objActSheet->setCellValue('S1', 'SIM卡ID');
$objActSheet->setCellValue('T1', '提取');

// 设置个表格宽度
$objActSheet->getColumnDimension('D')->setWidth(19);
$objActSheet->getColumnDimension('E')->setWidth(19);

if ($arr['image_file']) {
    $objActSheet->getColumnDimension('F')->setWidth(6);
    $objActSheet->getColumnDimension('G')->setWidth(6);
} else {
    $objActSheet->getColumnDimension('F')->setWidth(44);
    $objActSheet->getColumnDimension('G')->setWidth(44);
}

$objActSheet->getStyle('F')->getAlignment()->setWrapText(true);
$objActSheet->getStyle('G')->getAlignment()->setWrapText(true);
$objActSheet->getColumnDimension("H")->setAutoSize(true);

$k = 2;
for ($i = 0; $i < $limitMerge; $i++) {
    $sql = "select * from typedata where id<" . $maxId . " and $where order by id desc limit $limit";

    echo $sql . "\n";
    $res = $mysqli->query($sql);
    while ($row = $res->fetch_assoc()) {

        $objActSheet->setCellValue('A' . $k, $row['orderid']);
        $objActSheet->setCellValue('B' . $k, $row['tid']);
        $objActSheet->setCellValue('C' . $k, $typename);
        $objActSheet->setCellValue('D' . $k, date('Y-m-d H:i:s', $row['creattime']));
        $objActSheet->setCellValue('E' . $k, $row['updatetime'] ? date('Y-m-d H:i:s', $row['updatetime']) : '');

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
                $objDrawing[$k]->setCoordinates('F' . $k);
                // 图片偏移距离
                $objDrawing[$k]->setOffsetX(6);
                $objDrawing[$k]->setOffsetY(6);
                $objDrawing[$k]->setWorksheet($objPHPExcel->getActiveSheet());
            } else {
                $objActSheet->setCellValue('F' . $k, '');
            }
            if ($row['img1'] && file_exists($image_dir . $row['img1'])) {
                $objDrawing[$k] = new \PHPExcel_Worksheet_Drawing();
                $objDrawing[$k]->setPath($image_dir . $row['img1']);
                // 设置宽度高度
                $objDrawing[$k]->setWidth(30); //照片宽度
                $objDrawing[$k]->setResizeProportional(true);
                /*设置图片要插入的单元格*/
                $objDrawing[$k]->setCoordinates('G' . $k);
                // 图片偏移距离
                $objDrawing[$k]->setOffsetX(6);
                $objDrawing[$k]->setOffsetY(6);
                $objDrawing[$k]->setWorksheet($objPHPExcel->getActiveSheet());
            } else {
                $objActSheet->setCellValue('G' . $k, '');
            }
        } else {
            $objActSheet->setCellValue('F' . $k, ($row['img'] ? 'http://47.99.122.175' . $row['img'] : ''));
            $objActSheet->setCellValue('G' . $k, ($row['img1'] ? 'http://47.99.122.175' . $row['img1'] : ''));
        }

        $objActSheet->setCellValueExplicit('H' . $k, $row['mobile'], PHPExcel_Cell_DataType::TYPE_STRING);
        $objActSheet->setCellValue('I' . $k, $row['account']);
        $objActSheet->setCellValue('J' . $k, $row['password']);
        $objActSheet->setCellValueExplicit('K' . $k, $row['ip'], PHPExcel_Cell_DataType::TYPE_STRING);
        $objActSheet->setCellValue('L' . $k, $row['ip_attribution']);
        $objActSheet->setCellValueExplicit('M' . $k, $row['imei'], PHPExcel_Cell_DataType::TYPE_STRING);
        $objActSheet->setCellValue('N' . $k, $row['name']);
        $objActSheet->setCellValueExplicit('O' . $k, $row['id_card'], PHPExcel_Cell_DataType::TYPE_STRING);
        $objActSheet->setCellValue('P' . $k, $row['device_mode']);
        $objActSheet->setCellValue('Q' . $k, $row['device_version']);
        $objActSheet->setCellValueExplicit('R' . $k, $row['imsi'], PHPExcel_Cell_DataType::TYPE_STRING);
        $objActSheet->setCellValueExplicit('S' . $k, $row['sim_id'], PHPExcel_Cell_DataType::TYPE_STRING);
        $objActSheet->setCellValue('T' . $k, $row['status'] == 1 ? '未提取' : '已提取');

        // 表格高度
        if ($arr['image_file']) {
            $objActSheet->getRowDimension($k)->setRowHeight(50);
        }

        $k++;

        $maxId = $row['id'];
        $redis->hset('data_export_'.$arr['typeid'], 'maxId', $maxId);
    }
}

$percent = round($fileInc / $fileNum, 2) * 100;
$redis->hset('data_export_'.$arr['typeid'], 'percent', $percent);
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
