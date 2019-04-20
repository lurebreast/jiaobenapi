<?php
use \Phalcon\Paginator\Adapter\Sql as PaginatorSql;
use Phalcon\Mvc\Model\Resultset\Simple as Resultset;

class TypedataController  extends \ControllerAd{

    public function indexAction()
    {
        $page = $this->request->get('page', 'int', 1);
        $target = $this->request->get('target', 'string', 'index');

        $typearrs = array();
        foreach ($this->typearr as $v){
            $typearrs[$v->typeid] = $v->typename;
        }
        $this->view->setVar("typearrs", $typearrs);
        //处理搜索
        $search = $this->request->get();

        !isset($search['sttime']) && $this->isMobile() && $search['sttime'] = date('Y-m-d 00:00:00');
        foreach (['status', 'typeid', 'sttime', 'endtime', 'recycle', 'data_unique', 'target', 'data'] as $v) {
            !isset($search[$v]) && $search[$v] = null;
        }

        $where = 'where 1';
        if (!empty($search['status'])){
            $where .= ' and status ='.$search['status'];
        }
        if (!empty($search['typeid'])){
            $where .= ' and tid ='.$search['typeid'];
        }
        if (!empty($search['sttime'])){
            $where .= " and creattime >= ".strtotime($search['sttime']);
        }
        if (!empty($search['endtime'])){
            $where .= " and creattime < ".strtotime($search['endtime']);
        }
        if (!empty($search['data'])){
            $where .= " and data like '%".$search['data']."%'";
        }
        if (!$this->typeAll) {
            $inTid = implode(',', array_keys($typearrs));
            $where .= " and tid in($inTid)";
        }

        $table = $target == 'recycle' ? 'typedata_recycle' : 'typedata';
        if (!empty($search['data_unique'])){
            $sql = "SELECT * FROM $table $where GROUP BY data order by id desc LIMIT :limit OFFSET :offset";
            $totalSql = "SELECT COUNT(DISTINCT(data)) rowcount FROM $table $where";
        } else {
            $sql = "SELECT * FROM $table $where order by id desc LIMIT :limit OFFSET :offset";
            $totalSql = "SELECT COUNT(*) rowcount FROM $table $where";
        }

        $paginator = new PaginatorSql(
            array(
                "sql" => $sql,
                "total_sql" => $totalSql,
                "limit"   => 30,
                "page"    => $page
            )
        );

        $this->view->setVar("page", $paginator->getPaginate());
        $this->view->setVar("search", $search);
        $this->view->setVar("target", $target);
    }

