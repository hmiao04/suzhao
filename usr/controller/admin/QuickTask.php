<?php
/**
 * File: Suzhao.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-03-05 13:52
 */

namespace Controller\Admin;


use Models\LogAction;
use Models\LogState;
use Models\Task;

class QuickTask extends \AdminController
{

    public function init()
    {
        $this->addAdminUrl('task/view.{page}', 'pageView');
        $this->addAdminUrl('task/task.info', 'taskInfo');
        $this->addAdminUrl('task/task.update', 'taskUpdate');
    }

    /**
     * @param $page
     */
    public function pageView($page)
    {
        $allowView = array(
            'task-list' => 'taskList',
        );
        parent::processPageView($page, $allowView);
    }

    public function taskList()
    {
        $tsk = new Task();
        list($page, $size, $star) = $this->getPageAndSize(15); //array(page,size,start)
        $condition = [];
        $tskList = $tsk->getListByCondition($condition, [$star, $size],['seq DESC','id DESC']);
        $totalCount = $tsk->count($condition);
        $this->createWindowPageLink('view.task-list', $totalCount, $size);
        $this->assign('taskList', $tskList);
        if (isAjax()) $this->renderAjax('task-list-item');
    }

    public function taskInfo()
    {
        $tsk = new Task();
        $condition = [
            't.id' => $this->getRequestId(),
            't.status[!]' => 0
        ];
        $data = $tsk->info($condition);
        if (!$data) ajaxError('请求的速找数据不存在');
        $this->assign('tsk', $data);
        $this->renderAjax('task-info');
    }

    public function taskUpdate()
    {
        $tsk = new Task();
        $tskId = $this->getRequestId();
        $tsk->setProperty($this->input()->post());
        try {
            $tsk->update(['id' => $tskId]);
            $this->recordLog(LogAction::UPDATE, $this->getLoginAdminId(), LogState::SUCCESS);
            ajaxSuccess();
        } catch (\Exception $e) {
            $this->recordLog(LogAction::UPDATE, $this->getLoginAdminId(), LogState::FAILED);
            ajaxException($e);
        }
    }
}