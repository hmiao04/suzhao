<?php
/**
 * Created by PhpStorm.
 * User: yancheng<cheng@love.xiaoyan.me>
 * Date: 16/10/31
 * Time: 上午11:51
 */

namespace Models;


class AdminResources extends \Model
{
    public $id;
    public $res_name;
    public $res_id;
    public $res_url;
    public $res_icon;
    public $parent_id;
    public $create_time;
    public $sort;
    public $type;
    public $state;


    public function __construct()
    {
        $this->setTableName('yc_admin_resources');
        $this->setPrimaryKey('id');
    }
}