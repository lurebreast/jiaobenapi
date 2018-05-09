<?php

abstract class ControllerBase extends \Phalcon\Mvc\Controller
{
    public function beforeExecuteRoute() {
        \Phalcon\Tag::setTitleSeparator('·');
        \Phalcon\Tag::setTitle('米多');
        $this->view->setTemplateAfter('after');
        $this->view->setVar('config', $this->config);
    }
    public function afterExecuteRoute() {
        $this->assets->addCss('css/bootstrap.min.css')
            ->addCss('css/mdadmin.css?v=1');
        $this->assets->addJs('js/jquery.js')
            ->addJs('js/bootstrap.min.js');
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

}