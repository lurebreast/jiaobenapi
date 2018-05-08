<?php
class AuthController extends \ControllerBase {

    public function indexAction() {
        if ($this->session->get('admin_auth')) {
            /*if ($this->_user->active == \Users::status_normal) {
                if ((int) $this->_user->sys != \Users::common_sys) {*/
                   // $this->response->redirect('index/index');
                   // return;
              //  }
           // }
         //   $this->session->remove('admin_auth');
       }
    }


    public function checkAction() {
        try {
            if (!$this->request->isPost()) {
                throw new \Exception('非法请求，请重新登录！');
            }
            if (!$this->security->checkToken()) {
                throw new \Exception('提交超时，请重新登录！');
            }

            $username = $this->request->getPost('username');
            $password = $this->request->getPost('password');
            $user = \CyAdmin::findFirst(array(
                'username = :username: ',
                'bind' => ['username' => $username]
            ));

            if ($user == FALSE) {
                throw new \Exception('帐号错误！');
            }
            // 获取配置文件
            $config = \Phalcon\DI::getDefault()->get('config');
            if( $user->password != md5($config->ad_key.$password)) {
                throw new \Exception('密码错误！');
            }
            if(empty($user->status)) {
                throw new \Exception('账号被禁用！');
            }
            if ($user) {
                $this->session->set('admin_auth', $user->id);
                $this->session->set('uid',$user->id );
                $this->session->set('gid', $user->department_id);
                $this->session->set('username', $user->username);

                $this->flashSession->success('登录成功!');
                $this->response->redirect('index/index');
            } else {
                $this->flashSession->error('登录失败');
                $this->response->redirect('auth/index');
            }
        } catch (\Exception $e) {
            $this->flashSession->error($e->getMessage());
            $this->response->redirect('auth/index');
        }
    }

    public function logoutAction() {
        $this->session->destroy();
        $this->cookies->set('identity', NULL, time() + 1209600, '/');
        $this->cookies->set('token', NULL, time() + 1209600, '/');
        $this->response->redirect('auth/index');
        $this->response->send();
        exit;
    }
    /**
     * 获取oss文件上传路径
     */
    public function getosskeyAction(){
        $this->view->setRenderLevel(\Phalcon\Mvc\View::LEVEL_ACTION_VIEW);
        $list=$this->request->get('type');
        $typearr=array('1'=>'image',2=>'apk');
        $typename=$typearr[$list['type']];
        if ($list['type']==1){
            $size='2';
        }else if ($list['type']==2){
            $size='1024';
        }
        header('Access-Control-Allow-Origin:*');
        if (empty($typename)){
            $this->sendError('无效的类型');
        }
        $dir=$typename.'/'.date('Y').'/'.date('m'.'/'.date('d').'/');
        $res=\CyOssfile::wapupfile($size,$dir);
        $this->sendSuccess('接收成功',$res);
    }
}