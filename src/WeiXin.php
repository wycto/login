<?php
/**
 * 微信登录类库
 * @author : weiyi <294287600@qq.com>
 * Licensed ( http://www.wycto.com )
 * Copyright (c) 2016~2099 http://www.wycto.com All rights reserved.
 */
namespace wycto\login;
class WeiXin extends LoginAbstract
{
    private $_config = array(
        //开发平台获取
        'app_id' => 'wx587351cc9b2fbca4',
        //开发平台获取
        'app_secret' => '382b75b03fa71c6691555c65037598dc',
        //回掉地址，需要在腾讯开发平台填写
        'callback' => "/default/user/wxcallback",
        //终端类型
        'terminal' => "pc",
        //手机端回调地址
        'callback_wx' => "/wap/user/wxcallback",
        //订阅号appid
        'app_id_d' => 'wxae47b941e485a3a8',
        //订阅号app_secret
        'app_secret_d' => '3ca2f30daa500012ac1b0d126e83eefe',
        'framework' => ''//框架，如tp
    );

    // 全局唯一实例
    private static $_app = null;

    private function __construct($config) {
        $this->_config = array_replace_recursive($this->_config,$config);
    }

    static function init($config) {

        if (null == self::$_app) {
            self::$_app = new Weixin($config);
        }
        return self::$_app;
    }

    function login($callback=null){

        $state  = md5(uniqid(rand(), TRUE));
        //Helper_session::set('wx_state', $state); //存到SESSION
        if($this->_config['framework']=='tp'){
            session('wx_state', $state);
        }else{
            $_SESSION['wx_state'] = $state;//存到SESSION
        }


        if($callback!=null){
            $callback = $this->getCallback($callback);
        }else{
            $callback = $this->getCallback($this->_config['callback']);
        }

        $callback = urlencode($callback);
        $wxurl = "https://open.weixin.qq.com/connect/qrconnect?appid=".$this->_config['app_id']."&redirect_uri={$callback}&response_type=code&scope=snsapi_login&state={$state}#wechat_redirect";
        header("Location: $wxurl");
    }

    function authorize($callback=null){

        $AppID = $this->_config['app_id_d'];
        $AppSecret = $this->_config['app_secret_d'];

        if($callback!=null){
            $callback = $this->getCallback($callback);
        }else{
            $callback = $this->getCallback($this->_config['callback_wx']);
        }

        $callback = urlEncode($callback);

        $state = md5(uniqid(rand(), TRUE));
        //Helper_session::set('wap_wx_status', $state);
        if($this->_config['framework']=='tp'){
            session('wap_wx_status', $state);
        }else{
            $_SESSION['wap_wx_status'] = $state;//存到SESSION
        }

        $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . $AppID . '&redirect_uri=' . $callback . '&response_type=code&scope=snsapi_userinfo&state=' . $state . '#wechat_redirect';
        header("Location: $url");
    }

    /**
     * 验证，PC端扫描和手机端登录公用
     * (non-PHPdoc)
     * @see LoginAbstract::auth()
     */
    function auth(){
        if($this->_config['framework']=='tp'){
            $wx_state = session('wx_state');
            $wap_wx_status = session('wap_wx_status');
        }else{
            $wx_state = $_SESSION['wx_state'];
            $wap_wx_status = $_SESSION['wap_wx_status'];
        }

        if($_GET['state']!=$wx_state&&$_GET['state']!=$wap_wx_status){
            //return $this->_redirectMessage('登录失败', '登录超时！请稍后重试，错误代码5001', url("user/login"), 'fail', 3);
            return array('errcode'=>'5001','errmsg'=>'已经超时，请稍后重试，错误代码5001');
            exit;
        }else{
            //Helper_session::set('wx_state', null);
            //Helper_session::set('wap_wx_status', null);
            if($this->_config['framework']=='tp'){
                session('wx_state',null);
                session('wap_wx_status',null);
            }else{
                unset($_SESSION['wx_state']);
                unset($_SESSION['wap_wx_status']);
            }
        }

        if($this->_config['terminal']=="pc"){
            $AppID = $this->_config['app_id'];
            $AppSecret = $this->_config['app_secret'];
        }else{
            $AppID = $this->_config['app_id_d'];
            $AppSecret = $this->_config['app_secret_d'];
        }

        $code = $_GET['code'];

        $url='https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$AppID.'&secret='.$AppSecret.'&code='.$code.'&grant_type=authorization_code';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_URL, $url);
        $json =  curl_exec($ch);
        curl_close($ch);

        $arr=json_decode($json,1);
        return $arr;
    }

    function callback(){}

    function getUserInfo(){

        $oauth = $this->auth();
        //dump($oauth);exit();
        $url='https://api.weixin.qq.com/sns/userinfo?access_token='.$oauth['access_token'].'&openid='.$oauth['openid'].'&lang=zh_CN';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_URL, $url);
        $json =  curl_exec($ch);
        curl_close($ch);
        $info=json_decode($json,1);//得到 用户资料

        return $info;
    }

    /**
     * 获取回调地址
     * @param unknown_type $callback
     * @return multitype:string number
     */
    function getCallback($callback){
        if(strpos($callback,'http://')===false){
            $this->_config['callback'] = "http://" . $_SERVER['HTTP_HOST'] . $callback;
        }elseif(strpos($callback,'https://')===false){
            $this->_config['callback'] = "https://" . $_SERVER['HTTP_HOST'] . $callback;
        }else{
            $this->_config['callback'] = $callback;
        }
        return $this->_config['callback'];
    }
}
?>
