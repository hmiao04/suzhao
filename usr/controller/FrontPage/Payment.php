<?php
/**
 * File: Payment.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-02-18 18:36
 */

namespace Controller\FrontPage;


use Lib\Bill\BillTool;
use Lib\Bill\PayType;
use Lib\WebController;
use Lib\YCPaymentNotify;
use Models\BillData;
use Models\BillModel;
use Payment\ChargeContext;
use Payment\NotifyContext;
use Payment\Common\PayException;
use Payment\Config;
use Payment\QueryContext;

class Payment extends WebController
{
    private $pay_config = array();

    public function init()
    {
        $this->addRoute('/pay/notify.html', 'payNotify');
        $this->addRoute('/pay/query.html', 'queryPay');
        $this->addRoute('/pay/pay.html', 'startPay');
        $this->addRoute('/pay/return.html', 'payReturn');
        $this->addRoute('/pay/test.html', 'testPay');
    }

    public function before()
    {
        $this->pay_config = include(APP_DIR . '/usr/var/payment.config.php');
    }

    /**
     * 支付宝回调通知
     */
    public function payNotify()
    {
        $notify = new NotifyContext();
        $callback = new YCPaymentNotify();
        try {
            // 支付宝回调
            $notify->initNotify(Config::ALI, $this->pay_config['alipay']);
            $ret = $notify->notify($callback);
        } catch (PayException $e) {
            echo $e->errorMessage();
            exit;
        }
        file_put_contents('ali_notify.txt', print_r($ret, 1));
        var_dump($ret);
        exit;
    }

    public function queryPay()
    {
        $pay_sn = $this->input()->post('pay_way_sn');
        $bill_sn = $this->input()->post('bill_sn');
        if(!$pay_sn) ajaxError('无法确认支付的账单(ERROR_PARAM_PAY_WAY_SN)');
        error_reporting(0);
        $bill = new BillModel();
        if(!$bill->find(['pay_way_sn'=>$pay_sn])){
            $not_found = true;
            if($bill_sn){
                if($bill->find(['bill_sn'=>$bill_sn])){
                    $not_found = false;//已经找到了
                }
            }
            if($not_found){
                ajaxError('未找到要支付的账单(ERROR_NOT_FOUND_BILL)');
            }
        }
        if($bill->pay_status != BillData::$STATUS_UN_PAY) {
            ajaxSuccess();
            //ajaxError('该账单已被支付(ERROR_BILL_STATUS)');
        }
        // 通过订单号查询支付状态
        $data = ['order_no' => $pay_sn];
        $query = new QueryContext();
        try {
            // 支付宝订单查询
            $query->initQuery(Config::ALI, $this->pay_config['alipay']);
            $ret = $query->query($data);
            if(is_array($ret) && $ret['is_success'] == 'T'){//支付成功
                //更新账单状态
                $bill->update(['bill_sn'=>$bill->bill_sn],['pay_status'=>BillData::$STATUS_PAYED,'paid_time'=>REQ_TIME]);
                //通知相应业务进行操作
                try{
                    BillTool::Instance()->GetProcess($bill->bill_type)->onPaySuccess($bill->bill_data);
                }catch (\Exception $ex){}
                ajaxSuccess();
            }
        } catch (PayException $e) {
           ajaxException($e);
        }
        ajaxError('暂时无法获取支付状态,请稍候查看账单状态或者联系客服人员(ERROR_CHECK_PAY_STATUS)',5);
    }

    public function startPay()
    {
        $this->checkMemberLogin();
        $bill_sn = $this->input()->get('bill_sn');
        if (!$bill_sn) throw new PayException('账单编号错误');
        $bill = new BillModel();
        $bill->bill_sn = trim($bill_sn);
        if (null == $bill->find()) throw new PayException('参数错误，没有找到要支付的账单');
        if ($bill->status != 1) throw new PayException('无法支付该账单');
        if ($bill->pay_status != BillData::$STATUS_UN_PAY) throw new PayException('该账单暂时无法支付');
        if ($bill->member_id != $this->getLoginMemberId()) throw new PayException('参数错误，没有找到要支付的账单');

        $processor = BillTool::Instance()->GetProcess($bill->bill_type);
        if($bill->pay_money <= 0){//如果待支付账单金额为0 则直接支付
            BillTool::Instance()->SetBillPaid($bill_sn);
            jump_url('../member/bill.html');
            exit;
        }
        $bill->bill_title = $processor->getPayTitle($bill->bill_data);
        $bill->pay_way_sn = BillTool::Instance()->CreatePaySN($bill->member_id);
        try {
            $bill->update(['bill_sn' => $bill->bill_sn], ['pay_way_sn' => $bill->pay_way_sn]);
        } catch (\Exception $e) {
            throw new PayException('更新账单数据失败,请重新进行操作');
        }
        $pay_body = $processor->getPayBody($bill->bill_data);
        $bill_data = array(
            'bill_sn'=>$bill_sn,
            'bill_data'=>$bill->bill_data
        );
        $payData = [
            "order_no" => $bill->pay_way_sn,
            "amount" => $bill->pay_money,// 支付金额
            "client_ip" => getClientIP(),
            "subject" => $bill->bill_title,
            "body" => $pay_body,
            "show_url" => URL() . '/bill/info.html?' . $bill_sn,// 支付宝手机网站支付接口 该参数必须上传 。其他接口忽略
            "extra_param" => json_encode($bill_data),
        ];

        $charge = new ChargeContext();
        try {
            // 支付宝即时到帐接口
            $charge->initCharge(Config::ALI_CHANNEL_WEB, $this->pay_config['alipay']);
            $ret = $charge->charge($payData);
            $this->assign('pay_url', $ret);
            $this->assign('bill_info', $bill);
            $this->render('pay-info');
        } catch (PayException $e) {
            print_r($e);
        }
    }

    public function testPay()
    {
        $payData = [
            "order_no" => 'YCD' . date('YmdHis'),
            "amount" => '0.01',// 单位为元 ,最小为0.01
            "client_ip" => '125.71.89.89',
            "subject" => '测试即时到帐接口',
            "body" => '支付接口测试',
            "show_url" => URL(1) . '/bill/info.html?time=' . REQ_TIME,// 支付宝手机网站支付接口 该参数必须上传 。其他接口忽略
            "extra_param" => ''
        ];
        $charge = new ChargeContext();
        try {
            // 支付宝即时到帐接口
            $charge->initCharge(Config::ALI_CHANNEL_WEB, $this->pay_config['alipay']);
            $ret = $charge->charge($payData);
            echo "<a href='{$ret}' target='_blank'>点击使用支付宝支付</a>";
            exit;
        } catch (PayException $e) {
            print_r($e);
        }


    }

    public function payReturn()
    {
        $extra_common_param = $this->input()->get('extra_common_param');
        // 通过支付宝交易号查询
        $returnData = [
            'pay_way_sn' => $this->input()->get('out_trade_no'),
            'trade_no' => $this->input()->get('trade_no')
        ];
        $returnData['bill_sn'] = '';
        if($extra_common_param) {
            $param = @@json_decode($extra_common_param,1);
            if($param){
                $returnData['bill_sn'] = $param['bill_sn'];
            }
        }

        $this->render('pay-return', [
            'pay_back_data' => json_encode($returnData)
        ]);
//        file_put_contents('ali_return.txt', print_r($_REQUEST, 1));
//        echo 'success';

    }
}