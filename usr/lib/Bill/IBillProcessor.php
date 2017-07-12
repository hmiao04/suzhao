<?php
/**
 * File: BillProcess.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-03-25 0:34
 */

namespace Lib\Bill;

interface IBillProcessor
{
    /**
     * 将数据转换成string
     * @param $array
     * @return mixed
     */
    function stringify($array);

    /**
     * 将string重写转成array
     * @param $payData
     * @return array
     */
    function parse($payData);

    /**
     * 获取账单标题
     * @param $payData
     * @return mixed
     */
    function getPayTitle($payData);

    /**
     * 获取支付商品内容
     * @param $payData
     * @return mixed
     */
    function getPayBody($payData);

    /**
     * 事件：当支付成功时回调
     * @param $payData
     * @return mixed
     */
    function onPaySuccess($payData);
}