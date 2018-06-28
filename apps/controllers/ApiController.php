<?php
class ApiController extends \ControllerBase
{
    public function getAction()
    {
        $typeid =  $this->request->get('typeid');
        $typedataid =  $this->request->get('typedataid');
        $rand = $this->request->get('rand');

        if (empty($typeid)){
            $this->serror('没有项目id');
        }

        if (isset($rand) && $rand == 0) { // 获取单条数据不更新状态
            if ($typedataid) {
                $newdata = \Typedata::findfirst(
                    ['tid = :tid: and orderid = :orderid:',
                        'bind' => ['tid' => $typeid, 'orderid' => $typedataid],
                        'order' => 'id DESC']
                );
            } else {
                $randnum = mt_rand(1, $this->getOrderId($typeid) - 1);
                $newdata = \Typedata::findfirst(
                    ['tid = :tid: and orderid = :orderid:',
                        'bind' => [
                            'tid' => $typeid,
                            'orderid' => $randnum
                        ]
                    ]
                );
            }

            if (!$newdata){
                $this->serror('没有可用数据');
            } else {
                $this->ssussess($newdata->id.'|'.$newdata->data.'|'.$newdata->tid.'|'.$newdata->orderid.'|'.date('Y-m-d H:i:s', $newdata->creattime));
            }
        }

        $redis = $this->getRedis();
        if (isset($rand) && $rand == 1){ //随机获取一条数据
            $randnum = mt_rand(1, $this->getOrderId($typeid) - 1);
            $less = mt_rand(0, 1) ? true : false;

            $findData = [
                'tid = :tid: and orderid '.($less ? '<' : '>').' :orderid: and status = :status:',
                'bind' => [
                    'tid' => $typeid,
                    'orderid' => $randnum,
                    'status' => 1
                ]
            ];
            $redis->lRem('tid_orderid_'.$typeid, $randnum, 2);
        } else {
            if ($typedataid) { // 获取单条数据
                $findData = [
                    'tid = :tid: and orderid = :orderid: and status = :status:',
                    'bind' => [
                        'tid' => $typeid,
                        'orderid' => $typedataid,
                        'status' => 1
                    ]
                ];
                $redis->lRem('tid_orderid_'.$typeid, $typedataid, 2);
            } else {
                if ($orderid = $redis->rPop('tid_orderid_'.$typeid)) {
                    $findData = [
                        'tid = :tid: and orderid = :orderid:',
                        'bind' => ['tid' => $typeid, 'orderid'=> $orderid],
                    ];
                }
            }
        }
        $redis->close();

        if (!empty($findData)) {
            $newdata = \Typedata::findfirst($findData);
        }

        if (empty($newdata)) {
            $this->serror('没有可用数据');
        } else {
            $newdata->status = 2;
            $newdata->updatetime = time();

            if ($newdata->save()){
                $this->ssussess($newdata->id.'|'.$newdata->data.'|'.$newdata->tid.'|'.$newdata->orderid.'|'.date('Y-m-d H:i:s', $newdata->creattime));
            }else{
                $this->serror('数据保存失败');
            }
        }
    }

