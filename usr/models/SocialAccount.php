<?php
/**
 * Created by PhpStorm.
 * User: yancheng<cheng@love.xiaoyan.me>
 * Date: 16/11/27
 * Time: 上午3:48
 */

namespace Models;


class SocialAccount extends \Model
{

    public $id;
    public $open_id;
    public $mid;
    public $ext;
    public $provider;
    public $state;

    public function __construct()
    {
        $this->setPrimaryKey('id');
        $this->setTableName('yc_account_social');
    }
}