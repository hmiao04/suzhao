<?php
/**
 * File: CompanyType.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-05-01 5:52
 */

namespace Models;


class CompanyType
{
    const Factory = 1;
    const Supplier = 2;
    const Store = 3;
    const Designer = 4;
    const Other = 10;

    public static $AllType = array(
        self::Factory => '生产厂家',
        self::Supplier => '原材料商',
        self::Store => '实体店',
        self::Designer => '设计师',
        self::Other => '其他'
    );

    public static function Format($typeId){
        $typeId = $typeId && isset(self::$AllType[$typeId]) ? $typeId : self::Other;
        return self::$AllType[$typeId];
    }
}