    public function setAction()
    {
        $typeid =  $_GET['typeid'];
        $typedataid =  $this->request->get('typedataid');
        $only =  $this->request->get('only');
        $data =  $this->request->get('data');
        $imgBase64 =  $this->request->get('img');
        $imgBase641 =  $this->request->get('img1');

        if (empty($typeid)){
            $this->serror('项目id或者数据为空');
        }

        if (!\Type::findfirst($typeid)) {
            $this->serror('没有此项目');
        }

        if ($only == '1'){
            $newsdata = \Typedata::findfirst([
                'tid = :tid: and data = :data:',
                'bind' => ['tid' => $typeid, 'data'=>$data]
            ]);
            if ($newsdata){
                $this->serror('该数据重复');
            }
        }

        $encode = mb_detect_encoding($data, array('ASCII','UTF-8','GB2312','GBK'));
        if ($encode != 'UTF-8'){
            $data = iconv($encode,'UTF-8',$data);
        }

        $img = $img1 = '';
        if ($imgBase64) {
            $imgBase64 = str_replace(' ', '+', $imgBase64);
            $imgBase64 = str_replace('data:image/png;base64,', '', $imgBase64);
            $imgBin = base64_decode($imgBase64);

            if ($imgBin) {
                $basePath = $_SERVER['DOCUMENT_ROOT'];
                $img = '/images/'.md5(time().mt_rand(1, 10000)).'.png';
                file_put_contents($basePath.$img, $imgBin);
                unset($imgBin);
            } else {
                file_put_contents('/tmp/img_upload.txt', date('Y-m-d H:i:s').' '.$typeid.' '.$this->request->get('img')."\n", FILE_APPEND);
            }
        }
        if ($imgBase641) {
            $imgBase641 = str_replace(' ', '+', $imgBase641);
            $imgBase641 = str_replace('data:image/png;base64,', '', $imgBase641);
            $imgBin = base64_decode($imgBase641);
            if ($imgBin) {
                $basePath = $_SERVER['DOCUMENT_ROOT'];
                $img1 = '/images/'.md5(time().mt_rand(1, 10000)).'.png';
                file_put_contents($basePath.$img1, $imgBin);
                unset($imgBin);
            } else {
                file_put_contents('/tmp/img_upload.txt', date('Y-m-d H:i:s').' '.$typeid.' '.$this->request->get('img1')."\n", FILE_APPEND);
            }
        }

        $typedata = \Typedata::findfirst([
                'tid = :tid: and orderid = :orderid:',
                'bind' => ['tid' => $typeid, 'orderid' => $typedataid],
                'order' => 'id DESC'
            ]) ?: new \Typedata();

        if (!$typedata->id) {
            $typedata->status = 1;
            $typedata->creattime = time();
            $typedata->orderid = $this->getOrderId($typeid);
            $typedata->tid = $typeid;
        }
        $data && $typedata->data = $data;
        $img && $typedata->img = $img;
        $img1 && $typedata->img1 = $img1;

        if ($typedata->save()){
            $this->ssussess($typedata->tid.'|'.$typedata->orderid);
        }else{
            $this->serror('数据保存失败');
        }
    }
    public function getcountAction()
    {
        $typeid =  $this->request->get('typeid');
        $status = $this->request->get('status');
        if (empty($typeid)){
            $this->serror('项目id为空');
        }

        $redis = $this->getRedis();
        $key = 'typeid_count_'.$typeid.'_'.$status;

        if ($redis->setnx($key.'_lock', true)) {
            $redis->expire($key.'_lock', 60);

            if (!empty($status)){
                $countnum = \Typedata::count([
                    'tid = :tid: and status = :status:',
                    'bind' => ['tid' => $typeid,'status'=>$status]
                ]);
            }else{
                $countnum = \Typedata::count([
                    'tid = :tid:',
                    'bind' => ['tid' => $typeid]
                ]);
            }

            $redis->setex($key, 86400, $countnum);
        } else {
            $countnum = $redis->get($key);
        }

        $this->ssussess($countnum);
    }

    public function delAction()
    {
        $typeid =  $this->request->get('typeid');
        $typedataid =  $this->request->get('typedataid');

        if (empty($typeid)){
            $this->serror('项目id为空');
        }
        if (empty($typedataid)){
            $this->serror('没有数据id');
        }

        $typedata = \Typedata::findfirst([
            'tid = :tid: and orderid = :orderid:',
            'bind' => [
                'tid' => $typeid,
                'orderid' => $typedataid
            ]
        ]);

        if ($typedata) {
            if ($typedata->delete()) {
                $this->ssussess('');
            } else {
                $this->serror('删除失败');
            }
        } else {
            $this->serror('没有数据');
        }
    }

    public function serror($msg){
        echo 'ERR|'.$msg;
        die();
    }
    public function ssussess($msg){
        $msg = $this->trimall($msg);
        echo 'OK|'.$msg;
        die();
    }
   public function trimall($str){
        $qian=array("\n","\r");
        return str_replace($qian, '', $str);
    }


}