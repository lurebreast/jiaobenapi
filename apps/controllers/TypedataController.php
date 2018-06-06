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
        ignore_user_abort();//关掉浏览器，PHP脚本也可以继续执行.
        set_time_limit(0);//通过set_time_limit(0)可以让程序无限制的执行下去

        $uid = $this->session->get('uid');
        $typeid = $this->request->getPost('typeid');
        $typeid = intval($typeid);

        $exce = false;
        $typearr = \Type::find();
        $this->view->setVar("type", $typearr);
        if ($this->request->isPost() && $this->security->checkToken()) {
            try {
                if ($this->request->hasFiles() != true) {
                    throw new \Exception('没有上传文件！');
                }

                foreach ($this->request->getUploadedFiles() as $file) {
                    if (empty( $typeid)){
                        throw new \Exception('没有选择项目！');
                    }

                    $file_name = $_SERVER['DOCUMENT_ROOT'].'/../apps/cron/'.$file->getName();

                    if (file_exists($file_name)) {
                        throw new \Exception($file->getName()."文件已经上传，请务重复");
                    }

                    $file->moveTo($file_name);
                    $this->getRedis()->lPush('dataadd_files', json_encode(['tid' => $typeid, 'file' => $file_name]));
                }
            } catch (\Exception $e) {
                $this->flashSession->error($e->getMessage());
                $exce = true;
            }

            !$exce && $this->flashSession->success('文件上传成功，等待后台执行导入数据');
            $this->response->redirect('typedata/dataadd');
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
        $typeid = explode(',', $typeid);

        $typename = '';
        foreach ($typeid as $id) {
            $id = intval($id);
            if ($id && $types = \Type::findfirst(intval($id))) {
                if ($types->delete()){
                    $phql = "DELETE FROM Typedata WHERE tid = ".$id." and uid = ".$this->session->get('uid');
                    $query = $this->modelsManager->createQuery($phql);
                    $query->execute();
                    $typename .= $types->typename.',';
                }
            }
        }

        if ($typename) {
            $this->flashSession->success('项目'.rtrim($typename, ',').'删除成功');
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
        $typeid = explode(',', $typeid);

        $typename = '';
        foreach ($typeid as $id) {
            $id = intval($id);
            if ($id && $types = \Type::findfirst($id)) {
                $phql = "DELETE FROM Typedata WHERE tid = ".$id." and uid = ".$this->session->get('uid');
                $query = $this->modelsManager->createQuery($phql);
                $query->execute();
                $typename .= $types->typename.',';
            }
        }

        if ($typename) {
            $this->flashSession->success('清空项目'.rtrim($typename, ',').'数据成功');
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
        $getlist = $this->request->get();
        $search['tid'] =$getlist['typeid'];
        $search['status'] =$getlist['status'];
        $search['sttime'] =$getlist['sttime'];
        $search['endtime'] =$getlist['endtime'];

        if (empty($search['tid'])) {
            $this->flashSession->error('必须选择一个项目');
            header('Location:/typedata/index');
            die;
        }

        $where = "t.tid = '{$search['tid']}'";
        if (!empty($search['status'])){
            $where .= " and status = '{$search['status']}'";
        }
        if (!empty($search['sttime'])){
            $where .= " and t.creattime >= '".strtotime($search['sttime'])."'";
        }
        if (!empty($search['endtime'])){
            $where .= " and t.creattime < '".strtotime($search['endtime'])."'";
        }

        $phql = "SELECT count(*) as total FROM Typedata AS t WHERE $where";
        $countnum =  $this->modelsManager->createQuery($phql)->getSingleResult()->total;

        $file = '/tmp/data-'.time().'.csv';

        $csv_data = [[
            '序号',
            '提取',
            '项目id',
            '项目名称',
            '图片',
            '图片1',
            '上传时间',
            '更新时间',
            '数据',
        ]];

        $typearr = \Type::find(array(array('uid'=>$this->session->get('uid'))));
        $this->view->setVar("type", $typearr);
        $typearrs = array();

        foreach ($typearr as $v){
            $typearrs[$v->typeid] = $v->typename;
        }

        $limit = 1000;
        $pages = ceil($countnum / $limit);
        $pages > 200 && $pages = 200; // 最多取二十万条数据
        for ($i = 1; $i <= $pages; $i++) {

            $first = $i == 1;
            if ($first) {
                $phql = "SELECT t.* FROM Typedata AS t WHERE $where order by id desc limit $limit";

            } else {
                $phql = "SELECT t.* FROM Typedata AS t WHERE id < '{$id}' and $where order by id desc limit $limit";
            }

            $res = $total = $this->modelsManager->createQuery($phql)->execute();

            !$first && $csv_data = [];
            foreach ($res as $data){
                $csv_data[] = [
                    $data->orderid,
                    $data->status == 1 ? '未提取' : '已提取',
                    $data->tid,
                    $typearrs[$data->tid],

                    $data->img ? $this->view->getVar("domain_url").$data->img : '',
                    $data->img1 ? $this->view->getVar("domain_url").$data->img1 : '',
                    date('Y-m-d H:i:s', $data->creattime),
                    $data->updatetime ? date('Y-m-d H:i:s', $data->updatetime) : '',
                    $data->data,
                ];

                $id = $data->id;
            }

            $this->writeCsv($file, $csv_data, $first);
        }

        $this->downloadCsv($file);
    }

    private function writeCsv($file, $data, $first = false)
    {
        if ($first) {
            $fp = fopen($file, 'w');
            fwrite($fp,chr(0xEF).chr(0xBB).chr(0xBF)); //Windows下使用BOM来标记文本文件的编码方式
        } else {
            $fp = fopen($file, 'a+');
        }

        foreach ($data as $line) {
            foreach ($line as $k => $v) {
                if ($k === 'url') {
                    unset($line[$k]);
                }
            }
            fputcsv($fp, $line);
        }

        fclose($fp);
        return true;
    }

    private function downloadCsv($file)
    {
        set_time_limit(0);  //大文件在读取内容未结束时会被超时处理，导致下载文件不全。

        $fpath = $file;
        $file_pathinfo = pathinfo($fpath);
        $file_name = $file_pathinfo['basename'];
        $handle = fopen($fpath,"rb");
        if (FALSE === $handle)
            exit("Failed to open the file");
        $filesize = filesize($fpath);

        header("Content-type:application/force-download");//更具不同的文件类型设置header输出类型
        header("Accept-Ranges:bytes");
        header("Accept-Length:".$filesize);
        header("Content-Disposition: attachment; filename=".$file_name);

        while (!feof($handle)) {
            $contents = fread($handle, 8192);
            echo $contents;
            @ob_flush();  //把数据从PHP的缓冲中释放出来
            flush();      //把被释放出来的数据发送到浏览器
        }
        fclose($handle);
        unlink($fpath);
        die;
    }
}