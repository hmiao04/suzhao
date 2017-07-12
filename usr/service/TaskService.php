<?php
/**
 * File: TaskService.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-04-11 3:17
 */

namespace Service;


use Models\DataStatus;
use Models\Task;
use models\TaskStatus;

class TaskService
{
    /**
     * @var \Models\Task
     */
    private $task = null;

    public function __construct()
    {
        $this->task = new Task();
    }

    public function getAvailableCount()
    {
        $condition = ['status[!]' => 0];
        return $this->task->count($condition);
    }

    public static function AvailableCount()
    {
        $t = new TaskService();
        return $t->getAvailableCount();
    }

    public function getTopBySeq($count, $start = 0)
    {
        return $this->task->findByCondition(['status' => TaskStatus::$NotStart], [$start, $count], ['seq DESC', 'id DESC']);
    }

    public function getTaskByMember($memberId, $status = 0, $start, $count)
    {
        $condition = [
            'member_id' => $memberId
        ];
        if ($status > 0) {
            $condition['status'] = $status;
        } else {
            $condition['status[!]'] = DataStatus::DELETE;
        }
        return $this->task->findByCondition($condition, [$start, $count], ['id DESC']);
    }

    public function getAcceptTask($memberId, $status = 0, $start, $count)
    {
        $status = $status > 0 ? 'and t.status=' . $status : 'and t.status <> ' . DataStatus::DELETE;
        $query = "SELECT
	t.id,t.find_title,t.paid_price,t.pay_status,t.home_image,t.main_image
from sz_fast_find t,sz_fast_find_record r
where
	t.id = r.fast_find_id
and	r.member_id = {$memberId} {$status}
limit $start,$count ";
        return $this->task->findListByQuery($query);
    }
}