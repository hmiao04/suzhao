<?php

return array(
    /********** 系统相关配置 *********/
    'template' => array(
        'engine' => 'default',
        'tpl_config'=> array(
            'path' => '/usr/src',
            'cache' => '/usr/cache'
        )
    ),
    'memcache' => array(
        'server' => '127.0.0.1',
        'port' => 11211,
        'timeout' => 5
    ),
    'session' => array(
        'id'=>'YCFS',
        'type'=>'local',
        'auto'=>true
    ),
    'show_log' => true,
    'show_error' => false,
    'timezone' => 8, //时区

    'url_mode'=>'rewrite',
    'charset' => 'utf-8',
    'default_page' => '/',
    /********** 数据库相关配置 *********/
    'db_config' => array(
        'runtime' => 'dev',
        'dev' => array(
            'database_type' => 'mysql',
            'username' => '',
            'password' => '',
            'port' => 3306,
            'charset' => 'utf8',
            'database_name' => '',
            'db_prefix' => '',
            'option' => array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            )
        )
    ),
    'error' => array(
        404 => '/var/error/404.html',
        403 => '/var/error/403.html',
        500 => '/var/error/50x.html'
    )
);