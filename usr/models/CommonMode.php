<?php
/**
 * File: CommonCategory.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-03-30 22:04
 */

namespace Models;


class CommonMode extends \Model
{

    public $id;
    public $mode;
    public $status;
    public $type;

    public function __construct()
    {
        $this->setPrimaryKey('id');
        $this->setTableName('sz_common_mode');
    }
}