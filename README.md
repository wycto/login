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