    public function moveTypedataAction()
    {
        $ids = $this->request->get('ids');
        $type = $this->request->get('type');


        $ids = array_filter(explode(',', $ids));
        $ids = implode(',', $ids);
        if (empty($ids)){
            Throw new \Exception('id为空！');
        }

        $typedata = new \Typedata();
        $con = $typedata->getWriteConnection();
        if ($type == 'recycle') {
            $phql = "insert into typedata_recycle select * from typedata where id in($ids)";
            $con->query($phql);
        }

        $con->query("DELETE FROM typedata WHERE id in($ids)", null);
        $con->query("DELETE FROM typedata_recycle WHERE id in($ids)", null);
        header("Location:".$_SERVER['HTTP_REFERER']);
    }
    public function typeaddAction(){
        $this->view->setRenderLevel(\Phalcon\Mvc\View::LEVEL_ACTION_VIEW);
        $timestamp = time();
        $typename=$this->request->getPost('typename');

        if ($this->request->isPost() && $this->security->checkToken()) {

            if (\Type::findFirst("typename='".addslashes($typename)."'")) {
                $this->flashSession->success('此项目已存在');
            } else {
                $type = new Type();
                $type->typename = $typename;
                $type->createtime = $timestamp;
                $type->updatetime = $timestamp;

                if ($type->save()){
                    $this->addAdminAllowType($type->typeid);
                    $this->flashSession->success('添加成功');
                }else{
                    $this->flashSession->error('添加失败');
                }
            }

            $this->response->redirect('typedata/index');
        }
    }
    public function dataaddAction(){
        ignore_user_abort();//关掉浏览器，PHP脚本也可以继续执行.
        set_time_limit(0);//通过set_time_limit(0)可以让程序无限制的执行下去

        $typeid = $this->request->getPost('typeid');
        $typeid = intval($typeid);

        $exce = false;
        if ($this->request->isPost() && $this->security->checkToken()) {

            try {
                if ($this->request->hasFiles() != true) {
                    throw new \Exception('没有上传文件！');
                }

                foreach ($this->request->getUploadedFiles() as $file) {
                    if (empty( $typeid)){
                        throw new \Exception('没有选择项目！');
                    }

                    if (strpos($file->getName(), '.txt') === false) {
                        throw new \Exception($file->getName()."上传文件不是txt文件");
                    }

                    $file_name = $_SERVER['DOCUMENT_ROOT'].'/../apps/cron/import_data_'.$typeid.'.txt';
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

    public function imgaddAction(){
        ignore_user_abort();//关掉浏览器，PHP脚本也可以继续执行.
        set_time_limit(0);//通过set_time_limit(0)可以让程序无限制的执行下去

        $typeid = $this->request->getPost('typeid');
        $typeid = intval($typeid);

        if ($this->request->isPost() && $this->security->checkToken()) {

            try {
                if ($this->request->hasFiles() != true) {
                    throw new \Exception('没有上传文件！');
                }

                $typedata = new \Typedata();
                $con = $typedata->getWriteConnection();
                $imgages_dir = __DIR__.'/../../public';

                foreach ($this->request->getUploadedFiles() as $file) {
                    if (empty( $typeid)){
                        throw new \Exception('没有选择项目！');
                    }

                    $order_id = $this->getOrderId($typeid);

                    $img_src = "/images/{$typeid}_{$order_id}_1.".pathinfo($file->getName())['extension'];
                    $file->moveTo($imgages_dir.$img_src);
                    $con->query("INSERT INTO typedata(tid, orderid, status, data, creattime, img1) VALUES({$typeid}, {$order_id}, 1, '', ".time().", '{$img_src}')");
                }
            } catch (\Exception $e) {
                $this->flashSession->error($e->getMessage());
            }

            $this->flashSession->success('文件上传成功');
            $this->response->redirect('typedata/imgadd');
        }
    }

    public function apiAction(){
        $uid = $this->session->get('uid');
        $this->view->setVar("uid", $uid);
    }
    public function typeadAction(){

        $page = $this->request->get('page', 'int', 1);
        $list = $this->modelsManager->createBuilder()
            ->from('Type');

        $recycle = !empty($this->request->get('recycle')) ? true : false;
        if ($recycle) {
            $list->where('Type.is_delete = 1');
        } else {
            $list->where('Type.is_delete = 0');
        }

        if (empty($this->admin['roles']['/typedata/index']) && $this->admin['roles']['allow_type']) {
            $list->andWhere('Type.typeid in('.$this->admin['roles']['allow_type'].')');
        }

        $list = $list->orderBy('typeid desc');


        $paginator = new \Phalcon\Paginator\Adapter\QueryBuilder(array(
            "builder" => $list,
            "limit" => 10,
            "page" => $page
        ));

        $this->view->setVar("page", $paginator->getPaginate());
        $this->view->setVar("recycle", $recycle);
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
                $types->updatetime = time();
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

    public function recycletypeAction(){
        $typeid = $this->request->get('typeid');
        $typeid = explode(',', $typeid);

        foreach ($typeid as $id) {
            $id = intval($id);

            $typedata = new \Typedata();
            $con = $typedata->getWriteConnection();

            $con->query("UPDATE type SET is_delete = 1, updatetime=".time()." WHERE typeid = ".$id);
            $con->query("insert into typedata_recycle select * from typedata where tid=$id");
            $con->query("delete from typedata where tid=$id");
        }

        $this->flashSession->success('操作成功');
        $this->response->redirect('typedata/typead');
    }

    public function deltypeAction(){
        $typeid = $this->request->get('typeid');
        $typeid = explode(',', $typeid);

        $typename = '';
        foreach ($typeid as $id) {
            $id = intval($id);
            if ($id && $types = \Type::findfirst(intval($id))) {
                if ($types->delete()){
                    $this->modelsManager->createQuery("DELETE FROM Typedata WHERE tid = {$id}")->execute();
                    $this->modelsManager->createQuery("DELETE FROM TypedataRecycle WHERE tid = {$id}")->execute();
                    $typename .= $types->typename.',';

                    // 删除图片
                    array_map(function($v){
                        unlink($v);
                    }, glob(getcwd().'/images/'.$id.'_*.png'));

                    // 删除redis缓存
                    $redis = $this->getRedis();
                    $redis->del("tid_orderid_".$id);
                    $redis->del("increment_order_id_".$id.'_2');
                }
            }
        }

        if ($typename) {
            $this->flashSession->success('项目'.rtrim($typename, ',').'删除成功');
        }

        $this->response->redirect('typedata/typead?recycle=1');
    }

    public function restortypeAction(){
        $typeid = $this->request->get('typeid');
        $typeid = explode(',', $typeid);

        foreach ($typeid as $id) {
            $id = intval($id);

            $typedata = new \Typedata();
            $con = $typedata->getWriteConnection();

            $con->query("UPDATE type SET is_delete = 0, updatetime=".time()." WHERE typeid = ".$id);
            $con->query("insert into typedata select * from typedata_recycle where tid=$id");
            $con->query("delete from typedata_recycle where tid=$id");

        }

        $this->flashSession->success('操作成功');
        $this->response->redirect('typedata/typead?recycle=1');
    }

    public function status1Action(){
        $typeid = $this->request->get('typeid');
        $typeid = explode(',', $typeid);

        foreach ($typeid as $id) {
            $id = intval($id);

            $this->getRedis()->lPush('tid_status1', $id);
        }

        $this->flashSession->success('操作成功, 等待后台执行完成');
        $this->response->redirect('typedata/typead');
    }

    public function status2Action(){
        $typeid = $this->request->get('typeid');
        $typeid = explode(',', $typeid);

        foreach ($typeid as $id) {
            $id = intval($id);

            $typedata = new \Typedata();
            $con = $typedata->getWriteConnection();

            $con->query("UPDATE typedata SET status = 2, updatetime=".time()." WHERE tid = ".$id);

            // 删除redis队列
            $redis = $this->getRedis();
            $rs = $redis->del("tid_orderid_".$id);
        }

        $this->flashSession->success('操作成功');
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
        $this->response->redirect('typedata/typead?recycle=');
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
                $this->modelsManager->createQuery("DELETE FROM Typedata WHERE tid = {$id}")->execute();
                $this->modelsManager->createQuery("DELETE FROM TypedataRecycle WHERE tid = {$id}")->execute();
                $typename .= $types->typename.',';
            }
        }

        if ($typename) {
            $this->flashSession->success('清空项目'.rtrim($typename, ',').'数据成功');
        }
        $this->response->redirect('typedata/typead?recycle=1');
    }

    public function ajax_export_processAction()
    {
        $tid = $this->request->get('typeid');
        $result = $this->getRedis()->hGetAll('data_export_'.$tid);
        if ($result['lock'] == 0) {
            $result['files'] = json_decode($result['files'], true);
        }

        echo json_encode($result);
        die;
    }

    public function outdataAction(){
        set_time_limit(0);

        $json = [
            'code' => 200,
            'data' => [],
            'msg' => ''
        ];
        $redis = $this->getRedis();
        $search = $this->request->get();

        if (empty($search['typeid'])) {
            $json['code'] = 500;
            $json['msg'] = '必须选择一个项目';
        }

        if ($redis->hget('data_export_'.$search['typeid'], 'lock')) {
//            $json['code'] = 501;
//            $json['msg'] = '该项目正在导出';
        }

        echo json_encode($json);
        if ($json['code'] == 200) {
            $redis->lPush('data_export', json_encode($search));
            $redis->hset('data_export_'.$search['typeid'], 'percent', 1);
            $redis->hset('data_export_'.$search['typeid'], 'lock', 1);
            $redis->hDel('data_export_'.$search['typeid'], 'maxId');
            $redis->expire('data_export_'.$search['typeid'], 864000);
        }
        die;
    }

}
