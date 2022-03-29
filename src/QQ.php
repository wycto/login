<?php
/**
 * QQ登录类库
 * @author : weiyi <294287600@qq.com>
 * Licensed ( http://www.wycto.com )
 * Copyright (c) 2016~2099 http://www.wycto.com All rights reserved.
 */
namespace wycto\login;

class QQ extends LoginAbstract
{

    const VERSION = "2.0";

    const GET_AUTH_CODE_URL = "https://graph.qq.com/oauth2.0/authorize";

    const GET_ACCESS_TOKEN_URL = "https://graph.qq.com/oauth2.0/token";

    const GET_OPENID_URL = "https://graph.qq.com/oauth2.0/me";

    private $_config = array(
        // 开发平台获取
        'app_id' => '101363004',
        // 开发平台获取
        'app_key' => '5023acb1767d931a664e995b89e5de07',
        // 回掉地址，需要在腾讯开发平台填写
        'callback' => "/index/user/qqcallback",
        'scope' => 'get_user_info',
        'expires_in' => 7775000,
        'display' => '',/*仅PC网站接入时使用。 用于展示的样式。不传则默认展示为PC下的样式。如果传入“mobile”，则展示为mobile端下的样式。*/
        'g_ut' => '',/*仅WAP网站接入时使用。QQ登录页面版本（1：wml版本； 2：xhtml版本），默认值为1。*/
        'framework' => ''//框架，如tp
    );

    // 全局唯一实例
    private static $_app = null;

    private function __construct($config)
    {
        $this->_config = array_replace_recursive($this->_config, $config);
    }

    static function init($config)
    {
        if (null == self::$_app) {
            self::$_app = new QQ($config);
        }

        return self::$_app;
    }

    /**
     * 登录方法，交换code
     * 可传递回调方法url，不传则实用config里面的回调地址
     */
    function login($callback = null)
    {
        $appid = $this->_config['app_id'];
        if ($callback == null) {
            $callback = $this->getCallback($this->_config['callback']);
        } else {
            $callback = $this->getCallback($callback);
        }

        $scope = $this->_config['scope'];

        // -------生成唯一随机串防CSRF攻击
        $state = md5(uniqid(rand(), TRUE));
        if($this->_config['framework']=='tp'){
            session('qquser.state', $state);
        }else{
            $_SESSION['qquser.state'] = $state;
        }

        // -------构造请求参数列表
        $keysArr = array(
            "response_type" => "code",
            "client_id" => $appid,
            "redirect_uri" => $callback,
            "state" => $state,
            "scope" => $scope
        );

        $login_url = $this->combineURL(self::GET_AUTH_CODE_URL, $keysArr);

        header("Location:$login_url");
        exit();
    }

    /**
     * 获取access_token
     * @return Ambigous <>
     */
    function getAccessToken(){
        // --------验证state防止CSRF攻击
        if($this->_config['framework']=='tp'){
            session('qquser.state');
        }else{
            $state = $_SESSION['qquser.state'];
        }

        if ($_GET['state'] != $state) {
            return $this->showError('0', "验证过期，请重新操作");
            exit();
        }

        // 获取access_token
        $keysArr = array(
            "grant_type" => "authorization_code",
            "client_id" => $this->_config['app_id'],
            "redirect_uri" => urlencode($this->getCallback($this->_config['callback'])),
            "client_secret" => $this->_config['app_key'],
            "code" => $_GET['code']
        );
        $token_url = $this->combineURL(self::GET_ACCESS_TOKEN_URL, $keysArr);
        $response = $this->get_contents($token_url);

        if (strpos($response, "callback") !== false) {

            $lpos = strpos($response, "(");
            $rpos = strrpos($response, ")");
            $response = substr($response, $lpos + 1, $rpos - $lpos - 1);
            $msg = json_decode($response, true);

            if (isset($msg['error'])) {
                return $this->showError($msg['error'], $msg['error_description']);
                exit();
            }
        }

        $params = array();
        parse_str($response, $params);
        if($this->_config['framework']=='tp'){
            session('qquser.access_token',$params["access_token"]);
        }else{
            $_SESSION['qquser.access_token'] = $params["access_token"];
        }

        return $params["access_token"];
    }

