<?php
/**
 * File: App.Config.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-05-01 14:32
 */
define('APP_NAME','速找网');
define('APP_MANAGE_NAME','/admin');

define('USER_SES_KEY', 'ycf_user');

define('ADMIN_SES_KEY', 'ycf_admin');
/**
 * 加密关键字
 */
define('SALT_KEY', 'ycs');

/**
 * 默认头像
 */
define('DEFAULT_AVATAR', 'avatar-default.jpg');

/**
 * 会员开通价格
 */
define('VIP_PRICE', 0.01);
/**
 * 默认开通时间
 */
define('VIP_MONTH', 12);
/**
 * 默认商家年费时常
 */
define('COMPANY_YEAR_MONTH', 12);
/**
 * 默认商家年费时常
 */
define('COMPANY_YEAR_PRICE', 0.01);
/**
 * 实地认证费用
 */
define('CERTIFICATION_PRICE', 388);

/**
 * 速找账单标题
 */
define('TASK_BILL_TITLE','速找佣金');

define('API_MAIN_KEY','LTAIsJ5U3ZjhGKY3');
define('API_MAIN_KEY_SEC','GJtt7CxASmweGxZDEZjY8KKFzES6qu');
define('SMS_ENDPOINT','http://1395435902991238.mns.cn-shenzhen.aliyuncs.com/');
define('SMS_SIGN_TEXT','速找科技');
/**
 * 主题名称
 */
define('SMS_TOPIC','sms.topic-cn-shenzhen');
/**
 * 身份验证
 */
define('SMS_IDENTIFICATION_VERIFY','SMS_63370280');
/**
 * 用户注册
 */
define('SMS_MEMBER_REGISTER','SMS_63370276');
/**
 * 时间限制（单位：分钟）
 */
define('SMS_LIMIT_TIME',10);

/**
 * 是否允许非会员查看图片
 */
define('ALLOW_VIEW_NO_LOGIN',false);