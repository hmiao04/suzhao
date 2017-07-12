<?php
/**
 * File: CommonCategoryService.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-04-27 1:06
 */

namespace service;


use Models\CommonCategory;
use Models\DataStatus;

class CommonCategoryService
{
    /**
     * @var \models\MemberCompany
     */
    private $categoryModel;

    public function __construct()
    {
        $this->categoryModel = new CommonCategory();
    }

    public function getCateById($cateId){
        return $this->categoryModel->findByPrimary($cateId);
    }

    public function getGoodsCategory()
    {
        $list = $this->categoryModel->findByCondition(['type' => 'goods', 'state' => DataStatus::NORMAL], null, 'id DESC');
        $list = array_value_toKey($list, 'id');
        $tmp = [];
        foreach ($list as $id => $c) {
            if ($c['parent_id'] == 0) {
                $c['child'] = [];
                $tmp[$id] = $c;
            } elseif (isset($tmp[$c['parent_id']])) {
                $tmp[$c['parent_id']]['child'][] = $c;
            }
        }
        return $tmp;
    }
}