    /**
     * 获取openid
     * @return unknown
     */
    function getOpenid()
    {
        // 获取openid
        if($this->_config['framework']=='tp'){
            $access_token = session('qquser.access_token');
        }else{
            $access_token = $_SESSION['qquser.access_token'];
        }
        $keysArr = array(
            "access_token" => $access_token
        );

        $graph_url = $this->combineURL(self::GET_OPENID_URL, $keysArr);
        $response = $this->get_contents($graph_url);

        // --------检测错误是否发生
        if (strpos($response, "callback") !== false) {

            $lpos = strpos($response, "(");
            $rpos = strrpos($response, ")");
            $response = substr($response, $lpos + 1, $rpos - $lpos - 1);
        }

        $user = json_decode($response, true);
        if (isset($user['error'])) {
            return $this->showError($user['error'], $user['error_description']);
            exit();
        }

        if($this->_config['framework']=='tp'){
            session('qquser.openid',$user['openid']);
        }else{
            $_SESSION['qquser.openid'] = $user['openid'];
        }

        return $user['openid'];
    }

/**************************************************************************/

    function callback()
    {
        if($this->_config['framework']=='tp'){
            $state = session('qquser.state');
        }else{
            $state = $_SESSION['qquser.state'];
        }

        // --------验证state防止CSRF攻击
        if ($_GET['state'] != $state) {
            return array(
                'status' => 0,
                'message' => "验证过期，请重新操作"
            );
            exit();
        }

        // -------请求参数列表
        $keysArr = array(
            "grant_type" => "authorization_code",
            "client_id" => $this->_config['app_id'],
            "redirect_uri" => urlencode($this->getCallback($this->_config['callback'])),
            "client_secret" => $this->_config['app_key'],
            "code" => $_GET['code']
        );

        // ------构造请求access_token的url
        $token_url = $this->combineURL(self::GET_ACCESS_TOKEN_URL, $keysArr);
        $response = $this->get_contents($token_url);

        if (strpos($response, "callback") !== false) {

            $lpos = strpos($response, "(");
            $rpos = strrpos($response, ")");
            $response = substr($response, $lpos + 1, $rpos - $lpos - 1);
            $msg = json_decode($response, true);

            if (isset($msg['error'])) {
                return $this->showError($msg['error'], $msg['error_description']);
                exit();
            }
        }

        $params = array();
        parse_str($response, $params);

        return $params;
    }

    function getUserInfo()
    {

        // --------验证state防止CSRF攻击
        if($this->_config['framework']=='tp'){
            $state = session('qquser.state');
        }else{
            $state = $_SESSION['qquser.state'];
        }

        if ($_GET['state'] != $state) {
            return $this->showError('0', "验证过期，请重新操作");
            exit();
        }

        // 获取access_token
        $keysArr = array(
            "grant_type" => "authorization_code",
            "client_id" => $this->_config['app_id'],
            "redirect_uri" => urlencode($this->getCallback($this->_config['callback'])),
            "client_secret" => $this->_config['app_key'],
            "code" => $_GET['code']
        );
        $token_url = $this->combineURL(self::GET_ACCESS_TOKEN_URL, $keysArr);
        $response = $this->get_contents($token_url);

        if (strpos($response, "callback") !== false) {

            $lpos = strpos($response, "(");
            $rpos = strrpos($response, ")");
            $response = substr($response, $lpos + 1, $rpos - $lpos - 1);
            $msg = json_decode($response, true);

            if (isset($msg['error'])) {
                return $this->showError($msg['error'], $msg['error_description']);
                exit();
            }
        }

        $params = array();
        parse_str($response, $params);

        // 获取openid
        $keysArr = array(
            "access_token" => $params["access_token"]
        );

        $graph_url = $this->combineURL(self::GET_OPENID_URL, $keysArr);
        $response = $this->get_contents($graph_url);

        // --------检测错误是否发生
        if (strpos($response, "callback") !== false) {

            $lpos = strpos($response, "(");
            $rpos = strrpos($response, ")");
            $response = substr($response, $lpos + 1, $rpos - $lpos - 1);
        }

        $user = json_decode($response, true);
        if (isset($user['error'])) {
            return $this->showError($user['error'], $user['error_description']);
            exit();
        }

        // 获取用户信息
        $keysArr = array(
            "access_token" => $params["access_token"],
            "oauth_consumer_key" => $this->_config['app_id'],
            "openid" => $user['openid']
        );
        $baseURL = "https://graph.qq.com/user/get_user_info";
        $url = $this->combineURL($baseURL, $keysArr);
        $response = $this->get_contents($url);
        $response = json_decode($response, true);
        return array_merge($response, $user, $params);
    }

