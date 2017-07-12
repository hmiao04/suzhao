<?php
/**
 * File: UEditor.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-03-04 0:39
 */
namespace controller;

use Models\CommonCategory;
use Models\FileResource;
use Models\PictureModel;

class UEditor extends \BaseController
{
    private $file_key = 'upfile';
    public function init()
    {
        $this->addRoute('/ueditor.html', 'process');
    }

    /**
     * {"state":"SUCCESS","url":"/ueditor/php/upload/image/20170304/1488560025355012.png","title":"1488560025355012.png","original":"ixjd.png","type":".png","size":585286}
     * {"upfile":{"name":"ixjd.png","type":"image\/png","tmp_name":"C:\\Windows\\Temp\\php533F.tmp","error":0,"size":585286},"state":"ERROR"}
     */
    private function uploadImage()
    {
        $message = 'SUCCESS';
        $data = array();
        try {
            $_GET['cate_id'] = 1;
            $picture = $this->saveUploadImage($this->file_key);
            if ($picture != null) {
                $file_obj = $_FILES[$this->file_key];
                $res_url = $this->getConfig('upload')->getConfig('url')->getValue();
                $data = [
                    "url" => $res_url . $picture,
                    "title" => $picture,
                    "original" => $picture,//$file_obj['name'],
                    "type" => '.'.getFileSuffix($picture),
                    "size" => $file_obj['size']
                ];
            }else {
                $message = '上传失败';
            }

        } catch (\Exception $e) {
            $message = $e->getMessage();
        }
        $this->outData($message,$data);
    }

    private function outData($state = 'SUCCESS', $data = array())
    {
        $ret = array_merge($data, ['state' => $state]);
        exit(json_encode($ret, JSON_UNESCAPED_UNICODE));
    }

    public function process()
    {
        $CONFIG = json_decode(preg_replace("/\/\*[\s\S]+?\*\//", "", file_get_contents(APP_DIR . "/usr/var/ueditor_config.json")), true);
        $action = $this->input()->get('action');
        $result = '';
        switch ($action) {
            case 'config':
                $result = json_encode($CONFIG,JSON_UNESCAPED_UNICODE);
                break;
            /* 上传图片 */
            case 'uploadimage':
                $this->uploadImage();
                break;
            case 'dirs':
                $cate = new CommonCategory();
                $dirs = $cate->findByCondition(['type'=>'picture']);
                $result = json_encode(array(
                    "state" => "SUCCESS",
                    "list" => $dirs
                ),JSON_UNESCAPED_UNICODE);
                break;
            /* 上传图片 */
            case 'listimage':
                $res = new PictureModel();
                $start = $this->input()->get('start');
                $size = $this->input()->get('size');
                $cate_id = $this->input()->get('cate_id');
                $resUrl = $this->getConfig('upload')->getConfig('url')->getValue();
                list($list,$totalCount) = $res->getListByCateId($cate_id,$this->getLoginMemberId(),[$start,$size]);
                $picture = [];
                foreach($list as $item){
                    $picture[] = [
                        'url'=>$resUrl.$item['file_name'],
                        'mtime'=>$item['file_time'],
                        'type'=>'img'
                    ];
                }
                $picture[] = [
                    'name'=>'aa',
                    'type'=>'dir',
                    'id'=>1
                ];
                $result = json_encode(array(
                    "state" => "SUCCESS",
                    "list" => $picture,
                    "start" => $start,
                    "total" => $totalCount
                ),JSON_UNESCAPED_UNICODE);
                break;
            default:
                $result = json_encode(array(
                    'state' => '请求地址出错'
                ),JSON_UNESCAPED_UNICODE);
                break;
        }
        /* 输出结果 */
        if (isset($_GET["callback"])) {
            if (preg_match("/^[\w_]+$/", $_GET["callback"])) {
                echo htmlspecialchars($_GET["callback"]) . '(' . $result . ')';
            } else {
                echo json_encode(array(
                    'state' => 'callback参数不合法'
                ), JSON_UNESCAPED_UNICODE);
            }
        } else {
            echo $result;
        }
    }
}