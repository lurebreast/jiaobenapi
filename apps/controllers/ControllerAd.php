<?php
abstract class ControllerAd extends \ControllerBase {

    public function beforeExecuteRoute() {

        parent::beforeExecuteRoute();
        if (!$this->session->get('admin_auth')) {
            $this->response->redirect('auth/index');
            return FALSE;
        }
        $gid = $this->session->get('gid');
        if (empty($gid)){//检测是否有用户组
            $this->flashSession->error('无效用户！');
            return FALSE;
        }
    }

  /*  public function afterExecuteRoute() {
        parent::afterExecuteRoute();
        $this->view->setVar('bodystyle', 'skin-blue sidebar-mini');
    }*/
}