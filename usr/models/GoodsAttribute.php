<?php
/**
 * File: GoodsAttribute.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-03-06 23:03
 */

namespace models;


class GoodsAttribute extends \Model
{

    public $goods_id;
    public $attr_id;
    public $value;

    public function __construct()
    {
        $this->setPrimaryKey('id');
        $this->setTableName('sz_goods_attr');
    }
}