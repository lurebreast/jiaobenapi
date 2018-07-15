<?php
/**
 * Created by PhpStorm.
 * User: Henry
 * Date: 2018/6/30
 * Time: 9:56
 */
ini_set("error_reporting", E_ALL);
print_r(my_dir("/tmp/img"));


function my_dir($dir) {
    if($handle = opendir($dir)) { //注意这里要加一个@，不然会有warning错误提示：）
        while(($file = readdir($handle)) !== false) {
            if($file != ".." && $file != ".") { //排除根目录；
                if(is_dir($dir."/".$file)) { //如果是子文件夹，就进行递归
                    $files[$file] = my_dir($dir."/".$file);
                } else { //不然就将文件的名字存入数组；

                    if (strpos($file, '.png')) {
                        image_png_size_add($dir."/".$file, $dir."/".$file);
                        echo $file."\n";
                    }
                }

            }
        }
        closedir($handle);
    }
}


/**
 * desription 压缩图片
 * @param sting $imgsrc 图片路径
 * @param string $imgdst 压缩后保存路径
 */
function image_png_size_add($imgsrc,$imgdst){
    list($width,$height,$type)=getimagesize($imgsrc);
    $new_width = $width*0.6;
    $new_height =$height*0.6;
    switch($type){
        case 1:
            $giftype=check_gifcartoon($imgsrc);
            if($giftype){
                $image_wp=imagecreatetruecolor($new_width, $new_height);
                $image = imagecreatefromgif($imgsrc);
                imagecopyresampled($image_wp, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                imagejpeg($image_wp, $imgdst,75);
                imagedestroy($image_wp);
            }
            break;
        case 2:
            $image_wp=imagecreatetruecolor($new_width, $new_height);
            $image = imagecreatefromjpeg($imgsrc);
            imagecopyresampled($image_wp, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
            imagejpeg($image_wp, $imgdst,75);
            imagedestroy($image_wp);
            break;
        case 3:
            $image_wp=imagecreatetruecolor($new_width, $new_height);
            $image = imagecreatefrompng($imgsrc);
            imagecopyresampled($image_wp, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
            imagejpeg($image_wp, $imgdst,75);
            imagedestroy($image_wp);
            break;
    }
}
/**
 * desription 判断是否gif动画
 * @param sting $image_file图片路径
 * @return boolean t 是 f 否
 */
function check_gifcartoon($image_file){
    $fp = fopen($image_file,'rb');
    $image_head = fread($fp,1024);
    fclose($fp);
    return preg_match("/".chr(0x21).chr(0xff).chr(0x0b).'NETSCAPE2.0'."/",$image_head)?false:true;
}
