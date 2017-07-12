<?php
/**
 * File: TuanProcessor.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-03-25 0:34
 */

namespace Lib\Bill;

use Models\BillData;
use Models\UserGroupJoin;

/**
 * 团购支付账单业务处理类
 * @package Lib\Bill
 */
class TuanProcessor implements IBillProcessor
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
        return '参加团购-' . $payData['title'];
    }

    /**
     * 获取支付商品内容
     * @param string $payData {"title":"测试团购呀 - AD高端职业装女装套装时尚气质正装白领西装西服套裙ol工作服春秋","group_id":"2","join_time":1490523282,"price":"0.01","count":"1","remark":"bbbbbbbb"}
     * @return mixed
     */
    function getPayBody($payData)
    {
        $payData = json_decode($payData, 1);
        $body = $payData['title'] . "\n";
        $body .= '参团单价:' . $payData['price'] . "\n";
        $body .= '购买数量:' . $payData['count'];
        return $body;
    }

    /**
     * 事件：当支付成功时回调
     * 更新参团支付状态
     * @param $payData
     * @return mixed
     */
    function onPaySuccess($payData)
    {
        $payData = @@json_decode($payData, 1);
        if (is_array($payData)) {
            $groupJoin = new UserGroupJoin();
            //更新支付状态
            $groupJoin->update(
                ['id' => $payData['join_id']],
                [
                    'pay_status' => BillData::$STATUS_PAYED
                ]
            );
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
            'none' => stringFormat('<a href="{2}/tuan/detail.html?id={0}" target="_blank">{1}</a>', $payData['group_id'], $payData['title'],URL()),
            '参团价格' => $payData['price'] . '元',
            '购买数量' => $payData['count'],
            '参团时间' => date('Y-m-d h:i:s', $payData['join_time']),
            '备注' => $payData['remark'],
        ];

        return $data;
    }
}