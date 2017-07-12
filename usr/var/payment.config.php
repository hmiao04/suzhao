<?php
/**
 * File: payment.config.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-02-18 18:17
 */
return [
    "alipay" => [
        'partner' => '2088712675008981',// 合作者身份ID:填写支付宝账号信息
        'md5_key' => 'ntmwd58m7gckglvitk181cus63a7cgbv',// MD5密钥
        'rsa_private_key' => APP_DIR . '/usr/var/rsa_private_key.pem', // RSA私钥
        'rsa_public_key' => APP_DIR . '/usr/var/rsa_public_key.pem', // RSA公钥 rsaAliPubPath
        "notify_url" => URL(1) . '/pay/notify.html', // 服务器异步通知URI,建议使用https
        "return_url" => URL(1) . '/pay/return.html', // 页面跳转同步通知页面路径(支付成功之后的跳转路径),仅在即时到账接口有效.建议使用https
        "time_expire" => '14',//	超时时间:单位默认为分钟

        // 转款接口，必须配置以下两项
//    'account'   => 'xxxxx@126.com',
//    'account_name' => 'xxxxx',
        'sign_type' => 'MD5',// 默认方式    目前支持:RSA   MD5`
    ]
];