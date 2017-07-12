<?php
/**
 * Created by PhpStorm.
 * User: yancheng<cheng@love.xiaoyan.me>
 * Date: 16/10/25
 * Time: 上午11:39
 */

namespace Models;


class LogAction
{
    const LOGIN = 'login';
    const UPDATE = 'update';
    const DELETE = 'delete';
    const INSERT = 'insert';
    const QUERY = 'query';
    const OTHER = 'other';
    const UNKNOWN = 'unknown';
    private static $format = array(
        self::LOGIN => '登录',
        self::UPDATE => '修改',
        self::DELETE => '删除',
        self::INSERT => '新增',
        self::QUERY => '查询',
        self::OTHER => '其他',
        self::UNKNOWN => '未知'
    );

    public static function AllAction()
    {
        return self::$format;
    }

    public static function format($key)
    {
        $key = isset(self::$format[$key]) ? $key : self::UNKNOWN;
        return self::$format[$key];
    }
}