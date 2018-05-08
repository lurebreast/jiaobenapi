<?php

class TypedataController  extends \ControllerAd{

    public function indexAction(){

        $page = $this->request->get('page', 'int', 1);
        $typearr = \Type::find(array(array('uid'=>$this->session->get('uid'))));
        $this->view->setVar("type", $typearr);
        $typearrs = array();
        foreach ($typearr as $v){
            $typearrs[$v->typeid] = $v->typename;
        }
        $this->view->setVar("typearrs", $typearrs);
        $datalists = $this->modelsManager->createBuilder()
            ->from('Typedata')
        ;
        //处理搜索
        $getlist = $this->request->get();
        $search['tid'] =$getlist['typeid'];
        $search['status'] =$getlist['status'];
        $search['sttime'] =$getlist['sttime'];
        $search['endtime'] =$getlist['endtime'];
        if (!empty($search['status'])){
            $datalists-> andwhere("status = '".$search['status']."'");
        }
        if (!empty($search['tid'])){
            $datalists-> andwhere("tid = '".$search['tid']."'");
        }
        if (!empty($search['sttime'])){
            $datalists-> andwhere("creattime >= ".strtotime($search['sttime']));
        }
        if (!empty($search['endtime'])){
            $datalists-> andwhere("creattime < ".strtotime($search['endtime']));
        }
        $uid = $this->session->get('uid');
        $datalists-> andwhere("uid = '".$uid."'");
        $datalists->orderBy('id desc');
        $paginator = new \Phalcon\Paginator\Adapter\QueryBuilder(array(
            "builder" => $datalists,
            "limit" => 30,
            "page" => $page
        ));

        $this->view->setVar("page", $paginator->getPaginate());
        $this->view->setVar("search", $search);
    }
    public function typeaddAction(){
        $this->view->setRenderLevel(\Phalcon\Mvc\View::LEVEL_ACTION_VIEW);
        if ($this->request->isPost() && $this->security->checkToken()) {
            $typename=$this->request->getPost('typename');
            $type = new Type();
            $type->typename = $typename;
            $type->uid = $this->session->get('uid');
            if ($type->save()){
                $this->flashSession->success('添加成功');
            }else{
                $this->flashSession->error('添加失败');
            }
            $this->response->redirect('typedata/index');
        }
    }
    public function dataaddAction(){
        $typearr = \Type::find();
        $this->view->setVar("type", $typearr);
        if ($this->request->isPost() && $this->security->checkToken()) {
            try {
                if ($this->request->hasFiles() != true) {
                    throw new \Exception('没有上传文件！');
                }
                foreach ($this->request->getUploadedFiles() as $file) {

                    $typeid = $this->request->getPost('typeid');
                    $typeid = intval($typeid);
                    if (empty( $typeid)){
                        throw new \Exception('没有选择项目！');
                    }
                    $filess = fopen($file->getPathname(), "r");
                    $user=array();
                    $i=0;
                    $uid = $this->session->get('uid');
                    $newsdata = \Typedata::findfirst([
                        'tid = :tid: and uid = :uid:',
                        'bind' => ['tid' => $typeid,'uid'=>$uid],
                        'order' => 'id DESC'
                    ]);
                    $orderid = $newsdata->orderid;
//输出文本中所有的行，直到文件结束为止。
                    while(! feof($filess))
                    {
                        $user[$i]= fgets($filess);//fgets()函数从文件指针中读取一行
                        $i++;
                    }
                    fclose($filess);
                    $user=array_filter($user);
                    unlink($file->getPathname());
                    $typedata = new  Typedata();
                    $typearr = array();
                    $typearrs = array();
                    $time =time();
                    $a = 0;
                    foreach ($user as $data){
                        $a++;
                        $orderid++;
                        $encode = mb_detect_encoding($data, array('ASCII','UTF-8','GB2312','GBK'));
                        if ($encode != 'UTF-8'){
                            $data = iconv($encode,'UTF-8',$data);
                        }
                        $typearr['data'] = $data;
                        $typearr['creattime'] = $time;
                        $typearr['status'] = '1';
                        $typearr['tid'] =$typeid;
                        $typearr['uid'] =$uid ;
                        $typearr['orderid'] =$orderid;
                        $typearrs[] = $typearr;
                    }
                    $installkey = array('data','creattime','status','tid','uid','orderid');
                    $res = $typedata->insertall($installkey,$typearrs);
                    if ($res){
                        $this->flashSession->success('插入成功'.$a.'条数据');
                    }else{
                        $this->flashSession->error('插入数据失败!');
                    }

                }
            } catch (\Exception $e) {
                $this->flashSession->error($e->getMessage());
            }
            $this->response->redirect('typedata/index?typeid='.$typeid);
        }

    }
    public function apiAction(){
        $uid = $this->session->get('uid');
        $this->view->setVar("uid", $uid);
    }
    public function typeadAction(){
        $typearr = \Type::find(array(array('uid'=>$this->session->get('uid'))));
        $this->view->setVar("type", $typearr);
    }
    public function deltypeAction(){
        $typeid = $this->request->get('typeid');
        $typeid = intval($typeid);
        try {
            if (empty($typeid)){
                Throw new \Exception('项目id为空！');
            }
            $types = \Type::findfirst($typeid);
            if (empty($types)){
                Throw new \Exception('没有该项目！');
            }
            if ($types->delete()){
                $phql = "DELETE FROM Typedata WHERE tid = ".$typeid." and uid = ".$this->session->get('uid');
                $query = $this->modelsManager->createQuery($phql);
                $cars  = $query->execute();
                $this->flashSession->success('删除项目'.$types->typename.'成功');
            }else{
                Throw new \Exception('删除失败！');
            }
        }catch (\Exception $e){
            $this->flashSession->error($e->getMessage());
        }
        $this->response->redirect('typedata/typead');
    }