    /**
     * 获取回调地址
     *
     * @param unknown_type $callback
     * @return multitype:string number
     */
    function getCallback($callback)
    {
        if (strpos($callback, 'http://') === false && strpos($callback, 'https://') === false) {
            $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' :'http://';
            $this->_config['callback'] = $http_type . $_SERVER['HTTP_HOST'] . $callback;
        }else {
            $this->_config['callback'] = $callback;
        }

        return $this->_config['callback'];
    }

    /**
     * combineURL
     * 拼接url
     *
     * @param string $baseURL
     *            基于的url
     * @param array $keysArr
     *            参数列表数组
     * @return string 返回拼接的url
     */
    public function combineURL($baseURL, $keysArr)
    {
        $combined = $baseURL . "?";
        $valueArr = array();

        foreach ($keysArr as $key => $val) {
            $valueArr[] = "$key=$val";
        }

        $keyStr = implode("&", $valueArr);
        $combined .= ($keyStr);

        return $combined;
    }

    /**
     * get_contents
     * 服务器通过get请求获得内容
     *
     * @param string $url
     *            请求的url,拼接后的
     * @return string 请求返回的内容
     */
    public function get_contents($url)
    {
        if (ini_get("allow_url_fopen") == "1") {
            $response = file_get_contents($url);
        } else {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_URL, $url);
            $response = curl_exec($ch);
            curl_close($ch);
        }

        // -------请求为空
        if (empty($response)) {
            return $this->showError("50001", "<h2>可能是服务器无法请求https协议</h2>可能未开启curl支持,请尝试开启curl支持，重启web服务器，如果问题仍未解决，请联系我们");
        }

        return $response;
    }

    /**
     * get
     * get方式请求资源
     *
     * @param string $url
     *            基于的baseUrl
     * @param array $keysArr
     *            参数列表数组
     * @return string 返回的资源内容
     */
    public function get($url, $keysArr)
    {
        $combined = $this->combineURL($url, $keysArr);
        return $this->get_contents($combined);
    }

    /**
     * post
     * post方式请求资源
     *
     * @param string $url
     *            基于的baseUrl
     * @param array $keysArr
     *            请求的参数列表
     * @param int $flag
     *            标志位
     * @return string 返回的资源内容
     */
    public function post($url, $keysArr, $flag = 0)
    {
        $ch = curl_init();
        if (! $flag)
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $keysArr);
        curl_setopt($ch, CURLOPT_URL, $url);
        $ret = curl_exec($ch);

        curl_close($ch);
        return $ret;
    }

    /**
     * showError
     * 显示错误信息
     *
     * @param int $code
     *            错误代码
     * @param string $description
     *            描述信息（可选）
     */
    public function showError($code, $description = '$')
    {
        echo "<meta charset=\"UTF-8\">";
        echo "<h3>error:</h3>$code";
        echo "<h3>msg  :</h3>$description";
        exit();
    }
}
?>