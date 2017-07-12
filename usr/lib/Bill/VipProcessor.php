<?php
/**
 * File: TuanProcessor.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-03-25 0:34
 */

namespace Lib\Bill;

use Models\BillData;
use Models\MemberModel;

/**
 * 会员支付账单业务处理类
 * @package Lib\Bill
 */
class VipProcessor implements IBillProcessor
{

    /**
     * 将数据转换成string
     * @param $array
     * @return mixed
     */
    function stringify($array)
    {
        return json_encode($array);
    }

    /**
     * 获取账单标题
     * @param $payData
     * @return mixed
     */
    function getPayTitle($payData)
    {
        $payData = json_decode($payData, 1);
        return $payData['title'];
    }

    /**
     * 获取支付商品内容
     * @param string $payData {"title":"开通会员","vip_month":"2","price":"0.01","remark":"","member_id":3}
     * @return mixed
     */
    function getPayBody($payData)
    {
        $payData = json_decode($payData, 1);
        $body = $payData['title'] . "\n";
        $body .= '开通时长:' . $payData['vip_month'].'个月';
        return $body;
    }

    /**
     * 事件：当支付成功时回调
     * 更新支付状态
     * @param $payData
     * @return mixed
     */
    function onPaySuccess($payData)
    {
        $payData = @@json_decode($payData, 1);
        if (is_array($payData)) {
            $member = new MemberModel();
            $memberId = $payData['member_id'];
            if($member->findByPrimary($memberId)){
                $startTime = $member->vip_time;
                $memberId = $member->id;
                $startTime = $member->vip_time;
                if (!$startTime || REQ_TIME >= strtotime($member->vip_time)) {
                    $startTime = REQ_DATETIME;
                }
                $endTime = date('Y-m-d H:i:s',strtotime($startTime . ' +' . $payData['vip_month'] . ' month'));

                //更新支付状态
                $member->update(
                    ['id' => $memberId],
                    ['vip_time' => $endTime]
                );
            }
        }

    }

    /**
     * 将string重写转成array
     * @param $payData
     * @return array
     */
    function parse($payData)
    {
        $payData = @@json_decode($payData, 1);
        if (!is_array($payData)) return [];
        $data = [
            'none'=> '<a href="vip.html">'.$payData['title'].'</a>',
//            '起始时间' => $payData['start_time'],
            '开通时长' => $payData['vip_month'].'个月',
//            '结束时间' => $payData['end_time']
        ];
//        return [];
        return $data;
    }
}