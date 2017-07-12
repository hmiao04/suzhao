<?php
/**
 * File: TuanProcessor.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-03-25 0:34
 */

namespace Lib\Bill;


use Models\BillData;
use Models\Task;

class TaskProcessor implements IBillProcessor
{


    /**
     * 将数据转换成string
     * @param $array
     * @return mixed
     */
    function stringify($array)
    {
        return encode_json($array);
    }

    /**
     * 获取账单标题
     * @param $payData
     * @return mixed
     */
    function getPayTitle($payData)
    {
        $payData = decode_json($payData);
        return $payData['title'];
    }

    /**
     * 获取支付商品内容
     * @param $payData
     * @return mixed
     */
    function getPayBody($payData)
    {
        $payData = decode_json($payData);
        return $payData['task_title'];
    }

    /**
     * 事件：当支付成功时回调
     * @param $payData
     * @return mixed
     */
    function onPaySuccess($payData)
    {
        $payData = decode_json($payData);
        if (is_array($payData)) {
            $task = new Task();
            $tskId = $payData['task_id'];
            if($task->findByPrimary($tskId)){
                //更新支付状态
                $task->update(
                    ['id' => $tskId],
                    ['pay_status' => BillData::$STATUS_PAYED]
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
        $payData = decode_json($payData);
        if (!is_array($payData)) return [];
        $tskId = $payData['task_id'];
        $data = [
            'none'=> '<a href="../task/detail.html?id="'.$tskId.'>'.$payData['title'].'</a>'
        ];
        return [];
//        return $data;
    }
}