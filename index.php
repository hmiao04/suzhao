<?php
/**
 * Created by JetBrains PhpStorm.
 * To change this template use File | Settings | File Templates.
 */
header('Access-Control-Allow-Origin:*');
header('Access-Control-Allow-Headers: X-Requested-With,X_Requested_With');
require 'usr/var/app_config.php';
require 'usr/var/env.php';
require 'etc/YCF.php';


Logger::setPriority(Logger::$LEVEL_SYS);

YCF::Loader()->loadUserLib(array(
    './usr/lib/Env.php',
    './usr/lib/RedisClient.php',
    './usr/lib/BaseController.php'
));

YCFCore::getInstance()
    ->setRunMode(RunMode::DEV) // must be first
    ->init('./usr/var/conf.php')
    ->dispatch();
?>