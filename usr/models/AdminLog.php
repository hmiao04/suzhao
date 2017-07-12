<?php
/**
 * Created by PhpStorm.
 * User: yancheng<cheng@love.xiaoyan.me>
 * Date: 16/10/25
 * Time: 上午10:08
 */

namespace Models;

class AdminLog extends \Model
{
    public $log_time;
    public $admin_id;
    public $log_type;
    public $log_state;
    public $log_data;
    public $ip_address;
    public $log_item;
    public $remarks;

    public function __construct()
    {
        $this->setTableName('yc_admin_log');
        $this->setPrimaryKey('');
    }
    public function getLogInfoByCondition($condition = null){
        if(null == $condition) $condition = $this->getNotNullArray();
        return $this->createQuery()
            ->table($this->getTableName().' (l)')
            ->field('l.*,a.account')
            ->join(array(
                '[><]yc_admin(a)'=>array('a.id'=>'l.admin_id')
            ))
            ->where($condition)->get();
    }
    public function getLogByCondition($condition,$limit,$order){
        return $this->createQuery()
            ->table($this->getTableName().' (l)')
            ->field('l.*,a.account')
            ->join(array(
                '[><]yc_admin(a)'=>array('a.id'=>'l.admin_id')
            ))
            ->where($condition)->limit($limit)->orderBy($order)->select();
    }
    public function getCountByCondition($condition = array()){
        return $this->createQuery()
            ->where($condition)
            ->join(array(
                '[><]yc_admin(a)'=>array('a.id'=>'l.admin_id')
            ))->count($this->getTableName().'(l)');
    }
}