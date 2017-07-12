<?php
/**
 * File: AliOSSTool.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-02-28 9:41
 */

namespace Lib\Uploader;
use OSS\OssClient;

class AliOSSUploader implements UploadDriver
{
    /**
     * @var \OSS\OssClient
     */
    private $ossClient;

    private $accessKey;
    private $accessKeySecret;
    private $endpoint;
    private $bucket;

    public function init($config)
    {
        $this->accessKey = $config['accessKey'];
        $this->accessKeySecret = $config['accessKeySecret'];
        $this->endpoint = $config['endpoint'];
        $this->bucket = $config['bucket'];
        $this->ossClient = new OssClient($this->accessKey, $this->accessKeySecret, $this->endpoint);
        return $this;
    }

    public function upload($fileName,$fileObject, $fileInfo)
    {
        try{
            $ret = $this->ossClient->doesObjectExist($this->bucket,$fileName);
            if($ret){
                return true;
            }
        }catch (\Exception $e){
        }
        $this->ossClient->uploadFile($this->bucket,$fileName,$fileObject);
        return true;
    }
}