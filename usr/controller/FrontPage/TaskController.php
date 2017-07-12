<?php
/**
 * Created by PhpStorm.
 * User: yancheng<cheng@love.xiaoyan.me>
 * Date: 17/4/2
 * Time: 下午8:01
 */

namespace Controller\FrontPage;

use Lib\Bill\BillTool;
use Lib\WebController;
use Models\BillData;
use Models\BillModel;
use Models\BillType;
use Models\DataStatus;
use Models\MemberModel;
use Models\Task;
use Models\TaskRecord;
use models\TaskStatus;
use Service\MemberService;

class TaskController extends WebController
{
    public function init()
    {
        $this->addRoute('/task/', 'showTaskIndex');
        $this->addRoute('/task', 'showTaskIndex');
        $this->addRoute('/task/list.html', 'showDataList');

        $this->addRoute('/task/detail.html', 'showTaskDetail');
        $this->addRoute('/task/pay.html', 'payTask');

        $this->addRoute('/task/record.set-answer', 'setRecordAnswer');
        $this->addRoute('/task/record.info', 'showRecordInfo');
        $this->addRoute('/task/get-task.action', 'getTask');
        $this->addRoute('/task/done-task.action', 'doneTask');
    }

    public function before()
    {
        $this->setControllerRenderPath('front_page/sz');
    }

    public function showTaskIndex()
    {
        $tsk = new Task();
        $this->assign('recommendList', $tsk->getRecommend());
        $this->assign('newList', $tsk->getNewList());
        $this->render('index');
    }

    public function showDataList()
    {
        $tuan = new Task();
        $sort = 'id DESC';
        $queryString = '';
        if ($this->input()->get('sort') == 'top') {
            $sort = 'seq DESC';
            $queryString = '?sort=top';
        }
        $condition = ['status' => 1];
        list($page, $size, $start) = $this->getPageAndSize(30, null, 30); //array(page,size,start)
        $this->assign('dataList', $tuan->findByCondition($condition, [$start, $size], $sort));
        $totalCount = $tuan->count($condition);
        $this->createWindowPageLink('list.html' . $queryString, $totalCount, $size);
        $this->render('list');
    }

    /**
     * @return \Models\Task
     * @throws \AppException
     */
    private function getTaskInfo()
    {
        $tid = $this->input()->get('id');
        if (!$tid || preg_match('/^[\d]+$/', $tid) == false) {
            throw new \AppException('请求参数错误(ERROR_PARAM_ID)');
        }
        $t = new Task();
        if (!$t->findByPrimary($tid)) {
            throw new \AppException('您浏览的速配信息不存在(ERROR_NOT_FOUND)');
        }
        return $t;
    }

    public function payTask()
    {
        $t = $this->getTaskInfo();
        if ($t->pay_status == 1) {
            throw new \AppException('速配已经支付(ERROR_ALREADY_PAID)');
        }
        $bill = new BillModel();
        $bill_sn =$t->bill_sn;
        if (!$bill_sn || !$bill->find(['bill_sn' => $bill_sn, 'status' => DataStatus::NORMAL])) {
            $billData = [
                'task_id' => $t->id,
                'task_title' => $t->find_title,
                'member_id' => $t->member_id,
                'title' => TASK_BILL_TITLE,
            ];
            $bill_sn = BillTool::Instance()->Create($t->paid_price, $t->member_id, TASK_BILL_TITLE, BillType::$Task, $billData);
            //更新支付状态
            $t->update(['id' => $t->id], [
                'bill_sn' => $bill_sn,
                'pay_status' => 0
            ]);
        }
        jump_url('../member/bill.html?bill_sn='.$bill_sn);
    }

    public function showTaskDetail()
    {
        $t = $this->getTaskInfo();
//        $t->find_content = nl2br($t->find_content);
        $record = new TaskRecord();
        $r_list = $record->getTaskRecord($t->id);
        $memberId = $this->getLoginMemberId();
        $showPhone = false;
        foreach ($r_list as &$item) {
            $item['answer_data'] = nl2br($item['answer_data']);
            if ($memberId > 0 && $item['member_id'] == $memberId) {
                $showPhone = true;
            }
        }
        if ($memberId == $t->member_id) {
            $showPhone = true;
        }
        $m = new MemberModel();
        $this->assign('record_list', $r_list);
        $this->assign('task_info', $t);
        $taskMember = $m->findByPrimary($t->member_id);
        if ($showPhone == false && $taskMember) {
            $m->phone = substr($m->phone, 0, 3) . '****' . substr($m->phone, -4);
        }
        $this->assign('task_member', $m);
        $this->assign('showPhone', $showPhone);
        $this->render('detail');
    }

