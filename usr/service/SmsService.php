<?php
/**
 * File: SmsService.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-04-30 3:48
 */

namespace Service;

use AliyunMNS\Client;
use AliyunMNS\Exception\MnsException;
use AliyunMNS\Model\BatchSmsAttributes;
use AliyunMNS\Model\MessageAttributes;
use AliyunMNS\Requests\PublishMessageRequest;
use Models\DataStatus;
use Models\SMSModel;
use Models\SMSType;

class SmsService
{
    /**
     * @var \Models\SMSModel
     */
    private $smsModel;
    private $limitTime;
    const VERIFIED = 2;

    public function __construct()
    {
        $this->smsModel = new SMSModel();
        $this->limitTime = \YCF::Instance()->getConfig('app')->getConfig('sms')
            ->getConfig('limit_time')->getValue();
    }

    public function validate($phone, $code, $type = SMSType::VerifyCode)
    {
        $data = $this->smsModel->findData(['sms_phone' => $phone, 'status' => DataStatus::NORMAL, 'sms_type' => $type], 'id DESC');
        if ($data != null && !$data && $data->send_time <= REQ_TIME + $this->limitTime * 60) {
            return false;
        }
        if (strtolower($code) == strtolower($this->smsModel->sms_content)) {
            $this->smsModel->update(['id' => $data->id], ['status' => self::VERIFIED]);
            return true;
        }
        return false;
    }

    public function createCode($length = 6)
    {
        return str_pad(mt_rand(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
//        $chars = '0123456789';
//        $str = '';
//        for ($i = 0; $i < $length; $i++) {
//            $str .= $chars[mt_rand(0, strlen($chars) - 1)];
//        }
//        return $str;
    }

    public function sendVerifyCode($phone, $code = null, $type = SMSType::VerifyCode)
    {
        $data = $this->smsModel->findData(['sms_phone' => $phone, 'status' => DataStatus::NORMAL, 'sms_type' => $type], 'id DESC');
        if ($data && $data->send_time >= REQ_TIME - $this->limitTime * 60) {
            //return $data->sms_content;
            throw new \AppException('短信验证码有效期' . $this->limitTime . '分钟');
        }
        if (!$code) $code = $this->createCode();
        $insertData = new SMSModel();
        $insertData->sms_content = $code;
        $insertData->send_time = REQ_TIME;
        $insertData->sms_phone = $phone;
        $insertData->sms_type = $type;
        $insertData->status = DataStatus::NORMAL;
        $id = $insertData->insert()->lastInsertId;
        if ($id > 0) {
            if($this->sendValidateCode($phone,$code)){
                return $code;
            }
            return false;
        }
        return $id > 0 ? $code : false;
    }

    public function sendValidateCode($phone, $code)
    {
        include APP_DIR . '/usr/lib/AliyunSMS/mns-autoloader.php';
        $client = new Client(SMS_ENDPOINT, API_MAIN_KEY, API_MAIN_KEY_SEC);
        $topic = $client->getTopicRef(SMS_TOPIC);
        //签名,模板id
        $batchSmsAttributes = new BatchSmsAttributes(SMS_SIGN_TEXT, SMS_IDENTIFICATION_VERIFY);
        // 3.2 （如果在短信模板中定义了参数）指定短信模板中对应参数的值
        $batchSmsAttributes->addReceiver($phone, array("code" => $code, "product" => APP_NAME));
        $messageAttributes = new MessageAttributes(array($batchSmsAttributes));
        $messageBody = "YCF MESSAGE";
        $request = new PublishMessageRequest($messageBody, $messageAttributes);
        try {
            $res = $topic->publishMessage($request);
            if ($res->isSucceed()) {
                return $res->getMessageId();
            }
            return false;
        } catch (MnsException $e) {
            return false;
        }

    }
}