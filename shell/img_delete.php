<?php
/**
 * Created by PhpStorm.
 * User: Henry
 * Date: 2018/6/30
 * Time: 9:56
 */
ini_set("error_reporting", E_ALL);
ini_set("display_errors", "On");

$config = include_once dirname(__FILE__).'/../apps/config/config.php';

$mysqli = new mysqli($config['database']['host'].":".$config['database']['port'], $config['database']['username'], $config['database']['password'], $config['database']['dbname']);
//如果连接错误
if (mysqli_connect_errno()) {
    echo "连接数据库失败：" . mysqli_connect_error();
    $mysqli = null;
    exit;
}

$dir = "/home/wwwroot/default/public/images";
$dir_bak = "/tmp/images_bak";

!is_dir($dir_bak) && mkdir($dir_bak);
my_dir();

function my_dir() {
    global $mysqli, $dir, $dir_bak;

    if($handle = opendir($dir)) { //注意这里要加一个@，不然会有warning错误提示：）
        while(($file = readdir($handle)) !== false) {
            if($file != ".." && $file != ".") { //排除根目录；
                if(is_dir($dir."/".$file)) { //如果是子文件夹，就进行递归
                    $files[$file] = my_dir($dir."/".$file);
                } else { //不然就将文件的名字存入数组；

                    if (strpos($file, '.png')) {
                        $img = '/images/'.$file;
                        //$img = '/images/f45aaaada419aeb512c8327bd470dd27.png';
                        $result = $mysqli->query("select id from typedata where img='{$img}'");
                        if (!$result->fetch_assoc()) {
                            rename($dir.'/'.$file, $dir_bak.'/'.$file);
                            echo $img."\n";
                        }
                        $result->free();
                        usleep(80000);
                    }
                }
            }
        }
        closedir($handle);
    }
}
