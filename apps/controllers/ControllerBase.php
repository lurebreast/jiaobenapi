<?php

abstract class ControllerBase extends \Phalcon\Mvc\Controller
{
    public function beforeExecuteRoute() {
        \Phalcon\Tag::setTitleSeparator('·');
        \Phalcon\Tag::setTitle('校长');
        $this->view->setTemplateAfter('after');
        $this->view->setVar('config', $this->config);
        $this->view->setVar("domain_url", $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST']);
    }
    public function afterExecuteRoute()
    {
        if (!$this->session->get('admin_auth')) {
            $this->assets
                ->addCss('static/css/bootstrap.min.css')
                ->addCss('static/css/bootstrap-theme.min.css')
                ->addCss('static/css/pnotify.min.css')
                ->addJs('static/js/jquery.min.js')
                ->addJs('static/js/bootstrap.min.js')
                ->addJs('static/js/pnotify.min.js');
        } else {
            $this->assets
                ->addCss('static/css/bootstrap.min.css')
                ->addCss('static/css/font-awesome.min.css')
                ->addCss('static/css/ionicons.min.css')
                ->addCss('static/css/adminlte.min.css')
                ->addCss('static/css/skin-black.min.css')
                ->addCss('static/css/blue.css')
                ->addCss('static/css/bootstrap-datetimepicker.min.css')
                ->addCss('static/css/jquery-ui.css')
                ->addCss('static/css/select2.css')
                ->addCss('static/css/select2-bootstrap.min.css')
                ->addCss('static/css/bootstrap-editable.css')
                ->addCss('static/css/styles.css')
                ->addCss('static/css/layout.css')
                ->addCss('static/css/tree.css')
                ->addJs('static/js/jquery.min.js')
                ->addJs('static/js/jquery.scrollto.min.js')
                ->addJs('static/js/moment.min.js')
                ->addJs('static/js/jquery-ui.min.js')
                ->addJs('static/js/jquery-ui-i18n.min.js')
                ->addJs('static/js/bootstrap.min.js')
                ->addJs('static/js/bootstrap-datetimepicker.min.js')
                ->addJs('static/js/jquery.form.js')
                ->addJs('static/js/jquery.confirmexit.js')
                ->addJs('static/js/bootstrap-editable.min.js')
                ->addJs('static/js/select2.min.js')
                ->addJs('static/js/app.min.js')
                ->addJs('static/js/icheck.min.js')
                ->addJs('static/js/jquery.slimscroll.min.js')
                ->addJs('static/js/jquery.waypoints.min.js')
                ->addJs('static/js/sticky.min.js')
                ->addJs('static/js/readmore.min.js')
                ->addJs('static/js/masonry.pkgd.min.js')
                ->addJs('static/js/admin.js')
                ->addJs('static/js/treeview.js');
        }
    }

    public function getUser($feild = NULL, $default = NULL) {
        if ($this->session->get('identity')) {
            $this->_user = \Admin::findFirst($this->session->get('identity'));
            if ($feild) {
                return ($this->_user && isset($this->_user->$feild)) ? $this->_user->$feild : $default;
            }

            return $this->_user;
        } else {
            return FALSE;
        }
    }
    /**
     * 非加密发送数据
     */
    public function sendSuccess($message,$data=array()){
        $senddata['data']=$data;
        $senddata['sendstatus']='200';
        $senddata['sendmsg']=$message;
        return $this->sendData($senddata);
    }
    public function sendError($message,$data=array()){
        $senddata['data']=$data;
        $senddata['sendstatus']='500';
        $senddata['sendmsg']=$message;
        return $this->sendData($senddata);
    }
    /**
     *发送数据
     */
    public function sendData($data = NULL) {
        if (!empty($_GET['callback'])){
            $this->sendwap($data,$_GET['callback']);
        }else{
            $this->send($data);
        }

    }
    /**
     *跨域返回信息
     */
    public function sendwap($content = NULL,$callback){
        echo $callback.'('.json_encode($content,JSON_UNESCAPED_UNICODE).')';
        exit();
    }
    public function send($content = NULL)
    {
        if (is_array($content)){
            $content = array_filter($content, function ($val) {
                return !is_null($val);
            });
            $content = array_map(function ($val) {
                return is_object($val) ? $val->__toString() : $val;
            }, $content);
        }
        if (!empty($content)) {
            $this->response->setContentType('application/json;charset=utf-8');
            $this->response->setJsonContent($content, JSON_UNESCAPED_UNICODE);
        }
        $this->response->send();
        exit;
    }

    protected function getOrderId($typeid, $uid = 2)
    {
        $redis = $this->getRedis();

        $key = 'increment_order_id_'.$typeid.'_'.$uid;
        if (!$redis->exists($key)) {
            $newsdata = \Typedata::findfirst([
                'tid = :tid:',
                'bind' => ['tid' => $typeid],
                'order' => 'id DESC'
            ]);
            $order_id = $newsdata ? $newsdata->orderid : 1;

            $redis->set($key, $order_id);
        }

        $orderid = $redis->incr($key);
        if ($typeid != 521) { // 导出数据项目不读取
            $redis->rPush('tid_orderid_'.$typeid, $orderid);
        }

        $redis->close();

        return $orderid;
    }

    protected function getRedis()
    {
        $redis = new Redis();
        $redis->connect('127.0.0.1');

        return $redis;
    }
}