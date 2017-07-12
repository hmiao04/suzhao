<?php
/**
 * File: ${FILE_NAME}.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2016-11-02 22:46
 */

namespace Models;

class SZfast extends \Model
{
    public $id;            //		编号
    public $find_title;    //		标题
    public $member_id;    //		发起用户编号
    public $wish_finish_time;//	希望完成时间
    public $paid_price;    //		完成酬劳
    public $main_image;    //		主图
    public $home_image;    //		主图
    public $find_brief;    //		要求
    public $find_content;//		详情内容
    public $created_date;//		创建时间
    public $seq;            //		排序
    public $status;        //		状态(1:未接 2:已接 3:取消 0:删除)

    public function __construct($id = null)
    {

        $this->setTableName('sz_fast_find');//设置表名
        $this->setPrimaryKey('id');//设置主键
        /*$this->types=array(
            'find_title'=>'string',
            'wish_finish_time'=>'date',
            'paid_price'=>'float',
            'main_image'=>'image',
            'find_brief'=>'string',
            'find_content'=>'string',
            'seq'=>'int',
            "id"=>"int"
        );*/
        if ($id != null) {
            $this->find(array('id' => $id));
        }

    }

    /**
     * 判断归属并获取速找数据
     * @param null $mid
     * @param null $id
     * @return $this|bool|null
     */
    public function isMyPage($mid = null, $id = null)
    {
        if ($id != null) $this->id = $id;
        if ($mid == null) $mid = $this->member_id;

        if ($this->id > 0 && $mid) {
            return $this->find(array('id' => $this->id, "member_id" => $mid));
        }
        return false;
    }

    public function setPropertyCheck($data, $ys)
    {
        $obj = new \ReflectionObject($this);
        foreach ($ys as $k => $k2) {
            $value = NULL;
            if ($this->types[$k2] == "image") {
                $fileName = $this->saveUploadImage($k);
                if ($fileName) {
                    $value = URL() . '/resource/' . $fileName;
                }
                continue;
            } else if ($data[$k]) {
                if ($data[$k] == "" || $data[$k] == null)
                    switch ($this->types[$k2]) {
                        case "bool":
                            $value = FALSE;
                            break;
                        case "int":
                            $value = -1;
                            break;
                        case "float":
                            $value = -1.0;
                            break;
                        case "string":
                            $value = "";
                            break;
                        case "date":
                            $value = "1900-1-1 0:0";//-2209017600000;
                            break;
                        default:
                            continue;
                    }
                else {
                    try {
                        switch ($this->types[$k2]) {
                            case "bool":
                                $value = $data[$k] ? TRUE : FALSE;
                                break;
                            case "int":
                                $value = intval($data[$k]);
                                break;
                            case "float":
                                $value = floatval($data[$k]);
                                break;
                            case "string":
                                $value = addslashes($data[$k]);
                                break;
                            case "date":
                                $value = date("Y-m-d H:i", $data[$k]);//date("U", $data[$k]);
                                break;
                            default:
                                continue;
                        }
                    } catch (Exception $e) {
                        return array($k2);
                    }
                }
            }

            if ($value != NULL && $obj->hasProperty($k2))
                $obj->getProperty($k2)->setValue($this, $value);
        }
    }

    /**
     * 保存上传的文件
     * @param $imageKey
     */
    protected function saveUploadImage($imageKey)
    {
        if ($imageKey && isset($_FILES[$imageKey]) && $_FILES[$imageKey]['size'] > 0) {
            $adsPic = $_FILES[$imageKey];
            if ($_FILES[$imageKey]['error'] > 0) {
                ajaxResponse(1, '图片上传出现错误', $adsPic);
            }
            //获取允许上床的图片类型及相应的后缀名
            $pictureTypes = $this->getConfig('upload')->getConfig('picture')->getValue();
            $savePath = $this->getConfig('upload')->getConfig('path')->getValue();
            $viewUrl = $this->getConfig('upload')->getConfig('url')->getValue();
            $picType = strtolower(file_get_mime($adsPic['tmp_name']));
            if (!$picType || !isset($pictureTypes[$picType])) {
                ajaxResponse(2, '上传的图片类型不正确');
            }
            //使用MD5保存文件 节约空间
            $saveFileName = $savePath . md5_file($adsPic['tmp_name']) . '.' . $pictureTypes[$picType];
            //图片的url
            $viewFileName = $viewUrl . md5_file($adsPic['tmp_name']) . '.' . $pictureTypes[$picType];
            if (!file_exists($saveFileName)) {//判断是否已经存在文件 不存在则保存
                // 开始保存文件
                if (@move_uploaded_file($adsPic["tmp_name"], $saveFileName) == false) {
                    ajaxResponse(3, '上传的图片保存失败');
                }
            }
            return $viewFileName;
        }
        return null;
    }
}