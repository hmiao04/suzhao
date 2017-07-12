<?php
/**
 * File: BillType.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-05-01 11:14
 */

namespace Models;

class BillType
{
    static $TuanGou = 1;
    static $Task = 2;
    static $Vip = 3;
    static $CompanyFee = 4;
    static $Certification = 5;

    public static function AllType()
    {
        return [
            self::$TuanGou => 'TuanProcessor',
            self::$Task => 'TaskProcessor',
            self::$Vip => 'VipProcessor',
            self::$CompanyFee => 'BusinessJoinProcessor',
            self::$Certification => 'CertificationProcessor',
        ];
    }

    public static function GetProcessor($type)
    {
        $types = self::AllType();
        return isset($types[$type]) ? $types[$type] : null;
    }
}
