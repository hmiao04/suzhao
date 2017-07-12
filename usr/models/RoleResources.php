<?php
/**
 * File: RoleResources.php:newsys
 * User: xiaoyan f@yanyunfeng.com
 * Date: 16-11-8
 * Time: 下午9:27
 * @Description
 */

namespace Models;


class RoleResources extends \Model {

    public $id;
    public $role_name;
    public $type;
    public $res_id;
    public $alias;
    public $remark;
    public $is_sys;
    public $addition;
    public $level;
    public $state;

    public function __construct()
    {
        $this->setPrimaryKey('id');
        $this->setTableName('yc_admin_role');
    }
}