    public function setRecordAnswer()
    {
        $this->needAjax();
        $this->checkMemberLogin();
        $ret = $this->checkDataNull(array(
            array('id', 1, '请求参数错误(ERROR_PARAM_ID)'),
            array('fast_find_id', 1, '请求参数错误(ERROR_PARAM_TASK_ID)'),
            array('answer_data', 2, '必须填写答案'),
        ));
        $task = new Task();
        $t = new TaskRecord();
        $t->setProperty($ret);
        $checkRecord = new TaskRecord();
        if (!$task->findByPrimary($ret['fast_find_id'])) {//任务是否存在
            ajaxError('请求参数错误(ERROR_TASK_ID)');
        }
        if ($task->status != 2) {//任务状态是否正常
            ajaxError('此速找暂时无法操作(ERROR_TASK_STATUS)');
        }
        if(!MemberService::HasCertification($this->getLoginMemberInfo())){
            ajaxError('必须先进行实名认证后才能接此速配(INVALID_OPERATION)', 404);
        }
        $data = $checkRecord->find(['id' => $ret['id']]);
        if (!$data) {//检查记录
            ajaxError('暂时不允许进行此操作(INVALID_OPERATION)');
        }
        if ($checkRecord->member_id != $this->getLoginMemberId()) {
            ajaxError('暂时不允许进行此操作(INVALID_OPERATOR)');
        }
        $t->member_id = $this->getLoginMemberId();
        $t->answer_time = REQ_DATETIME;
        try {
            $t->status = 2;
            $t->update(['id' => $checkRecord->id]);
            ajaxSuccess();
        } catch (\Exception $e) {
            ajaxException($e);
        }
        ajaxError('设置速找答案失败');
    }

    public function showRecordInfo()
    {
        $this->needAjax();
        $this->checkMemberLogin();
        $tid = $this->input()->get('id');
        if (!$tid || preg_match('/^[\d]+$/', $tid) == false) {
            throw new \AppException('请求参数错误(ERROR_PARAM_ID)');
        }
        $t = new TaskRecord();
        if (!$t->find(['id' => $tid])) {
            throw new \AppException('您请求的数据不存在(ERROR_NOT_FOUND)');
        }
        if (empty($t->answer_data)) $t->answer_data = '';
        ajaxSuccess($t);
    }

    public function doneTask()
    {
        $this->needAjax();
        $this->checkMemberLogin();
        $task = $this->getTaskInfo();
        if ($task->member_id != $this->getLoginMemberId()) {
            ajaxError('您的请求暂时不支持(INVALID_OPERATION)', 403);
        }
        if ($task->status != TaskStatus::$Started) {
            ajaxError('该速找还没有进行速配(ERROR_STATUS)', 501);
        }
        if ($task->pay_status != BillData::$STATUS_PAYED) {
            ajaxError('无法继续操作,您尚未支付此速配(ERROR_PAY_STATUS)', 502);
        }
        ajaxError();
        try {
            $task->update(['id' => $task->id], ['status' => TaskStatus::$Done]);
            ajaxSuccess();
        } catch (\Exception $e) {
            ajaxException($e);
        }
        ajaxError('操作速找失败');
    }

    public function getTask()
    {
        $this->needAjax();
        $this->checkMemberLogin();
        $task = $this->getTaskInfo();
        if ($task->status != TaskStatus::$NotStart && $task->status != TaskStatus::$Started) {
            ajaxError('此速找暂时不接受操作(ERROR_STATUS)');
        }
        $memberInfo =$this->getLoginMemberInfo();
        $mid = $memberInfo->id;
        if ($task->member_id == $mid) {
            ajaxError('您不能接自己的速找任务');
        }
        if(!MemberService::HasCertification($memberInfo)){
            ajaxError('必须先进行实名认证后才能接此速配(INVALID_OPERATION)', 404);
        }
        $t = new TaskRecord();
        $t->fast_find_id = $task->id;
        $t->member_id = $mid;
        $t->status = DataStatus::NORMAL;
        if ($t->exists()) {
            throw new \AppException('您已经为此速找速配了(ERROR_ACCEPT)');
        }
        $t->join_time = REQ_DATETIME;
        try {
            $task->update(['id' => $task->id], ['status' => TaskStatus::$Started]);
            if ($t->insert()->lastInsertId > 0) {
                ajaxSuccess();
            }
        } catch (\Exception $e) {
            ajaxException($e);
        }
        ajaxError('操作失败请重试');
    }
}