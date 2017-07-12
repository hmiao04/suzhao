<?php
/**
 * File: Task.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-03-05 16:29
 */

namespace Models;


class Task extends \Model
{

    public $id;
    public $find_title;
    public $member_id;
    public $wish_finish_time;
    public $paid_price;
    public $pay_status;
    public $bill_sn;
    public $home_image;
    public $main_image;
    public $find_brief;
    public $find_content;
    public $created_date;
    public $seq;
    public $status;

    public function __construct()
    {
        $this->setPrimaryKey('id');
        $this->setTableName('sz_fast_find');
    }

    public function info($condition = null)
    {
        if (null == $condition) $condition = $this->getNotNullArray();
        $data = DB()->field('t.*,m.name(member_name)')->table($this->getTableName() . '(t)')
            ->join([
                '[><]' . MemberModel::TableName(MemberModel::class) . '(m)' => ['t.member_id' => 'm.id']
            ])->where($condition)->get();
        if (!$data) return null;
        if ($data && is_array($data)) $this->setProperty($data);
        return $data;
    }

    public function getListByCondition($condition = null, $limit = null, $orderBy = null)
    {
        return DB()->field('t.*,m.name(member_name)')->table($this->getTableName() . '(t)')
            ->join([
                '[><]' . MemberModel::TableName(MemberModel::class) . '(m)' => ['t.member_id' => 'm.id']
            ])->where($condition)->orderBy($orderBy)->limit($limit)->select();
    }
    public function getRecommend($count  = 15){
        $data = $this->getQuery()->where([
            'status' => DataStatus::NORMAL
        ])->limit($count)->orderBy('seq DESC')->select();
        return $data;
    }

    public function getNewList($count  = 10){
        $data = $this->getQuery()->where([
            'status' => DataStatus::NORMAL
        ])->limit($count)->orderBy('id DESC')->select();
        return $data;
    }
}