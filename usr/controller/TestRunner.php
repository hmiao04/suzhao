<?php
/**
 * File: TestRunner.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-05-07 18:36
 */
namespace Controller;

use AliyunMNS\Client;
use AliyunMNS\Model\BatchSmsAttributes;
use AliyunMNS\Model\MessageAttributes;
use AliyunMNS\Requests\PublishMessageRequest;
use Lib\AliSms;
use Lib\WebController;

class TestRunner extends WebController
{
    private $client;

    public function init()
    {
        $this->addRoute('/test/sms', 'sendSMS2');
    }

    public function sendSMS2()
    {
        include APP_DIR.'/usr/lib/AliyunSMS/mns-autoloader.php';
        $this->client = new Client(SMS_ENDPOINT, API_MAIN_KEY, API_MAIN_KEY_SEC);
        /**
         * Step 2. 获取主题引用
         */
        $topicName = "sms.topic-cn-shenzhen";
        $topic = $this->client->getTopicRef($topicName);
        /**
         * Step 3. 生成SMS消息属性
         */
        // 3.1 设置发送短信的签名（SMSSignName）和模板（SMSTemplateCode）
        $batchSmsAttributes = new BatchSmsAttributes("速找科技", "SMS_63370280");
        // 3.2 （如果在短信模板中定义了参数）指定短信模板中对应参数的值
        $batchSmsAttributes->addReceiver("18982208214", array(
            "code" => "123456",
            "product" => "速找网"
        ));
        $messageAttributes = new MessageAttributes(array($batchSmsAttributes));
        /**
         * Step 4. 设置SMS消息体（必须）
         *
         * 注：目前暂时不支持消息内容为空，需要指定消息内容，不为空即可。
         */
        $messageBody = "YCF MESSAGE";
        /**
         * Step 5. 发布SMS消息
         */
        $request = new PublishMessageRequest($messageBody, $messageAttributes);
        try
        {
            $res = $topic->publishMessage($request);
            echo $res->isSucceed();
            echo "\n";
            echo $res->getMessageId();
            echo "\n";
        }
        catch (MnsException $e)
        {
            echo $e;
            echo "\n";
        }

    }

    public function sendSMS()
    {
        $sms = new AliSms(API_MAIN_KEY, API_MAIN_KEY_SEC);
        $sms->debug = true;
        $ret = $sms->send('18982208214', 'SMS_63370276',
            '{"code":"123456","product":"速找网"}');
        var_dump($ret);
        exit;
    }
}