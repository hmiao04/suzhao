<?php

/**
 * File: Session.php:router
 * User: xiaoyan f@yanyunfeng.com
 * Date: 15-8-11
 * Time: 上午10:28
 * @Description
 */
class Session extends YC
{
    private static $obj;

    public static function getSession()
    {
        if (self::$obj != null) {
            return self::$obj;
        }
        self::$obj = new Session();
        return self::$obj;
    }

    public function get()
    {

    }

    public function save($key, $data)
    {
        return $this;
    }
}