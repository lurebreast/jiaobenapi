<?php
abstract class ControllerAd extends \ControllerBase {

    protected $typearr;
    protected $typeAll = true;

    public function beforeExecuteRoute()
    {
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

        if (empty($this->admin['roles']['/typedata/index']) && $this->admin['roles']['allow_type']) {
            $this->typearr = \Type::find('is_delete = 0 and typeid in('.$this->admin['roles']['allow_type'].')');
            $this->typeAll = false;
        } else {
            $this->typearr = \Type::find('is_delete = 0');
        }
        $this->view->setVar("type", $this->typearr);
    }

  /*  public function afterExecuteRoute() {
        parent::afterExecuteRoute();
        $this->view->setVar('bodystyle', 'skin-blue sidebar-mini');
    }*/
}