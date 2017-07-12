<?php
/**
 * File: Common.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2016-11-06 13:15
 */

namespace Controller\Admin;

class Common extends \AdminController
{

    public function init()
    {
        $this->addRoute($this->adminBaseUrl . '/api/model.{tableName}.info', '_getModelDataInfo');
    }

    public function _getModelDataInfo($tableName)
    {
        $itemId = $this->input()->request('id');
        if(!$itemId){ajaxResponse(-1,'参数错误');}
        $primaryKey = isset($_GET['pk']) ? $this->input()->get('pk') : 'id';
        $data = DB()->table($tableName)->where($primaryKey,$itemId)->get();
        if($data){ajaxSuccess($data);}
        ajaxResponse(2,'没有找到要获取的数据',array());
    }
}