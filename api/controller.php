<?php
/**
 * Created by PhpStorm.
 * User: Henry
 * Date: 2018/7/3
 * Time: 20:31
 */

/**
 * @param $request
 * @return string
 */
function action_set($request)
{
    $tid =  isset($request->get['typeid']) ? $request->get['typeid'] : 0;
    $orderid =  isset($request->get['typedataid']) ? $request->get['typedataid'] : 0;
    $only =  isset($request->get['only']) ? $request->get['only'] : null;
    $data =  isset($request->get['data']) ? $request->get['data'] : '';
    $imgBase64 =  isset($request->post['img']) ? $request->post['img'] : '';
    $imgBase641 =  isset($request->post['img1']) ? $request->post['img1'] : '';

    if (!$tid) {
        return error('项目id或者数据为空');
    }

    $mysqli = getMysqli();

    $type = $mysqli->query("select id from type where tid='{$tid}'");
    if ($type && !$type->fetch_assoc()) {
        $type->free();
        $mysqli->close();
        return error('没有此项目');
    }

    if ($only == 1){
        $typedata = $mysqli->query("select id from typedata where tid='{$tid}' and data='".$mysqli->escape_string($data)."' limit 1");
        if ($typedata && $typedata->fetch_assoc()) {
            $typedata->free();
            $mysqli->close();
            return error('该数据重复');
        }
    }

    $encode = mb_detect_encoding($data, array('ASCII','UTF-8','GB2312','GBK'));
    if ($encode != 'UTF-8'){
        $data = iconv($encode,'UTF-8',$data);
    }

    !$orderid && $orderid = getOrderId($tid);

    $img = uploadImg($imgBase64, $tid, $orderid, 0);
    $img1 = uploadImg($imgBase641, $tid, $orderid, 1);

    $res = $mysqli->query("select id from typedata where tid='{$tid}' and orderid=$orderid limit 1");
    $type_id = $res ? $res->fetch_assoc()['id'] : 0;
    $timestamp = time();

    if ($type_id) {
        $update = [];
        $data && $update[] = "data='".$mysqli->escape_string($data)."'";
        $img && $update[] = "img='$img'";
        $img1 && $update[] = "img1='$img1'";
        $update[] = "updatetime={$timestamp}";

        $sql = "update typedata set ".implode(',', $update)." where id={$type_id}";
    } else {
        $sql = "insert into typedata(tid, orderid, data, img, img1, status, creattime, updatetime) value($tid, $orderid, '".$mysqli->escape_string($data)."', '$img', '$img1', 1, $timestamp, $timestamp)";
    }

    if ($mysqli->query($sql)) {
        $mysqli->close();
        return success("$tid|$orderid");
    } else {
        return error("数据保存失败");
    }
}

function uploadImg($base64_img, $tid, $orderid, $img_id)
{
    $img = '';
    if ($base64_img) {
        $base64_img = str_replace(' ', '+', $base64_img);
        $base64_img = str_replace('data:image/png;base64,', '', $base64_img);

        if ($imgBin = base64_decode($base64_img)) {
            $basePath = '/home/wwwroot/default/public';
            $img = "/images/{$tid}_{$orderid}_{$img_id}.png";
            file_put_contents($basePath.$img, $imgBin);
            unset($imgBin);
        } else {
            //file_put_contents('/tmp/img_upload.txt', date('Y-m-d H:i:s').' '.$tid.' '.$this->request->get('img')."\n", FILE_APPEND);
        }
    }

    return $img;
}

/**
 * @param $request
 * @return string
 */
function action_getCount($request)
{
    $typeid = isset($request->get['typeid']) ? $request->get['typeid'] : 0;
    $status = isset($request->get['status']) ? $request->get['status'] : null;

    if (!$typeid) {
        return error('项目id为空');
    }

    $redis = getRedis();
    $key = 'typeid_count_'.$typeid.'_'.$status;

    if ($redis->setnx($key.'_lock1', true)) {
        $redis->expire($key.'_lock1', 10);

        $countnum = 0;
        $mysqli = getMysqli();
        if ($mysqli) {
            $where_status = $status ? " and status = '{$status}'" : '';
            $sql = "select count(*) num from typedata where tid='{$typeid}' $where_status limit 1;";
            $res = $mysqli->query($sql);

            if ($res) {
                $row = $res->fetch_assoc();
                $countnum = $row['num'];
                $res->free();
            }

            $mysqli->close();
        }

        $redis->setex($key, 86400, $countnum);
    } else {
        $countnum = $redis->get($key);
    }
    $redis->close();

    return success($countnum);
}

function getMysqli()
{
    global  $config;
    return new mysqli($config['database']['host'].':'.$config['database']['port'], $config['database']['username'], $config['database']['password'], $config['database']['dbname']);
}

function getRedis()
{
    global $config;
    $redis = new Redis();
    $redis->connect($config['redis']['host'], $config['redis']['port']);

    return $redis;
}

function getOrderId($tid)
{
    $mysqli = getMysqli();
    $redis = getRedis();

    $key = 'increment_order_id_'.$tid.'_2';
    if (!$redis->exists($key)) {

        $result = $mysqli->query("SELECT * FROM  typedata where tid={$tid} order by id desc limit 1");
        $orderid = $result->fetch_array(MYSQLI_BOTH)['orderid'];
        empty($orderid) && $orderid = 0;

        $redis->set($key, $orderid);
    }

    $orderid = $redis->incr($key);
    $redis->rPush('tid_orderid_'.$tid, $orderid);
    $redis->close();

    return $orderid;
}


function success($message)
{
    return 'OK|'.$message;
}

function error($message)
{
    return 'ERR|'.$message;
}