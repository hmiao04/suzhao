<?php
/**
 * File: GoodsCategory.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-03-06 23:03
 */

namespace models;


class GoodsCategory extends \Model
{
    public $id;
    public $parent_id;
    public $cate_name;
    public $meta_keywords;
    public $meta_description;
    public $seq;
    public $status;

    public function __construct()
    {
        $this->setPrimaryKey('id');
        $this->setTableName('sz_goods_cate');
    }
}