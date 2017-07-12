<?php
/**
 * File: TuanProcessor.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-03-25 0:34
 */

namespace Lib\Bill;

use models\MemberCompany;

/**
 * 实地认证支付账单业务处理类
 * @package Lib\Bill
 */
class CertificationProcessor implements IBillProcessor
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
     * @param string $payData {"title":"实地认证手续费","member_id":"8","company_id":"4","price":0.01}
     * @return mixed
     */
    function getPayBody($payData)
    {
        $payData = json_decode($payData, 1);
        $body = $payData['title'];
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
            $company = new MemberCompany();
            $memberId = $payData['company_id'];
            if($company->findByPrimary($memberId)){
                //更新认证状态
                $company->update(
                    ['company_id' => $memberId],
                    ['certification_status' => 3]
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
            'none'=> '<a href="vip.html">'.$payData['title'].'</a>'
        ];
        return [];
//        return $data;
    }
}