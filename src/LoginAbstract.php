<?php
/**
 * LoginAbstract 类定义了登录接口的公共方法
 * @author : weiyi <294287600@qq.com>
 * Licensed ( http://www.wycto.com )
 * Copyright (c) 2016~2099 http://www.wycto.com All rights reserved.
 */
namespace wycto\login;
abstract class LoginAbstract
{
    abstract function login();

    abstract function callback();
}
?>