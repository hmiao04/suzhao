<?php
/**
 * File: BillTool.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-03-25 0:34
 */

namespace Lib\Bill;

use Models\BillData;
use Models\BillModel;
use Models\BillType;
use Payment\Common\PayException;

class PayType
{
    public static $Alipay = 'Alipay';
}

class BillTool
{
    /**
     * @var BillTool
     */
    private static $instance = null;
    /**
     * @var IBillProcessor
     */
    private $currentProcessor;
    private $processorList = [];


    /**
     * @param $pay_type
     * @return IBillProcessor
     * @throws PayException
     * @throws \AppException
     */
    private function createProcessor($pay_type)
    {
        if (isset($this->processorList[$pay_type])) return $this->processorList[$pay_type];
        $pay_type = BillType::GetProcessor($pay_type);
        if (null == $pay_type) throw new PayException('pay processor not found');
        $driver = "\\Lib\\Bill\\" . $pay_type;
        if (!class_exists($driver)) {
            throw new \AppException('pay processor not exists');
        }
        $cls = new \ReflectionClass($driver);
        if (!$cls->implementsInterface('\\Lib\\Bill\\IBillProcessor')) throw new \AppException('processor not implements IBillProcessor');
        $this->currentProcessor = $cls->newInstanceArgs();
        $this->processorList[$pay_type] = $this->currentProcessor;
        return $this->currentProcessor;
    }

    /**
     * 根据支付项获取支付处理工具类对象
     * @param string $billType
     * @return IBillProcessor
     * @throws PayException
     * @throws \AppException
     */
    public function GetProcess($billType)
    {
        return $this->createProcessor($billType);
    }

    private function __construct()
    {
    }


    /**
     * @return BillTool
     */
    public static function Instance()
    {
        if (null == self::$instance) {
            self::$instance = new BillTool();
        }
        return self::$instance;
    }

    private function CreateBillSN($member_id)
    {
        //201702181915120001001
        return date('Ymdhis', REQ_TIME)
        . (strlen($member_id . '') > 5 ? substr($member_id . '', 0, 5) : str_pad($member_id, 5, '0', STR_PAD_LEFT))
        . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
    }

    public function CreatePaySN($member_id, $pay_way = 'ALI')
    {
        return strtoupper($pay_way) . date('Ymdhis', REQ_TIME) . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
    }

    /**
     * 将账单设置为已支付
     * @param $billSn
     */
    public function SetBillPaid($billSn)
    {
        $bill = new BillModel();
        if (!$bill->find(['bill_sn' => $billSn])) {
           return true;
        }
        //更新账单状态
        $bill->update(['bill_sn'=>$billSn],['pay_status'=>BillData::$STATUS_PAYED,'paid_time'=>REQ_TIME]);
        BillTool::Instance()->GetProcess($bill->bill_type)->onPaySuccess($bill->bill_data);
    }

    public function Create($pay_money, $member_id, $pay_title, $bill_type, $bill_data, $remark = '')
    {
        $this->createProcessor($bill_type);
        $bill = new BillModel();
        $bill->bill_title = $pay_title;
        $bill->member_id = $member_id;
        $bill->bill_sn = self::CreateBillSN($member_id);
        $bill->pay_money = $pay_money;
        $bill->create_time = REQ_TIME;
        $bill->pay_status = BillData::$STATUS_UN_PAY;
        $bill->remarks = $remark;
        $bill->bill_type = $bill_type;
        $bill->bill_data = $this->currentProcessor->stringify($bill_data);
        try {
            $bill->insert(true);
            return $bill->bill_sn;
        } catch (\Exception $e) {
            return false;
        }

    }
}