<?php
namespace Lib;
/**
 * 阿里云短信接口
 * @author 福来<115376835@qq.com>
 * 示例
 *     $sms = new \Lib\AliSms($accessKeyId,$accessKeySecret);
 *       $mobile = '18788830181';
 *       $code   = 'SMS_36225243';
 *       $paramString = '{"code":"344556"}';
 *       $re = $sms->send($mobile,$code,$paramString);
 *       print_r($re);
 *
 */

class AliSms
{
    public $config = array(
        'Format' => 'json', //返回值的类型，支持JSON与XML。默认为XML
        'Version' => '2016-09-27', //API版本号，为日期形式：YYYY-MM-DD，本版本对应为2016-09-27
        'SignatureMethod' => 'HMAC-SHA1', //签名方式，目前支持HMAC-SHA1
        'SignatureVersion' => '1.0',
    );
    public $debug = false;
    private $accessKeySecret;
    private $http = 'https://sms.aliyuncs.com/';        //短信接口
    private $dateTimeFormat = 'Y-m-d\TH:i:s\Z';

    public $signName = '速找科技'; //管理控制台中配置的短信签名（状态必须是验证通过）
    public $method = 'GET';
    private $AccessKeySecret;

    /**
     * AliSms constructor.
     * 发送短信
     * @param $accessKey 阿里云申请的 Access Key ID
     * @param $accessKeySecret 阿里云申请的 Access Key Secret
     */
    public function __construct($accessKey, $accessKeySecret)
    {
        $this->config['AccessKeyId'] = $accessKey;
        $this->AccessKeySecret = $accessKeySecret;
    }

    /**
     * 发送短信
     * @param $mobile 目标手机号，多个手机号可以逗号分隔
     * @param $code 短信模板的模板CODE
     * @param $ParamString 短信模板中的变量；,参数格式{“no”:”123456”}， 个人用户每个变量长度必须小于15个字符
     * @return bool|mixed
     */
    public function send($mobile, $code, $ParamString)
    {
        $apiParams = $this->config;
        $apiParams["Action"] = 'SingleSendSms';
        $apiParams['TemplateCode'] = $code;  //短信模板的模板CODE
        $apiParams['RecNum'] = $mobile;   //目标手机号，多个手机号可以逗号分隔
        $apiParams['ParamString'] = $ParamString;   //短信模板中的变量；,此参数传递{“no”:”123456”}， 个人用户每个变量长度必须小于15个字符
        $apiParams['SignName'] = $this->signName;   //管理控制台中配置的短信签名（状态必须是验证通过）
        date_default_timezone_set("GMT");
        $apiParams["Timestamp"] = date($this->dateTimeFormat);
        $apiParams["SignatureNonce"] = md5(SALT_KEY) . rand(100000, 999999) . uniqid(); //唯一随机数
        $apiParams["Signature"] = $this->computeSignature($apiParams, $this->AccessKeySecret);//签名

        $tag = '?';
        $requestUrl = $this->http;
        foreach ($apiParams as $apiParamKey => $apiParamValue) {
            $requestUrl .= $tag . "$apiParamKey=" . urlencode($apiParamValue);
            $tag = '&';
        }
        return $this->postSMS($requestUrl);
    }


    public function debug($msg)
    {
        if (!$this->debug) return;
        if (is_array($msg)) $msg = json_encode($msg, JSON_UNESCAPED_UNICODE);
        if (func_num_args() > 1) {
            $msg = implode('', func_get_args());
        }
        echo $msg . "<br>\n";
    }
    /**
     * @param $url 发送地址
     * @return bool|mixed
     */
    private function postSMS($url)
    {
        $opts = array(
            'http' => array(
                'method' => $this->method,
                'timeout' => 600,
                'header' => 'Content-Type: application/x-www-form-urlencoded',
            )
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        $this->debug('set url ', $url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1); //定义是否显示状态头 1：显示 ； 0：不显示
        curl_setopt($ch, CURLINFO_HEADER_OUT, 0);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
//
        $postHeader = array(
//            'Expect: ',
            'User-Agent: YCF SZ API V1',
            'Content-Type: application/x-www-form-urlencoded;charset=UTF-8'
        );
        curl_setopt($ch, CURLOPT_HTTPHEADER, $postHeader);

        $this->debug('post header:', json_encode($postHeader));
        $this->debug('post method:', $this->method);
        $sendData = false;
        if ($sendData && strtolower($this->method) == 'post') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $sendData);
        }
        $html = curl_exec($ch);
        $info = curl_getinfo($ch);
        $this->debug($html);
        $this->debug($info);
        if ($html) {
            return json_decode($html, true);
        } else {
            return false;
        }
    }

    /**
     * 生成短信签名
     * @param $parameters
     * @param $accessKeySecret
     * @return string
     */
    private function computeSignature($parameters, $accessKeySecret)
    {
        ksort($parameters);
        $canonicalizedQueryString = '';
        foreach ($parameters as $key => $value) {
            $canonicalizedQueryString .= '&' . $this->percentEncode($key) . '=' . $this->percentEncode($value);
        }
        $stringToSign = $this->method . '&%2F&' . $this->percentencode(substr($canonicalizedQueryString, 1));
        $signature = $this->signString($stringToSign, $accessKeySecret . "&");
        return $signature;
    }

    protected function percentEncode($str)
    {
        $res = urlencode($str);
        $res = preg_replace('/\+/', '%20', $res);
        $res = preg_replace('/\*/', '%2A', $res);
        $res = preg_replace('/%7E/', '~', $res);
        return $res;
    }

    /**
     * 签名内容
     * @param $source
     * @param $accessSecret
     * @return string
     */
    private function signString($source, $accessSecret)
    {
        return base64_encode(hash_hmac('sha1', $source, $accessSecret, true));
    }
}


