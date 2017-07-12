<?php
/**
 * File: Goods.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-03-07 0:05
 */

namespace controller\admin;


use models\GoodsModel;
use Models\LogAction;
use Models\LogState;

class Goods extends \AdminController
{

    public function init()
    {
        $this->addAdminUrl('goods/view.{page}', 'pageView');
        $this->addAdminUrl('goods/change.status', 'changeStatus');
    }

    public function pageView($page)
    {
        $allowView = array(
            'goods-list' => 'getAllGoods'
        );
        parent::processPageView($page, $allowView);
    }

    public function getAllGoods()
    {
        $g = new GoodsModel();
        list($page, $size, $star) = $this->getPageAndSize(15); //array(page,size,start)
        $condition = [];
        $queryParam = $this->getQueryParam();
        $queryString = http_build_query($queryParam['page']);
        $condition = array_merge($condition, $queryParam['query']);
        $gbList = $g->findByCondition($condition, [$star, $size], 'g.id DESC');
        $totalCount = $g->count($condition);
        $this->createWindowPageLink('view.goods-list?' . $queryString, $totalCount, $size);
        $this->assign('goodsList', $gbList);
        if (isAjax()) $this->renderAjax('goods-list-item');

    }

    public function changeStatus()
    {
        $gid = $this->getRequestId();
        $g = new GoodsModel();
        $condition = ['g.id' => $gid, 'g.status[!]' => 0];
        if (!$g->find($condition)) ajaxError('没有找到该商品(ERROR_GOODS_ID');
        $status = $this->input()->get('status');
        if (!in_array($status, [0, 1, 2])) ajaxError('商品状态不正确(ERROR_GOODS_STATUS)');
        try {
            $g->update(['id' => $gid], ['status' => $status]);
            $this->recordAdminLog(LogAction::UPDATE);
            ajaxSuccess();
        } catch (\Exception $e) {
            $this->recordAdminLog(LogAction::UPDATE, LogState::FAILED);
            ajaxException($e);
        }
    }
}