<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * Typecho互联；暂只支持QQ、微博
 * 
 * @package TeConnect 
 * @author 绛木子
 * @version 1.0
 * @link http://lixianhua.com
 * 
 * SDK使用了 http://git.oschina.net/piscdong 发布的sdk
 */
class TeConnect_Plugin implements Typecho_Plugin_Interface
{
    public static $session_key = '__typecho_connect_auth';
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
		//帐号登录
        Helper::addAction('teconnect_action', 'TeConnect_Action');
    }

    public static function hasConnectLogin() {
        if (!isset($_SESSION)) session_start();
        return isset($_SESSION[static::$session_key]);
    }

    public static function nickname() {
        if (!static::hasConnectLogin()) {
            return '';
        }
        return $_SESSION[static::$session_key]['nickname'];
    }
    
    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate(){
        Helper::removeAction('teconnect_action');
	}
    
    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        /** 互联配置 */
        $connect = new Typecho_Widget_Helper_Form_Element_Textarea('connect', NULL, NULL, _t('互联配置'), _t('一行一个配置，格式为：‘type:appid,appkey,title’，如：‘qq:12345678,asdiladaldns,腾讯QQ’'));
		$form->addInput($connect);
    }
    
    /**
     * 个人用户的配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}
	
    /**
	 * 安装数据库
	 */
	public static function installDb(){

	}
	
	public static function show($format='<a href="{url}"><i class="fa fa-{type}"></i> {title}</a>'){
		$list = self::options();
		if(empty($list)) return '';
		$html = '';
		foreach($list as $type=>$v){
			$url = Typecho_Common::url('/action/teconnect_action?do=oauth&type='.$type, Typecho_Widget::Widget('Widget_Options')->index);
			$html .= str_replace(
					array('{type}','{title}','{url}'),
					array($type,$v['title'],$url),$format);
		}
		echo $html;
	}

	public static function logout_url() {
        $url = Typecho_Common::url('/action/teconnect_action?do=logout', Typecho_Widget::Widget('Widget_Options')->index);
        return $url;
    }
	
	public static function options($type=''){
		static $options = array();
		if(empty($options)){
			$connect = Typecho_Widget::Widget('Widget_Options')->plugin('TeConnect')->connect;
			$connect = preg_split('/[;\r\n]+/', trim($connect, ",;\r\n"));
			foreach($connect as $v){
				$v = explode(':',$v);
				if(isset($v[1])){
					$tmp = explode(',',$v[1]);
				}
				if(isset($tmp[1])){
					$options[$v[0]] = array(
						'id'=>trim($tmp[0]),
						'key'=>trim($tmp[1]),
						'title'=>isset($tmp[2]) ? $tmp[2] : $v[0]
						);
				}
			}
		}
		return empty($type) ? $options : (isset($options[$type]) ? $options[$type] : array());
	}
}
