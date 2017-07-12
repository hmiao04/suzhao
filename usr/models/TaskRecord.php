<?php
/**
 * File: TaskRecord.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-03-05 16:30
 */

namespace Models;


class TaskRecord extends \Model
{

    public $id;
    public $fast_find_id;
    public $member_id;
    public $join_time;
    public $answer_time;
    public $answer_data;
    public $status;

    public function __construct()
    {
        $this->setPrimaryKey('id');
        $this->setTableName('sz_fast_find_record');
    }

    public function getTaskRecord($taskId = null){
        if(null == $taskId) $taskId = $this->fast_find_id;
        $list = DB()->field('r.*,m.name,m.avatar')->table($this->getTableName() . '(r)')
            ->join([
                '[><]' . $this->getTableName(Task::class) . '(t)' => ['t.id' => 'r.fast_find_id'],
                '[><]' . $this->getTableName(MemberModel::class) . '(m)' => ['m.id' => 'r.member_id'],
            ])->where(['fast_find_id'=>$taskId])->orderBy('id DESC')->select();
        return $list;
    }
}