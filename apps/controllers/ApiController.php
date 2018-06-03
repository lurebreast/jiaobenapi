<?php
class ApiController extends \ControllerBase
{
    public function getAction()
    {
        $typeid =  $this->request->get('typeid');
        $typedataid =  $this->request->get('typedataid');
        $uid = $this->request->get('uid');
        $rand = $this->request->get('rand');

        if (empty($typeid)){
            $this->serror('没有项目id');
        }
        if (empty($uid)){
            $this->serror('没有用户id');
        }

        if (isset($rand) && $rand == 0) { // 获取单条数据不更新状态
            $newdata = \Typedata::findfirst(
                ['tid = :tid: and uid = :uid: and orderid = :orderid:',
                    'bind' => ['tid' => $typeid,'uid'=>$uid,'orderid'=>$typedataid],
                    'order' => 'id DESC']
            );

            if (!$newdata){
                $this->serror('没有可用数据');
            } else {
                $this->ssussess($newdata->tid.'|'.$newdata->orderid.'|'.$newdata->data.'|'.$newdata->creattime);
            }
        }

        if (isset($rand) && $rand == 1){ //随机获取一条数据
                $newdata = \Typedata::findfirst(
                    ['tid = :tid: and uid = :uid: and status = :status:',
                        'bind' => [
                            'tid' => $typeid,
                            'uid'=>$uid,
                            'status' => '1'
                        ],
                        'order' => 'id DESC']
                );
                $orderid = $newdata->orderid;
                $randnum = rand(1,$orderid);

                $findData = [
                    'tid = :tid: and uid = :uid: and orderid = :orderid:',
                    'bind' => [
                        'tid' => $typeid,
                        'uid' => $uid,
                        'orderid' => $randnum
                    ]
                ];
        } else {
            if ($typedataid) { // 获取单条数据
                $findData = [
                    'tid = :tid: and uid = :uid: and status = :status: and orderid = :orderid:',
                    'bind' => [
                        'tid' => $typeid,
                        'uid' => $uid,
                        'orderid' => $typedataid,
                        'status' => '1'
                    ]
                ];
            } else {
                $findData =[
                    'tid = :tid: and uid = :uid: and status = :status:',
                    'bind' => ['tid' => $typeid,'uid'=>$uid,'status'=>'1'],
                    'order' => 'id DESC'
                ];
            }
        }

        $newdata = \Typedata::findfirst($findData);
        if (!$newdata) {
            $this->serror('没有可用数据');
        } else {
            $newdata->status = '2';
            $newdata->updatetime = time();
            if ($newdata->save()){
                $this->ssussess($newdata->tid.'|'.$newdata->orderid.'|'.$newdata->data.'|'.$newdata->creattime);
            }else{
                $this->serror('数据保存失败');
            }
        }
    }
    public function setAction(){
        $typeid =  $_GET['typeid'];
        $typedataid =  $this->request->get('typedataid');
        $only =  $this->request->get('only');
        $data =  $this->request->get('data');
        $imgBase64 =  $this->request->get('img');
        $imgBase641 =  $this->request->get('img1');
        $uid = $this->request->get('uid');
        if ($_GET['aa']) {
            $imgBase641 = 'data:image/png;base64,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';
        }

        if (empty($typeid)){
            $this->serror('项目id或者数据为空');
        }
        if (empty($uid)){
            $this->serror('没有用户id');
        }
        if ($only == '1'){
            $newsdata = \Typedata::findfirst([
                'tid = :tid:  and uid = :uid: and data = :data:',
                'bind' => ['tid' => $typeid,'uid'=>$uid,'data'=>$data]
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

            $basePath = $_SERVER['DOCUMENT_ROOT'];
            $img = '/images/'.md5(time().mt_rand(1, 10000)).'.png';
            file_put_contents($basePath.$img, $imgBin);
            unset($imgBin);
        }
        if ($imgBase641) {
            $imgBase641 = str_replace(' ', '+', $imgBase641);
            $imgBase641 = str_replace('data:image/png;base64,', '', $imgBase641);
            $imgBin = base64_decode($imgBase641);

            $basePath = $_SERVER['DOCUMENT_ROOT'];
            $img1 = '/images/'.md5(time().mt_rand(1, 10000)).'.png';
            file_put_contents($basePath.$img1, $imgBin);
            unset($imgBin);
        }

        $newsdata = \Typedata::findfirst([
            'tid = :tid:  and uid = :uid:',
            'bind' => ['tid' => $typeid,'uid'=>$uid],
            'order' => 'id DESC'
        ]);
        $orderid = $newsdata ? $newsdata->orderid + 1 : 1;

        $typedata = \Typedata::findfirst([
                'tid = :tid:  and uid = :uid: and orderid = :orderid:',
                'bind' => ['tid' => $typeid,'uid'=>$uid, 'orderid' => $typedataid],
                'order' => 'id DESC'
            ]) ?: new \Typedata();

        if (!$typedata->id) {
            $typedata->status = 1;
            $typedata->creattime = time();
            $typedata->orderid = $orderid;
            $typedata->tid = $typeid;
            $typedata->uid = $uid;
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
    public function getcountAction(){
        $typeid =  $this->request->get('typeid');
        $uid = $this->request->get('uid');
        $status = $this->request->get('status');
        if (empty($typeid)){
            $this->serror('项目id为空');
        }
        if (empty($uid)){
            $this->serror('没有用户id');
        }
        if (!empty($status)){
            $countnum = \Typedata::count([
                'tid = :tid:  and uid = :uid: and status = :status:',
                'bind' => ['tid' => $typeid,'uid'=>$uid,'status'=>$status]
            ]);
        }else{
            $countnum = \Typedata::count([
                'tid = :tid:  and uid = :uid:',
                'bind' => ['tid' => $typeid,'uid'=>$uid]
            ]);
        }
        $this->ssussess($countnum);
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
        $qian=array(" ","　","\t","\n","\r");
        return str_replace($qian, '', $str);
    }
}