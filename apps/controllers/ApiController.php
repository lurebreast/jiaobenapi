<?php
class ApiController extends \ControllerBase {
    public function getAction(){
        $typeid =  $this->request->get('typeid');
        $uid = $this->request->get('uid');
        if (empty($typeid)){
            $this->serror('没有项目id');
        }
        if (empty($uid)){
            $this->serror('没有用户id');
        }
        $rand = $this->request->get('rand');
        if (!empty($rand)){
            $newdata = \Typedata::findfirst(
                ['tid = :tid: and uid = :uid:',
                'bind' => ['tid' => $typeid,'uid'=>$uid],
            'order' => 'id DESC']
            );
            $orderid = $newdata->orderid;
            $randnum = rand(1,$orderid);
            $newdata2 = \Typedata::findfirst(
                ['tid = :tid: and uid = :uid: and orderid = :orderid:',
                    'bind' => ['tid' => $typeid,'uid'=>$uid,'orderid'=>$randnum],
                    'order' => 'id DESC']
            );
            if ($newdata2){
                $this->ssussess($newdata2->id.'|'.$newdata2->data);
            }
        }
        $newsdata = \Typedata::findfirst([
            'tid = :tid: and uid = :uid: and status = :status:',
            'bind' => ['tid' => $typeid,'uid'=>$uid,'status'=>'1'],
            'order' => 'id DESC'
        ]);
        if (!empty($newsdata)){
            $newsdata->status = '2';
            $newsdata->updatetime = time();
            if ($newsdata->save()){
                $this->ssussess($newsdata->id.'|'.$newsdata->data);
            }else{
                $this->serror('数据保存失败');
            }

        }else{
            $this->serror('没有可用数据');
        }
    }
    public function setAction(){
        $typeid =  $this->request->get('typeid');
        $only =  $this->request->get('only');
        $data =  $this->request->get('data');
        $uid = $this->request->get('uid');
        if (empty($typeid)||!isset($data)){
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
        $newsdatas = \Typedata::findfirst([
            'tid = :tid:  and uid = :uid:',
            'bind' => ['tid' => $typeid,'uid'=>$uid],
            'order' => 'id DESC'
        ]);
        $orderid = $newsdatas->orderid;
        $typedata = new Typedata();
        $typedata->data = $data;
        $typedata->status = '1';
        $typedata->creattime = time();
        $typedata->orderid = $orderid+1;
        $typedata->tid = $typeid;
		$typedata->uid = $uid;
        if ($typedata->save()){
            $this->ssussess('');
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