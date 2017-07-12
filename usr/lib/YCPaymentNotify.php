<?php
/**
 * File: YCPaymentNotify.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-02-18 19:24
 */
namespace Lib;
class YCPaymentNotify implements \Payment\Notify\PayNotifyInterface
{

    /**
     * 异步回调检验完成后，回调客户端的业务逻辑
     * @param array $data 第三方支付返回的数据
     * @return bool
     */
    public function notifyProcess(array $data)
    {

        return true;
    }
}