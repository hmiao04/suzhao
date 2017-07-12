<?php
/**
 * File: Goods.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-03-07 10:21
 */

namespace controller\FrontPage;


use Lib\WebController;
use Models\DataStatus;
use Models\GoodsColor;
use models\GoodsModel;
use service\CommonCategoryService;
use Service\GoodsService;

class Goods extends WebController
{
    /**
     * @param string $url
     * @param callback $processMethod
     */
    private function addRouteUrl($url, $processMethod)
    {
        $this->addRoute('/goods' . $url, $processMethod);
    }

    public function init()
    {
        $this->addRouteUrl('/', 'showList');
        $this->addRouteUrl('', 'showList');
        $this->addRouteUrl('/list.html', 'showList');
        $this->addRouteUrl('/publish.html', 'viewPublish');
        $this->addRouteUrl('/detail.html', 'viewGoods');
        $this->addRouteUrl('/disabled', 'disableGoods');
        $this->addRouteUrl('/enable', 'enableGoods');
        $this->addRouteUrl('/delete', 'deleteGoods');
        $this->addRouteUrl('/search.simple', 'searchGoodsSimple');

        $this->addRouteUrl('/save.html', 'savePublish');
    }

    public function before()
    {
        $this->setControllerRenderPath('front_page/goods');
    }

    public function savePublish()
    {
        $this->checkMemberLogin();
        $g = new GoodsModel();
        $g->created_date = REQ_TIME;
        $g->member_id = $this->getLoginMemberId();
        $g->setProperty($this->input()->post());
        $g->goods_image = $g->goods_image ? json_encode($g->goods_image) : null;
        $g->goods_content = $_POST['goods_content'];
        $g->goods_color = $g->goods_color ? implode(',', $g->goods_color) : '';
        if ($g->id > 0) {
            $g->update();
        } else {
            $g->insert();
        }
        ajaxSuccess();
    }

    public function viewGoods()
    {
        $gid = $this->input()->get('id');
        if (!$gid || preg_match('/^[\d]+$/', $gid) == false) {
            throw new \AppException('您浏览的商品不存在');
        }
        $g = new GoodsModel();
        $condition = ['g.id' => $gid, 'g.status[!]' => 0];
        if (!$g->find($condition) || $g->status != 1) throw new \AppException('您浏览的商品已下架或已被删除');
        $g->goods_image = $g->goods_image ? json_decode($g->goods_image, 1) : [];
        if (isAjax()) {
            ajaxSuccess($g);
        }
        if ($g->goods_color) {
            $colors = GoodsColor::ColorName(explode(',', $g->goods_color));
            $g->goods_color = implode(',', $colors);
        } else {
            $g->goods_color = '-';
        }
        $this->render('detail', ['goods' => $g]);
    }

    public function disableGoods()
    {
        //GoodsStatus
        $this->changeStatus(2);
    }

    public function enableGoods()
    {
        //GoodsStatus
        $this->changeStatus(1);
    }

    public function deleteGoods()
    {
        $this->changeStatus(DataStatus::DELETE);
    }


    private function changeStatus($status)
    {
        $gid = $this->input()->get('id');
        if (!$gid || preg_match('/^[\d]+$/', $gid) == false) {
            throw new \AppException('您浏览的商品不存在');
        }
        $g = new GoodsModel();
        $condition = ['g.id' => $gid];
        if (!$g->find($condition)) {
            throw new \AppException('您浏览的商品不存在');
        }
        if ($g->member_id != $this->getLoginMemberId()) {
            throw new \PermissionException();
        }
        $g->update(['id' => $gid], ['status' => $status]);
        ajaxSuccess();
    }


    public function searchGoodsSimple()
    {
        $g = new GoodsModel();
        $goods_name = $this->input()->get('goods_name');
        $condition = ['g.status' => DataStatus::NORMAL];
        $queryString = 'yc=1';
        if ($goods_name) {
            $condition['g.title[~]'] = $goods_name;
            $queryString = http_build_query(['goods_name' => $goods_name]);
        }
        list($page, $size, $star) = $this->getPageAndSize(10, null, 10);//array(page,size,start)
        $totalCount = $g->count($condition);
        $goods_list = $g->findByCondition($condition, array($star, $size), 'g.id DESC');
        foreach ($goods_list as &$item) {
            $item['goods_image'] = $item['goods_image'] ? json_decode($item['goods_image'], 1) : [];
            $item['main_goods_image'] = count($item['goods_image']) > 0 ? $item['goods_image'][0] : 'default-image.jpg';
        }
        $this->assign('goods_list', $goods_list);
        $this->createWindowPageLink(URL() . '/goods/search.simple?' . $queryString, $totalCount, $size);
        $this->render('goods-media-item', [], 'front_page/part');
    }

    public function showList()
    {

        $gs = new GoodsService();
        $condition = [];
        $categoryId = $this->input()->get('category');
        $color = $this->input()->get('color');
        list($page, $size, $start) = $this->getPageAndSize(30, null, 30);//array(page,size,start)

        $lastdigit = substr("$categoryId", -3);
//        echo 'last=' .$lastdigit . "<br>";
        if (!($lastdigit === "000")) {
        list($goodsList, $totalCount) = $gs->searchByCateAndColor($categoryId, $color, $start);
        }elseif ($lastdigit === "000") {
         //  echo "//////////////////";
        }

        $allGet = $this->input()->get();
        $queryString = '';
        if ($allGet) {
            $queryString = '?' . http_build_query($allGet);
        }
        $this->createWindowPageLink('list.html' . $queryString, $totalCount, $size);


        $ccs = new CommonCategoryService();
        $this->assign('colorList', GoodsColor::$AllColor);
        $page = 'list';
        if (isAjax()) {
            $page = 'list-item';
        }
        $this->render($page, ['goodsList' => $goodsList, 'categoryList' => $ccs->getGoodsCategory()]);

    }

    public function viewPublish()
    {
//     $this->checkMemberLogin();
 //      $this->checkVIPMemberLogin();
        if (!($this->checkVIPMemberLogin())) {
            //$this->render('login-frame');
            ajaxResponse(403, '请先<a href="' . $url . '">认证</a>后在进行操作');
            exit;
        }
//     $s = $this->getLoginMemberId();
/*       echo "Id="  . $s;
           if(strtoupper($this->checkMemberLogin()) == "TRUE"){
                echo " Is Login";
            }else{
             //   echo " not Login";
            }
*/
        $ccs = new CommonCategoryService();
        $this->assign('colorList', GoodsColor::$AllColor);
        $gid = $this->input()->get('id');
        $g = new GoodsModel();
        if ($gid && preg_match('/^[\d]+$/', $gid)) {
            $g->find(['g.id' => $gid]);
        }
        $this->assign('g', $g);
        $this->render('publish', array("acviteIndex" => 3, 'categoryList' => $ccs->getGoodsCategory()));
    }
}