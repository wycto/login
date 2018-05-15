<?php
/**
 * 登录工厂类
 * @author : weiyi <294287600@qq.com>
 * Licensed ( http://www.wycto.com )
 * Copyright (c) 2016~2099 http://www.wycto.com All rights reserved.
 */
namespace wycto\login;
class Login
{
	const QQ = "qq";

	const WEIXIN = "weixin";

	const WEIBO = "weibo";

	static function getApp($login = self::QQ, array $config = array())
	{
		if (strtolower($login) == self::QQ) {
			$app = QQ::init($config);
		}
		else if (strtolower($login) == self::WEIXIN) {
			$app = WeiXin::init($config);
		}
		else if (strtolower($login) == self::WEIBO) {
			$app = WeiBo::init($config);
		}
		else {
			return false;
		}

		return $app;
	}
}