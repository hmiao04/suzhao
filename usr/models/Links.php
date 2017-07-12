<?php
/**
 * File: Links.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-03-04 4:18
 */

namespace Models;


class Links extends \Model
{
    public $id;
    public $seq;
    public $category_id;
    public $url;
    public $name;
    public $image;
    public $target;
    public $visible;
    public $updated;
    public $remark;
    public $status;

    public function __construct()
    {
        $this->setPrimaryKey('id');
        $this->setTableName('xkl_links');
    }
}