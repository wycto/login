## PHP第三方登录类库

### 安装

~~~
composer require wycto/login
~~~


#### 类库列表，持续更新
~~~
QQ登录

微信登录

微博登录
~~~


### 使用方法

~~~
//登录方法
$name = 'qq',$config = array()
$login = \login\Login::getApp($name,$config);
$login->login();

//登录回调
$name = 'qq',$config = array()
$login = \login\Login::getApp($name,$config);
$login->getUserInfo();
~~~

### QQ登录示例：
~~~
$name = 'qq';
$config = array(
  // 开发平台获取
  'app_id' => '101363004',
  // 开发平台获取
  'app_key' => '5023acb1767d931a664e995b89e5de07',
  // 回掉地址，需要在腾讯开发平台填写,带域名
  'callback' => "/index/user/qqcallback",
  'scope' => 'get_user_info',//不用改
  'expires_in' => 7775000,//过期时间，不用改
  'display' => '',/*仅PC网站接入时使用。 用于展示的样式。不传则默认展示为PC下的样式。如果传入“mobile”，则展示为mobile端下的样式。*/
  'g_ut' => ''/*仅WAP网站接入时使用。QQ登录页面版本（1：wml版本； 2：xhtml版本），默认值为1。*/
);

/**
 * QQ登录
 */
function qqLoginAction()
{
    // qq登录
    $this->_set_referer();
    $login = login();
    $login->login();
}

/**
 * QQ登录回调
 */
function qqCallbackAction()
{
    $login = login();
    // 获取用户信息
    $userinfo = $login->getUserInfo();
    if (! isset($userinfo['openid']) || empty($userinfo['openid'])) {
        return $this->redirect(url("index/index/index"));
    }
    // 查询是否存在
    $user = User::get(array(
        'qq_openid' => $userinfo['openid']
    ));
    if ($user) {
        // 账号存在去登录
        return $this->_toLogin($user, false);
    } else {
        // 新注册该用户
        Session::set("qq_userinfo", $userinfo);
        return $this->redirect(url("index/user/newAccount"));
    }
}
~~~

