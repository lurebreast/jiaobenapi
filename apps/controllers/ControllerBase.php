<?php

abstract class ControllerBase extends \Phalcon\Mvc\Controller
{
    /* @var $admin array */
    protected $admin;

    public function beforeExecuteRoute() {
        \Phalcon\Tag::setTitleSeparator('·');
        \Phalcon\Tag::setTitle('校长');
        $this->view->setTemplateAfter('after');
        $this->view->setVar('config', $this->config);
        $this->view->setVar("domain_url", $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST']);
        $this->view->setVar("admin", $this->getAdminUser());
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

    public function getAdminUser()
    {
        if ($this->session->get('uid')) {
            $this->admin = \CyAdmin::findFirst($this->session->get('uid'));
            if ($this->admin) {
                $this->admin = $this->admin->toArray();
                $this->admin['roles'] = json_decode($this->admin['roles'], true);

                return $this->admin;
            }
        } else {
            return FALSE;
        }
    }

    protected function addAdminAllowType($type)
    {
        $uid = $this->session->get('uid');
        if ($uid && $uid != 1) { // 普通用户添加项目权限
            $admin = \CyAdmin::findFirst($uid);
            if ($admin) {
                $roles = $admin->roles;
                $roles = json_decode($roles, true);
                $roles['allow_type'] .= ','.$type;
                $roles = json_encode($roles);
                $admin->roles = $roles;
                return $admin->save();
            }
        }

        return false;
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

    protected function isMobile(){

        // returns true if one of the specified mobile browsers is detected
        // 如果监测到是指定的浏览器之一则返回true

        $regex_match="/(nokia|iphone|android|motorola|^mot\-|softbank|foma|docomo|kddi|up\.browser|up\.link|";

        $regex_match.="htc|dopod|blazer|netfront|helio|hosin|huawei|novarra|CoolPad|webos|techfaith|palmsource|";

        $regex_match.="blackberry|alcatel|amoi|ktouch|nexian|samsung|^sam\-|s[cg]h|^lge|ericsson|philips|sagem|wellcom|bunjalloo|maui|";

        $regex_match.="symbian|smartphone|midp|wap|phone|windows ce|iemobile|^spice|^bird|^zte\-|longcos|pantech|gionee|^sie\-|portalmmm|";

        $regex_match.="jig\s browser|hiptop|^ucweb|^benq|haier|^lct|opera\s*mobi|opera\*mini|320x320|240x320|176x220";

        $regex_match.=")/i";

        // preg_match()方法功能为匹配字符，既第二个参数所含字符是否包含第一个参数所含字符，包含则返回1既true
        return (bool)preg_match($regex_match, strtolower($_SERVER['HTTP_USER_AGENT']));
    }

    protected function getRedis()
    {
        $redis = new Redis();
        $redis->connect('127.0.0.1');

        return $redis;
    }

    protected function ajaxSuccess($data)
    {
        $result = [
            'code' => 200,
            'data' => $data,
            'msg' => '',
        ];

        echo json_encode($result, JSON_UNESCAPED_UNICODE);die;
    }

    protected function ajaxError($msg)
    {
        $result = [
            'code' => 500,
            'data' => '',
            'msg' => $msg,
        ];

        echo json_encode($result, JSON_UNESCAPED_UNICODE);die;
    }
}