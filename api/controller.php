<?php
/**
 * Created by PhpStorm.
 * User: Henry
 * Date: 2018/7/3
 * Time: 20:31
 */

function action_getCount($request)
{
    global $config;
    $typeid = $request->get['typeid'] ?: 0;
    $status = $request->get['status'] ?: null;

    if (!$typeid) {
        return error('项目id为空');
    }

    $redis = new Redis();
    $redis->connect($config['redis']['host'], $config['redis']['port']);
    $key = 'typeid_count_'.$typeid.'_'.$status;

    if ($redis->setnx($key.'_lock1', true)) {
        $redis->expire($key.'_lock1', 10);

        $countnum = 0;
        $mysqli = new mysqli($config['database']['host'].':'.$config['database']['port'], $config['database']['username'], $config['database']['password'], $config['database']['dbname']);
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


function success($message)
{
    return 'OK|'.$message;
}

function error($message)
{
    return 'ERR|'.$message;
}