    public function edittypeAction(){
        $this->view->setRenderLevel(\Phalcon\Mvc\View::LEVEL_ACTION_VIEW);
        $typeid = $this->request->get('typeid');
        $typeid = intval($typeid);
        $types = \Type::findfirst($typeid);
        if ($this->request->isPost() && $this->security->checkToken()) {
            try {
                $typename = $this->request->getPost('typename');
                if (empty($typename)){
                    Throw new \Exception('项目不能为空！');
                }
                $types->typename = $typename;
                if ($types->save()){
                    $this->flashSession->success('保存成功');
                }else{
                    Throw new \Exception('保存失败！');
                }
                $this->response->redirect('typedata/typead');
            }catch (\Exception $e){
                $this->flashSession->error($e->getMessage());
            }
        }
        $this->view->setVar("types", $types);
        $this->view->setVar("typeid", $typeid);
    }
    /**
     * 清空数据
     */
    public function cleardataAction(){
        $typeid = $this->request->get('typeid');
        $typeid = intval($typeid);
        try {
            if (empty($typeid)){
                Throw new \Exception('项目id为空！');
            }
            $types = \Type::findfirst($typeid);
            if (empty($types)){
                Throw new \Exception('没有该项目！');
            }
                $phql = "DELETE FROM Typedata WHERE tid = ".$typeid." and uid = ".$this->session->get('uid');
                $query = $this->modelsManager->createQuery($phql);
                $cars  = $query->execute();
                $this->flashSession->success('清空项目'.$types->typename.'数据成功');
        }catch (\Exception $e){
            $this->flashSession->error($e->getMessage());
        }
        $this->response->redirect('typedata/typead');

    }
    public function deldataAction(){
        $typeid = $this->request->get('id');
        $typeid = intval($typeid);
        try {
            if (empty($typeid)){
                Throw new \Exception('id为空！');
            }
            $types = \Typedata::findfirst($typeid);
            $tid = $types->tid;
            if (empty($types)){
                Throw new \Exception('没有该项目！');
            }
            if ($types->delete()){
                $this->flashSession->success('删除成功');
            }else{
                Throw new \Exception('删除失败！');
            }
        }catch (\Exception $e){
            $this->flashSession->error($e->getMessage());
        }
        $this->response->redirect('typedata/index?typeid='.$tid);
    }
    public function outdataAction(){
        $datalists = $this->modelsManager->createBuilder()
            ->from('Typedata')
        ;
        $getlist = $this->request->get();
        $search['tid'] =$getlist['typeid'];
        $search['status'] =$getlist['status'];
        $search['sttime'] =$getlist['sttime'];
        $search['endtime'] =$getlist['endtime'];
        if (!empty($search['status'])){
            $datalists-> andwhere("status = '".$search['status']."'");
        }
        if (!empty($search['tid'])){
            $datalists-> andwhere("tid = '".$search['tid']."'");
        }
        if (!empty($search['sttime'])){
            $datalists-> andwhere("creattime >= ".strtotime($search['sttime']));
        }
        if (!empty($search['endtime'])){
            $datalists-> andwhere("creattime < ".strtotime($search['endtime']));
        }
        $uid = $this->session->get('uid');
        $datalists-> andwhere("uid = '".$uid."'");
        $datalists->orderBy('id desc');
        $paginator = new \Phalcon\Paginator\Adapter\QueryBuilder(array(
            "builder" => $datalists,
            "limit" => 30000
        ));
        $res = $paginator->getPaginate();
        Header( "Content-type:   application/octet-stream ");
        Header( "Accept-Ranges:   bytes ");
        header( "Content-Disposition:   attachment;   filename=".$search['tid']."项目数据.txt ");
        header( "Expires:   0 ");
        header( "Cache-Control:   must-revalidate,   post-check=0,   pre-check=0 ");
        header( "Pragma:   public ");
        foreach ($res->items as $data){
            echo $data->data."\r\n";
        }
    die();
    }
}