<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/6/6
 * Time: 16:03
 */


require __DIR__.'/../../libs/PHPExcel/PHPExcel.php';

error_reporting(E_ALL);
ini_set('memory_limit','4096M');
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);

define('EOL',(PHP_SAPI == 'cli') ? PHP_EOL : '<br />');


$data = [
    ['goods_id' => 1322, 'goods_sn' => 'Index(0)->getStyle(\'A\')->getAlignment()->setHorizontal(\PHPExcel_Style_Al', 'goods_name' => 3, 'barcode' => 4, 'goods_type' => 5, 'price' => 6, 'aa' => 7, 'bb' => 8],
    ['goods_id' => 1322, 'goods_sn' => 'Index(0)->getStyle(\'A\')->getAlignment()->setHorizontal(\PHPExcel_Style_Al', 'goods_name' => 3, 'barcode' => 4, 'goods_type' => 5, 'price' => 6, 'aa' => 7, 'bb' => 8],
];
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

$objActSheet->setCellValue('A1', '商品货号');
$objActSheet->setCellValue('B1', '商品名称');
$objActSheet->setCellValue('C1', '商品图');
$objActSheet->setCellValue('D1', '商品图');
$objActSheet->setCellValue('E1', '商品条码');
$objActSheet->setCellValue('F1', '报价(港币)');
$objActSheet->setCellValue('G1', '商品属性');

// 设置个表格宽度
$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(16);
$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(16);
$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(23);
$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(23);
$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
$objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(12);
$objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(12);

// 垂直居中
$objPHPExcel->getActiveSheet()->getStyle('A')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle('B')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle('D')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle('E')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle('F')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
$objPHPExcel->getActiveSheet()->getStyle('G')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);

foreach($data as $k=>$v){
    $k +=1;
    $objActSheet->setCellValue('A'.$k, $v['goods_sn']);
    $objActSheet->setCellValue('B'.$k, $v['goods_name']);

    // 图片生成
    $objDrawing[$k] = new \PHPExcel_Worksheet_Drawing();
    $objDrawing[$k]->setPath('3448_7543_0.png');
    // 设置宽度高度
    $objDrawing[$k]->setHeight(400);//照片高度
    $objDrawing[$k]->setWidth(150); //照片宽度
    /*设置图片要插入的单元格*/
    $objDrawing[$k]->setCoordinates('C'.$k);
    // 图片偏移距离
    $objDrawing[$k]->setOffsetX(6);
    $objDrawing[$k]->setOffsetY(6);
    $objDrawing[$k]->setWorksheet($objPHPExcel->getActiveSheet());
    // 图片生成
    $objDrawing[$k] = new \PHPExcel_Worksheet_Drawing();
    $objDrawing[$k]->setPath('3448_7543_0.png');
    // 设置宽度高度
    $objDrawing[$k]->setHeight(400);//照片高度
    $objDrawing[$k]->setWidth(150); //照片宽度
    /*设置图片要插入的单元格*/
    $objDrawing[$k]->setCoordinates('D'.$k);
    // 图片偏移距离
    $objDrawing[$k]->setOffsetX(6);
    $objDrawing[$k]->setOffsetY(6);
    $objDrawing[$k]->setWorksheet($objPHPExcel->getActiveSheet());

    // 表格内容
    $objActSheet->setCellValue('E'.$k, $v['goods_type']);
    $objActSheet->setCellValue('F'.$k, $v['price']);
    $objActSheet->setCellValue('G'.$k, $v['aa']);

    // 表格高度
    $objActSheet->getRowDimension($k)->setRowHeight(240);

}

$fileName = '报价表';
$date = date("Y-m-d",time());
$fileName .= "_{$date}.xls";
$fileName = iconv("utf-8", "gb2312", $fileName);
//重命名表
// $objPHPExcel->getActiveSheet()->setTitle('test');
//设置活动单指数到第一个表,所以Excel打开这是第一个表
$objPHPExcel->setActiveSheetIndex(0);
$objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
$objWriter->save('aa.xls');

var_dump('ff');
die;


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
            $row['img'] ? 'http://47.99.122.175'.$row['img'] : '',
            $row['img1'] ? 'http://47.99.122.175'.$row['img1'] : '',
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
