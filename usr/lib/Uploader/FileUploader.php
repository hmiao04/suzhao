<?php
/**
 * File: FileUploader.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-02-28 10:03
 */

namespace Lib\Uploader;


class FileUploader implements UploadDriver
{

    private $upload_path;

    public function init($config)
    {
        $this->upload_path = $config['upload_path'];
    }

    public function upload($fileName, $fileObject, $fileInfo)
    {
        if(file_exists($this->upload_path.$fileName)) return true;
        if (@move_uploaded_file($fileObject, $this->upload_path.$fileName) == false) {
            throw new UploadException('上传的图片保存失败',3);
        }
        return true;
    }
}