<?php
/**
 * File: BillStatus.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-02-18 19:45
 */

namespace Models;


class BillData
{
    //支付状态    1：已支付   0：未支付
    public static $STATUS_LIST = [
        0 => '未支付',
        1 => '已支付'
    ];
    public static $STATUS_UN_PAY = 0;
    public static $STATUS_PAYED = 1;

    public static function StatusText($status)
    {
        return self::$STATUS_LIST[$status];
    }
}