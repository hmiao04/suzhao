<?php
/**
 * File: GroupBuy.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-02-27 20:36
 */

namespace Controller\Admin;

use Models\BillData;
use Models\BillModel;
use Models\DataStatus;
use Models\GroupBuyStatus;
use Models\LogAction;
use Models\LogState;
use Models\TuanBusiness;
use Models\UserGroupBuy;
use Models\UserGroupJoin;

class GroupBuy extends \AdminController
{

    public function init()
    {
        $this->addAdminUrl('tuan/view.{page}', 'pageView');
        $this->addAdminUrl('tuan/tuan.info', 'tuanInfo');
        $this->addAdminUrl('tuan/tuan.join', 'tuanJoin');
        $this->addAdminUrl('tuan/tuan.jie', 'jieTuanList');
        $this->addAdminUrl('tuan/tuan.jie.set', 'setJieTuan');
        $this->addAdminUrl('tuan/pay_status.change', 'changePayStatus');
        $this->addAdminUrl('tuan/tuan.update', 'updateTuan');
    }

    /**
     * @param $page
     */
    public function pageView($page)
    {
        $allowView = array(
            'tuan-list' => 'getAllTuan'
        );
        parent::processPageView($page, $allowView);
    }

    public function getAllTuan()
    {
        $groupBuy = new UserGroupBuy();
        list($page, $size, $star) = $this->getPageAndSize(15); //array(page,size,start)
        $condition = ['t.status'=>DataStatus::NORMAL,'group_status[!]' => GroupBuyStatus::$Saved];

        $queryParam = $this->getQueryParam();
        $queryString = http_build_query($queryParam['page']);
        $condition = array_merge($condition, $queryParam['query']);

        $gbList = $groupBuy->findByCondition($condition, [$star, $size], ['seq DESC', 'id DESC']);
        $totalCount = $groupBuy->count($condition);
        $this->createWindowPageLink('view.tuan-list?'.$queryString, $totalCount, $size);
        $this->assign('gbList', $gbList);
        if (isAjax()) $this->renderAjax('group-list-item');
    }

    public function tuanInfo()
    {
        $tuan = new UserGroupBuy();
        $tid = $this->getRequestId();
        if (!$tuan->find(['t.id'=>$tid])) ajaxError('没用找到团购信息');
        $this->render('tuan-info', ['gb' => $tuan->extraData]);
    }

    public function updateTuan()
    {
        $tuan = new UserGroupBuy();
        $tid = $this->getRequestId();
        $tuan->setProperty($this->input()->post());
        try{
            $tuan->update(['id'=>$tid]);
            ajaxSuccess();
        }catch (\Exception $e){
            ajaxException($e);
        }
    }

    public function tuanJoin()
    {
        $groupJoin = new UserGroupJoin();
        $groupJoin->status = 1;
        $id = $this->input()->get('id');
        $dataList = $groupJoin->findByCondition(['group_id' => $id]);
        $this->render('tuan-join', ['dataList' => $dataList]);
    }

    public function jieTuanList()
    {
        $groupJoin = new TuanBusiness();
        $groupJoin->status = 1;
        $tid = $this->getRequestId();
        $dataList = $groupJoin->getRecordList(['tuan_id' => $tid, 't.status[!]' => DataStatus::DELETE]);
        $this->render('tuan-jie', ['dataList' => $dataList]);
    }

    public function setJieTuan()
    {
        $tuan = new UserGroupBuy();
        $tid = $this->getRequestId();
        if (!$tuan->find(['t.id'=>$tid,'t.status'=>DataStatus::NORMAL])) {
            ajaxError('没有找到找到该团购(ERROR_JOIN_DATA)', 501);
        }
        $jt_id = $this->getRequestId(null, 'jt_id');
        $jt = new TuanBusiness();
        if (!$jt->exists(['id' => $jt_id])) {
            ajaxError('没有找到要设置的接团信息(ERROR_JOIN_DATA)', 501);
        }
        try {
            $jt->update(['id' => $jt_id], ['status' => 2]);//更新接团者状态
            $tuan->update(['id' => $tuan->id], ['group_status' => GroupBuyStatus::$Done]);//更新团购状态
            $this->recordAdminLog(LogAction::UPDATE, LogState::SUCCESS);

            ajaxSuccess();
        } catch (\Exception $e) {
            $this->recordAdminLog(LogAction::UPDATE, LogState::FAILED);
            ajaxException($e);
        }
        ajaxError('更新支付状态失败');

    }

    public function changePayStatus()
    {
        $gbj = new UserGroupJoin();
        $gId = $this->getRequestId();
        $gbj->id = $gId;
        $gbj->status = DataStatus::NORMAL;
        if (!$gbj->find()) {
            ajaxError('没有找到要设置的参团信息(ERROR_JOIN_DATA)', 501);
        }
        $bill = new BillModel();
        $bill->bill_sn = $gbj->bill_sn;
        $bill->status = DataStatus::NORMAL;
        if (!$bill->find()) {
            ajaxError('没有找到要支付的账单(ERROR_BILL_DATA)', 502);
        }
        if ($gbj->pay_status == BillData::$STATUS_UN_PAY) {
            $gbj->pay_status = BillData::$STATUS_PAYED;
        } else {
            $gbj->pay_status = BillData::$STATUS_UN_PAY;
        }
        try {
            $bill->update(['bill_sn' => $bill->bill_sn], ['pay_status' => $gbj->pay_status]);//更新账单支付状态
            $gbj->update(['id' => $gbj->id], ['pay_status' => $gbj->pay_status]);//更新参团支付状态
            $this->recordAdminLog(LogAction::UPDATE, LogState::SUCCESS);
            ajaxSuccess();
        } catch (\Exception $e) {
            $this->recordAdminLog(LogAction::UPDATE, LogState::FAILED);
            ajaxException($e);
        }
        ajaxError('更新支付状态失败');
    }
}