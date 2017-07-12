<?php
/**
 * File: Common.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-04-30 3:39
 */

namespace Controller\Api;


use Service\SmsService;

class Common extends \ApiController
{
    /**
     * 发送短信验证码
     * @param string phone 接收短信的手机号码
     */
    public function SendSMS(){
        $this->checkRequestMethod('POST');
        $ret = $this->checkDataNull(array(
            array('phone', 1, '参数错误,缺少电话号码(MISSING_PARAM_PHONE)')
        ), true, $this->input()->post());
        if(!preg_match('/^1[34578]\d{9}$/',$ret['phone'])){
            ajaxError('手机号码格式有误');
        }
        $sms = new SmsService();
        if($code = $sms->sendVerifyCode($ret['phone'])){
            ajaxSuccess([],'json','验证码已经发送!');
        }
        ajaxError('发送短信验证码失败');
    }
}