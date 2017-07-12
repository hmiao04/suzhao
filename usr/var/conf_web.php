<?php
return array(
    /********** 系统相关配置 *********/
    'show_log' => false,
    'showerror' => true,
    'timezone' => 8, //时区

    'site' => array(
        'cdn' => '//res.tsuzhao.com',
        'url' => array(
            'site' => '//www.tsuzhao.com',
            'res' => '//res.tsuzhao.com/'
        )
    ),

    'charset' => 'utf-8',
    'session' => array(
        'id'=>'YCFS',
        'type'=>'mysql',
        'auto'=>true
    ),
    'default_page' => '/',
    /**模板配置*/
    'template' => array(
        'engine' => 'Twig',
        'tpl_config'=> array(
            'path' => APP_DIR.'/usr/tpl',
            'admin' => APP_DIR.'/usr/tpl/admin',
            'front_page' => APP_DIR.'/usr/tpl/front_page',
            'cache' => APP_DIR.'/usr/cache',
        )
    ),
    'app' => array(
        'member' => array(
            'reset' => '123456'
        ),
        'sms' => array(
            'limit_time' => SMS_LIMIT_TIME //时间限制（单位：分钟）
        )
    ),
    'error' => array(
        404 => APP_DIR.'/usr/var/error/404.php',
        403 => APP_DIR.'/usr/var/error/403.php',
        500 => APP_DIR.'/usr/var/error/50x.php'
    ),
    'upload' => array(
        'url'=>'//img.tsuzhao.com/',
        'driver'=>'AliOSS',
        'file_name'=>'crc32',
        'picture'=>array(
            'image/jpeg'=>'jpg',
            'image/pjpeg'=>'jpg',
            'image/png'=>'png',
            'image/x-png'=>'png',
            'image/gif'=>'gif',
            'image/bmp'=>'bmp'
        ),
        'AliOSS'=>array(
            'accessKey' => 'LTAIsJ5U3ZjhGKY3',
            'accessKeySecret' => 'GJtt7CxASmweGxZDEZjY8KKFzES6qu',
            'endpoint' => 'oss-cn-shenzhen-internal.aliyuncs.com',//oss-cn-shenzhen-internal.aliyuncs.com
            'bucket' => 'suzhao-core'
        ),
    ),
    /********** 数据库相关配置 *********/
    'db_config' => array(
        'runtime' => 'dev',
        'dev' => array(
            'server'=>'localhost',
            'database_type' => 'mysql',
            'username' => 'root',
            'password' => '123456',
            'port' => 3306,
            'charset' => 'utf8',
            'database_name' => 'suzhao',
            'db_prefix' => '',
            'option' => array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            )
        )
    )

);