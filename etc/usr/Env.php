<?php
/**
 * Created by JetBrains PhpStorm.
 * User: home
 * Date: 15-8-21
 * Time: 上午8:03
 * To change this template use File | Settings | File Templates.
 */

define("SYS_DIR", dirname(dirname(__FILE__)).'/');
define("SYS_ROUTE_FILE", SYS_DIR.'var/routers.data.php');
define("LIB_DIR", dirname(dirname(__FILE__)).'/sys/');
define("SYS_LIB", dirname(dirname(__FILE__)).'/sys/libs/');
define("APP_DIR", dirname(dirname(dirname(__FILE__))));
define("USR_DIR", APP_DIR.'/usr/');
define("USR_LIB", APP_DIR.'/usr/lib/');
if(!defined('APP_PATH')) define('APP_PATH','./usr');

define("ACCESS_KEY","YCF1X0&21@#@$1XY");
define("YCPF_VER", '1.1.4');
/**
 * 请求发生的时间
 */
define('REQ_TIME', time());
/**
 * 请求发生的日期
 */
define('REQ_DATETIME', date('Y-m-d H:i:s',time()));

include_once(LIB_DIR.'libs/Logger.php');
include_once(LIB_DIR.'libs/HashIds.php');
include_once(LIB_DIR.'Common.php');
include_once(LIB_DIR.'Exceptions.php');