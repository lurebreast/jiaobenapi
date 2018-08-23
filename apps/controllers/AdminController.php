<?php

class AdminController extends \ControllerBase
{

    public function addadminAction()
    {
        $id = $this->request->get('id', 'int', 0);
        $admin = [];
        if ($id) {
            $admin = CyAdmin::findFirst($id)->toArray();
            if ($admin) {
                $admin['roles'] = json_decode($admin['roles'], true);
                if ($admin['roles']['allow_type']) {
                    $admin['roles']['allow_type'] = str_replace(',', '|', $admin['roles']['allow_type']);
                }
            }
        }
        $this->view->setVar("user", $admin);

        if ($this->request->isPost() && $this->security->checkToken()) {
            $post =  $this->request->getPost();
            $config = \Phalcon\DI::getDefault()->get('config');

            if ($post['id']) {
                $admin = CyAdmin::findFirst($id);
            } else {
                if (\CyAdmin::findfirst(array(array('username' => $post['username'])))) {
                    Throw new \Exception('该用户已经存在');
                }

                $admin = new CyAdmin();
                $admin->create_time = time();
                $admin->username = $post['username'];
                $admin->status = 1;
                $admin->department_id = 11;
            }

            $post['password'] && $admin->password = md5($config->ad_key .$post['password']);

            if ($post['roles']['allow_type']) {
                $post['roles']['allow_type'] = str_replace('|', ',', $post['roles']['allow_type']); // 用逗号分割方便查询
                $post['roles']['allow_type'] = explode(',', $post['roles']['allow_type']);

                $post['roles']['allow_type'] = array_map(function($v){
                    if (is_numeric($v) && Type::findFirst($v)) {
                        return $v;
                    }

                },$post['roles']['allow_type']);

                $post['roles']['allow_type'] = array_unique($post['roles']['allow_type']);
                $post['roles']['allow_type'] = array_filter($post['roles']['allow_type']);
                $post['roles']['allow_type'] = implode(',', $post['roles']['allow_type']);
            }

            $admin->roles = json_encode($post['roles']);

            if ($admin->save()){
                $this->flashSession->success('操作成功');
            }else{
                $this->flashSession->success('操作失败');
            }

            $url = 'admin/addadmin';
            $post['id'] && $url .= '?id='.$post['id'];

            $this->response->redirect($url);

        }
    }
    /**
     * 管理员列表
     */
    public function adminlistAction(){
        $page = $this->request->get('page', 'int', 1);
        $adminlists = $this->modelsManager->createBuilder()
            ->from('CyAdmin')
            ->where('CyAdmin.id != "2"'); // 老板账号 可懂否
        $adminlists->orderBy('id desc');
        $paginator = new \Phalcon\Paginator\Adapter\QueryBuilder(array(
            "builder" => $adminlists,
            "limit" => 10,
            "page" => $page
        ));
        
        $this->view->setVar("page", $paginator->getPaginate());
    }

    public function deladminAction()
    {
        $id = $this->request->get('id', 'int', 0);

        if ($id) {
            $admin = CyAdmin::find($id);
            if ($admin) {
                if ($admin->delete()) {
                    $this->flashSession->success('账号删除成功');
                }
            }
        }

        $this->response->redirect('admin/adminlist');
    }
}