<?php
/**
 * File: Env.php:router
 * User: xiaoyan f@yanyunfeng.com
 * Date: 15-8-11
 * Time: 下午3:17
 * @Description
 */

function OSS()
{
    $KEY = 'OSS_OBJ';
    if (Cache::getInstance()->exists($KEY)) {
        return Cache::getInstance()->get($KEY);
    }
    $s = YCF::Instance()->getAppConfig('ali');
    $oss = new AliOSSLoader(
        $s['access_key'],
        $s['secret_key'],
        $s['endpoint'], false);
    Cache::getInstance()->set($KEY, $oss);
    return $oss;
}

function RedisClient()
{
    $KEY = 'Redis_OBJ';
    if (Cache::getInstance()->exists($KEY)) {
        return Cache::getInstance()->get($KEY);
    }
    $redis = new Redis();
    $redis->connect();
    Cache::getInstance()->set($KEY, $redis);
    return $redis;
}

function classLoader($class)
{
    $path = str_replace('\\', '/', $class);
    $dir = dirname(__FILE__) . '/';
    $autoLoadList = array(
        'OSS' => '',
        'DEFAULT' => $dir
    );
    $loadKey = 'DEFAULT';
    if (str_startwith('OSS', $class)) {
//        $loadKey = '';
    }
    $file = $autoLoadList[$loadKey] . $path . '.php';

    if (file_exists($file)) {
        require_once $autoLoadList[$loadKey] . $path . '.php';
    }
}

function instanceHashId()
{
    $KEY = 'HashIds_OBJ';
    if (Cache::getInstance()->exists($KEY)) {
        return Cache::getInstance()->get($KEY);
    }
    $hashId = new HashIds(ACCESS_KEY, 6);
    Cache::getInstance()->set($KEY, $hashId);
    return $hashId;
}

spl_autoload_register('classLoader');

//简单模拟事件驱动
class cEvent
{
    //退出登录
    public static function logout()
    {
        $_SESSION[USER_SES_KEY] = null;
        unset($_SESSION[USER_SES_KEY]);
        if (isAjax()) {
            ajaxResponse(403, '请先登录后在进行操作');
        }
        header('Location: ' . URL() . '/');
        exit;
    }
}