<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
class TeConnect_Action extends Typecho_Widget implements Widget_Interface_Do {
    public static $session_key = '__typecho_connect_auth';
    private $auth = [];
    /**
     * @inheritDoc
     */
    public function action()
    {
        $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'javascript:history.back(-1);';
        if ($this->request->is('do=oauth')) {
            $referer = $this->oauth();
        } else if ($this->request->is('do=oauth_callback')) {
            $referer = $this->callback();
        } else if ($this->request->is('do=logout')) {
            $this->logout();
        }

        $this->response->redirect($referer);
    }

    public function oauth() {
        $type = $this->request->get('type');
        $next = $this->request->getReferer();

        if(is_null($type)){
            $this->widget('Widget_Notice')->set(array('请选择登录方式!'),'error');
            $this->response->goBack();
        }
        $options = TeConnect_Plugin::options();
        $type = strtolower($type);
        //不在开启的登陆方式内直接返回
        if(!isset($options[$type])){
            $this->widget('Widget_Notice')->set(array('暂不支持该登录方式!'),'error');
            $this->response->goBack();
        }
        $params = http_build_query(['type' => $type, 'next' => $next, 'do'=> 'oauth_callback']);
        $callback_url = Typecho_Common::url('/action/teconnect_action?'.$params, Typecho_Widget::Widget('Widget_Options')->index);

        require_once 'Connect.php';
        return Connect::getLoginUrl($type, $callback_url);
    }

    public function callback(){
        if(!isset($_SESSION)){
            session_start();
            if(isset($_SESSION[static::$session_key]))
                $this->auth = $_SESSION[static::$session_key];
        }


        $options = TeConnect_Plugin::options();
        $next_url = $this->request->get('next', Typecho_Widget::Widget('Widget_Options')->index);

        if(empty($this->auth)){

            $this->auth['type'] = $this->request->get('type','');
            $this->auth['code'] = $this->request->get('code','');

            //不在开启的登陆方式内直接返回
            if(!isset($options[$this->auth['type']])){
                $this->response->redirect(Typecho_Widget::Widget('Widget_Options')->index);
            }
            if(empty($this->auth['code'])){
                $this->response->redirect(Typecho_Widget::Widget('Widget_Options')->index);
            }

            $callback_url = Typecho_Common::url('/action/teconnect_action?do=oauth_callback&type='.$this->auth['type'], Typecho_Widget::Widget('Widget_Options')->index);

            $this->auth['openid'] = '';

            require_once 'Connect.php';
            //换取access_token
            $this->auth['token'] = Connect::getToken($this->auth['type'], $callback_url, $this->auth['code']);

            if(empty($this->auth['token'])){
                $this->response->redirect(Typecho_Widget::Widget('Widget_Options')->index);
            }

            //获取openid
            $this->auth['openid'] = Connect::getOpenId($this->auth['type']);

            if(empty($this->auth['openid'])){
                $this->response->redirect(Typecho_Widget::Widget('Widget_Options')->index);
            }

            $this->auth['nickname'] = Connect::getNickName($this->auth['type'],$this->auth['openid']);
        }


        $custom = Typecho_Widget::Widget('Widget_Options')->plugin('TeConnect')->custom;

        $dataStruct = array(
            'screenName'=>  $this->auth['nickname'],
            'created'   =>  $this->options->gmtTime,
            'group'     =>  'subscriber'
        );


        $_SESSION[static::$session_key] = $this->auth;
        return $next_url;

    }

    public function logout() {
        if (!isset($_SESSION)) session_start();
        unset($_SESSION[static::$session_key]);
    }


}