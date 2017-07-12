<?php
/**
 * File: Bill.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-05-01 10:14
 */

namespace Controller\FrontPage;


use Lib\WebController;
use Models\BillModel;
use Models\DataStatus;

class Bill extends WebController
{

    public function init()
    {
        $this->addRoute('/bill/delete.html', 'deleteBill');
    }
    public function before()
    {
        $this->checkMemberLogin();
        $this->setControllerRenderPath('front_page/bill');
    }

    public function deleteBill()
    {
        $bill = new BillModel();
        $bill_sn = $this->input()->get('bill_sn');
        if ($bill_sn && preg_match('/^\d+$/', $bill_sn)) {
            $bill->bill_sn = $bill_sn;
            $bill->status = DataStatus::DELETE;
            $bill->update();
            ajaxSuccess();
        }
        ajaxError('账单删除异常');
    }
}