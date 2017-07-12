<?php
/**
 * File: FileResource.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-02-17 19:15
 */

namespace Models;

/**
 * 文件资源
 * Class FileModel
 * @package Controller\Admin
 */
class FileResource extends \Model
{
    public $file_id;
    public $file_name;
    public $file_origin_name;
    public $file_size;
    public $file_mime_type;
    public $file_time;
    public $member_id;
    public $remark;
    public $status;

    public function __construct($data = null)
    {
        $this->setPrimaryKey('file_id');
        $this->setTableName('yc_file_resource');
        if($data) $this->setProperty($data);
    }

    public function saveResource($filename, $originName = '', $fileSize = 0, $remark = '', $type = 'application/octet-stream',$member_id= 0)
    {
        $this->file_name = $filename;
        $this->file_origin_name = $originName;
        $this->file_size = $fileSize;
        $this->remark = $remark;
        $this->file_time = REQ_TIME;
        $this->file_mime_type = $type;
        $this->member_id = $member_id;
        return $this->insert();
    }
}