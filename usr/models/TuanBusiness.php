<?php
/**
 * File: TuanBussiness.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-04-11 16:04
 */

namespace Models;


class TuanBusiness extends \Model
{
    public $id;
    public $tuan_id;
    public $member_id;
    public $money;
    public $remark;
    public $status;

    public function __construct()
    {
        $this->setPrimaryKey('id');
        $this->setTableName('sz_group_buy_business');
    }

    public function getRecordList($condition = null,$limit = null,$orderBy = null){
        return DB()->field('t.*,m.name(member_name)')->table($this->getTableName() . '(t)')
            ->join([
                '[><]' . $this->getTableName(MemberModel::class) . '(m)' => ['t.member_id' => 'm.id']
            ])->where($condition)->limit($limit)->orderBy($orderBy)->select();
    }
}