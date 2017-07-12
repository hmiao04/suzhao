<?php
/**
 * File: LocalAccount.php:suixinlv
 * User: xiaoyan f@yanyunfeng.com
 * Date: 16-11-17
 * Time: 下午11:21
 * @Description
 */

namespace Models;


class LocalAccount extends \Model {

    public $id;
    public $mid;
    public $login_account;
    public $login_pass;
    public $salt;
    public $status;

    public function __construct()
    {
        $this->setPrimaryKey('id');
        $this->setTableName('yc_account_local');
    }
}