<?php
/**
 * Created by PhpStorm.
 * User: yancheng<cheng@love.xiaoyan.me>
 * Date: 16/10/31
 * Time: 下午9:26
 */

namespace Models;

class ResourceType
{
    const MENU = 'm';
    const URL = 'u';
    const BUTTON = 'b';
    const OTHER = 'o';
    private static $format = array(
        self::MENU => '菜单',
        self::URL => '地址',
        self::BUTTON => '按钮',
        self::OTHER => '其他'
    );

    public static function getAllTypes()
    {
        return self::$format;
    }

    public static function format($key)
    {
        $key = isset(self::$format[$key]) ? $key : self::OTHER;
        return self::$format[$key];
    }
}