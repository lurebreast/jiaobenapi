<?php
class AdminController extends \ControllerBase {

    public function addadminAction(){
        if ($this->request->isPost() && $this->security->checkToken()) {
            $list =  $this->request->getPost();
            try {
                if (\CyAdmin::findfirst(array(array('username' => $list['name'])))) {
                    Throw new \Exception('该用户已经存在');
                }
                $config = \Phalcon\DI::getDefault()->get('config');
                $admin = new CyAdmin();
                $admin->username = $list['name'];
                $admin->password = md5($config->ad_key .$list['passwd']);
                $admin->create_time = time();
                $admin->department_id = 11;
                $admin->status = '1';
                if ($admin->save()){
                    $this->flashSession->success('添加成功');
                }else{
                    Throw new \Exception('用户保存失败');
                }
                }catch (\Exception $e){
                $this->flashSession->error($e->getMessage());
            }
            $this->response->redirect('admin/addadmin');
        }
    }
    /**
     * 管理员列表
     */
    public function adminlistAction(){
        $page = $this->request->get('page', 'int', 1);
        $adminlists = $this->modelsManager->createBuilder()
            ->from('CyAdmin');
        $adminlists->orderBy('id desc');
        $paginator = new \Phalcon\Paginator\Adapter\QueryBuilder(array(
            "builder" => $adminlists,
            "limit" => 10,
            "page" => $page
        ));
        
        $this->view->setVar("page", $paginator->getPaginate());
    }
}