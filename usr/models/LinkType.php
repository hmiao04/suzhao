<?php
/**
 * File: LinkType.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-03-04 17:54
 */

namespace Models;


class LinkType
{
    public static $Customer = 1;
    public static $FriendLink = 2;
    public static $ALLType = [
        1 => '经典客户',
        2 => '友情链接'
    ];

    public static function Text($id)
    {
        return isset(self::$ALLType[$id]) ? self::$ALLType[$id] : '未知';
    }
}