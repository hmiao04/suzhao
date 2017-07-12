<?php
/**
 * File: UploaderTool.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-02-28 9:43
 */

namespace Lib\Uploader;
class UploadException extends \YCRException
{
    function __construct($message, $code = 1, Exception $previous = null)
    {
        if ($code < 5000) $code += 5000;
        parent::__construct($message, $code, $previous);
    }
}

interface UploadDriver
{
    /**
     * @param $config
     * @return \Lib\Uploader\UploadDriver
     */
    public function init($config);

    /**
     * @param $fileName
     * @param $fileObject
     * @param $fileInfo
     * @return bool if upload success return true else false
     */
    public function upload($fileName, $fileObject, $fileInfo);
}

class UploaderTool
{
    /**
     * @var \Lib\Uploader\UploadDriver
     */
    private static $uploader = null;
    /**
     * @var \Lib\Uploader\UploaderTool
     */
    private static $instance = null;
    private $uploadConfig = array();


    private function  __construct()
    {
    }

    /**
     * @return UploaderTool
     * @throws \AppException
     */
    public static function GetUploader()
    {
        if (null != self::$instance) return self::$instance;
        $conf = \YCF::Instance()->getAppConfig('upload');
        $config = $conf[$conf['driver']];
        $driver = "\\Lib\\Uploader\\" . $conf['driver'] . "Uploader";
        if (!class_exists($driver)) {
            throw new \AppException('upload driver not exists');
        }
        $cls = new \ReflectionClass($driver);
        if (!$cls->isSubclassOf('\\Lib\\Uploader\\UploadDriver')) throw new \AppException('upload driver not implements UploadDriver');
        self::$uploader = $cls->newInstanceArgs();
        self::$uploader->init($config);
        self::$instance = new UploaderTool();
        self::$instance->uploadConfig = $conf;
        return self::$instance;
    }

    public function upload64($file_data)
    {

    }

    /**
     * 文件上传
     * @param array $file upload file object array
     * @param string $prefix upload file object array
     * @return null|string success will return filename or return null
     * @throws UploadException
     */
    public function upload($file,$prefix)
    {
        if ($file['error'] != UPLOAD_ERR_OK) {
            throw new UploadException('上传文件出现错误(FILE_UPLOAD_ERROR', 1);
        }
        if ($file['size'] < 1) {
            throw new UploadException('上传的文件出现异常(FILE_SIZE_ERROR)', 2);
        }
        $pictureTypes = $this->uploadConfig['picture'];
        $picType = strtolower(file_get_mime($file['tmp_name']));
        if (!$picType || !isset($pictureTypes[$picType])) {
            throw new UploadException('上传的图片类型不正确(FILE_TYPE_ERROR)', 2);
        }
        $fileName = null;
        $fileObject = $file['tmp_name'];
        switch($this->uploadConfig['file_name'])
        {
            case 'md5':
            case 'sha1':
            case 'crc32':
                $fileName = hash_file($this->uploadConfig['file_name'],$fileObject);
                break;
            default:
                $fileName = microtime().'_'.$file['size'];
                break;
        }
        $fileName = $fileName.'.'.$pictureTypes[$picType];
        $ret = self::$uploader->upload($fileName,$fileObject,array(
            'size'=>$file['size'],
            'type'=>$pictureTypes[$picType],
            'mime_type'=>$picType,
            'file_name'=>$file['name']));
        if($ret) return $fileName;
        return null;
    }
}