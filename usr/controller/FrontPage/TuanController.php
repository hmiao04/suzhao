<?php
/**
 * File: Tuan.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-02-27 19:46
 */

namespace Controller\FrontPage;


use Controller\Admin\GroupBuy;
use Lib\Bill\BillTool;
use Lib\WebController;
use Models\BillType;
use Models\DataStatus;
use models\GoodsModel;
use models\GoodsStatus;
use Models\GroupBuyStatus;
use models\MemberType;
use Models\TuanBusiness;
use Models\UserGroupBuy;
use Models\UserGroupJoin;
use Service\TuanService;

class TuanController extends WebController
{
    /**
     * @param string $url
     * @param callback $processMethod
     */
    private function addRouteUrl($url, $processMethod)
    {
        $this->addRoute('/tuan' . $url, $processMethod);
    }

    public function before()
    {
        $this->setControllerRenderPath('front_page/tuan');
    }

    public function init()
    {
        $this->addRouteUrl('', 'showIndex');
        $this->addRouteUrl('/', 'showIndex');
        $this->addRouteUrl('/list.html', 'showDataList');
        $this->addRouteUrl('/detail.html', 'viewDetail');
        $this->addRouteUrl('/join.list', 'joinList');
        $this->addRouteUrl('/join.check', 'checkJoin');
        $this->addRouteUrl('/pay_manner', 'mannerPay');
        $this->addRouteUrl('/join.submit', 'saveJoinData');

        $this->addRouteUrl('/join.get', 'getTuanInfo');
        $this->addRouteUrl('/jie-tuan', 'jieTuan');

        $this->addRouteUrl('/save.html', 'saveTuanGouData');
        $this->addRouteUrl('/new.html', 'showPublish');
        $this->addRouteUrl('/delete', 'deleteTuan');
        $this->addRouteUrl('/publish', 'publishTuan');
        $this->addRouteUrl('/cancel', 'cancelTuan');

    }

    public function showIndex()
    {
        $tuan = new UserGroupBuy();
        $this->assign('recommendList', $tuan->getRecommend());
        $this->assign('newList', $tuan->getNewList());
        $this->render('index');
    }

    public function showDataList()
    {
        $tuan = new UserGroupBuy();
        $sort = 'id DESC';
        $queryString = '';
        if ($this->input()->get('sort') == 'top') {
            $sort = 'seq DESC';
            $queryString = '?sort=top';
        }
        $condition = ['t.status' => DataStatus::NORMAL];

        list($page, $size, $start) = $this->getPageAndSize(30, null, 30); //array(page,size,start)
        $this->assign('dataList', $tuan->findByCondition($condition, [$start, $size], $sort));
        $totalCount = $tuan->count($condition);
        $this->createWindowPageLink('list.html' . $queryString, $totalCount, $size);
        $this->render('list');
    }

    public function joinList()
    {
        $groupJoin = new UserGroupJoin();
        $groupJoin->status = 1;
        $id = $this->input()->get('id');
        $dataList = $groupJoin->findByCondition(['group_id' => $id]);
        $this->render('tuan-join', ['dataList' => $dataList]);
    }

    public function viewDetail()
    {
        $tuanService = new TuanService();
        $tid = $this->input()->get('id');
        if (!$tid || preg_match('/^[\d]+$/', $tid) == false) {
            throw new \AppException('该团购不存在或者已经取消');
        }
        $member_id = $this->getLoginMemberId();
        $g = new UserGroupBuy();
        $groupJoin = new UserGroupJoin();
        $condition = ['t.id' => $tid, 't.status' => DataStatus::NORMAL];
        if (!$g->find($condition) || $g->status != 1) {
            throw new \AppException('该团购不存在或者已经取消(ERROR_STATUS)');
        }
        if ($g->group_status == GroupBuyStatus::$Saved) {
            if ($g->member_id != $this->getLoginMemberId()) {
                throw new \AppException('该团购不存在或者已经取消(ERROR_GROUP_STATUS)');
            }
        }
        $goods = new GoodsModel();
        $g_info = $goods->findByPrimary($g->goods_id);
        $joinArray = [];//$groupJoin->findByCondition(['group_id' => $g->id, 'status' => DataStatus::NORMAL]);

        $this->assign('joinData', [
            'list' => [],
            'count' => $groupJoin->getJoinCount($g->id)
        ]);
        //是否参团
        $joined = $member_id < 1 ? false : $groupJoin->exists(['group_id' => $g->id, 'member_id' => $member_id, 'status[!]' => '0']);
        //获取参团数据
        $joinInfo = $groupJoin->getTuanCount($g->id);
        $bidding = false;//是否已经参与接团
        if ($member_id > 0) {
            $biddingData = $tuanService->getMemberBidding($this->getLoginMemberInfo(), $g->id);
            if ($biddingData) {
                $bidding = true;
                $this->assign('biddingData', $biddingData);
            }
        }
        $this->assign('bidding', $bidding);
        $this->assign('join_info', $joinInfo);
        $this->render('detail', ['tuanInfo' => $g, 'goodsInfo' => $g_info, 'joined' => $joined]);
    }

    public function jieTuan()
    {
        $this->needAjax();
        $g = $this->getTuanObject();
        if ($g->status >= 3) {
            ajaxError('该团购已经结束(ERROR_STATUS)');
        }
        $memberId = $this->getLoginMemberId();
        $tg = new TuanBusiness();
        $tg->member_id = $this->getLoginMemberId();
        $tg->tuan_id = $g->id;
        $tg->status = DataStatus::NORMAL;
        if ($this->input()->post('money') <= 0) {
            ajaxError('接团金额必须大于0');
        }
        if ($tg->exists()) {
            ajaxError('您已经参加过此团(ERROR_REPEAT)', 1, $tg->queryInfo());
        }
        $tg->setProperty($this->input()->post());
        try {
            $g->update(['id' => $g->id], ['group_status' => GroupBuyStatus::$Accepted]);//更新团购状态
            $tg->insert();
            if ($tg->lastInsertId > 0) {
                ajaxSuccess();
            }
        } catch (\Exception $e) {
            ajaxException($e);
        }
        ajaxError('接团操作失败');
    }

    /**
     * @return UserGroupBuy
     */
    private function getTuanObject()
    {
        $this->checkMemberLogin();
        $gid = $this->input()->get('id');
        if (!$gid || preg_match('/^[\d]+$/', $gid) == false) {
            ajaxError('该团购不存在或者已经取消', 1);
        }
        $g = new UserGroupBuy();
        if (!$g->find(['t.id' => $gid])) ajaxError('该团购不存在或者已经取消(ERROR_ID)', 2);
        if ($g->status == DataStatus::DELETE) ajaxError('该团购不存在或者已经取消(ERROR_STATUS)', 2);
        return $g;
    }

    public function getTuanInfo($returnData = false)
    {
        $this->needAjax();
        $g = $this->getTuanObject();
        $memberInfo = $this->getLoginMemberInfo();
        $member_id = $this->getLoginMemberId();
        if ($memberInfo->type_id != 1) {
            ajaxError('个人暂时无法接团(ERR_MEM_TYPE)', 5090);
        }
        if (!isset($memberInfo->extraData['Certification']) ||
            $memberInfo->extraData['Certification']['certification_status'] != 3
        ) {
            ajaxError('您的账号还未认证或者认证不通过(ERR_MEM_CERT)', 5091, $memberInfo);
        }
        $tg = new TuanBusiness();
        $tg->member_id = $this->getLoginMemberId();
        $tg->tuan_id = $g->id;
        $tg->status = DataStatus::NORMAL;
        if ($tg->exists()) {
            ajaxError('您已经参与过此团(ERROR_REPEAT)', 1, $tg->queryInfo());
        }
        if ($returnData) return true;
        ajaxSuccess();
    }


    public function checkJoin()
    {
        $this->needAjax();
        $member_id = $this->getLoginMemberId();
        $g = $this->getTuanObject();
        $groupJoin = new UserGroupJoin();
        if ($groupJoin->find(['group_id' => $g->id, 'status[!]' => '0', 'member_id' => $member_id])) {
            //if($groupJoin->status != 1) ajaxError('您暂时无法参与此团购',3);
            ajaxError('你已经参与过该团购', 4);
        }
        ajaxSuccess();
    }

    public function mannerPay()
    {
      //  $gb_id = $this->input()->request('id');
       //echo 'Enter maanerPay function';
      //  echo "gb_is=>" .  $gb_id ;

        $tuan = new UserGroupBuy();
        $this->assign('recommendList', $tuan->getRecommend());
        $this->assign('newList', $tuan->getNewList());
      //  $this->render('paymanner.twig');
          $this->render('paymenner');
    }

    public function saveJoinData()
    {
        $this->needAjax();
        $this->checkMemberLogin();
        //TODO 创建订单 更新数据
        $gid = $this->input()->get('id');
        if (!$gid || preg_match('/^[\d]+$/', $gid) == false) {
            ajaxError('该团购不存在或者已经取消', 1);
        }
        $g = new UserGroupBuy();
        if (!$g->find(['t.id' => $gid])) ajaxError('该团购不存在或者已经取消', 1);
        $groupJoin = new UserGroupJoin();
        $this->checkDataNull(array(
            array('buy_price', 2, '请填写登录账号'),
            array('buy_count', 3, '请选择归属公司'),
            array('receiver', 4, 'telephone'),
            array('receiver', 5, 'shipping_address'),
        ), 1, null, 1);
        $groupJoin->setProperty($this->input()->post());
        if ($groupJoin->buy_price <= 0) ajaxError('参团价格必须大于0', 2);
        if ($groupJoin->buy_count <= 0) ajaxError('参团数量必须大于0', 3);
        $groupJoin->group_id = $gid;
        $groupJoin->member_id = $this->getLoginMemberId();
        $groupJoin->join_time = REQ_TIME;//支付时间

        $groupId = $groupJoin->insert()->lastInsertId;//保存参团信息  并获取参团编号
        try {
            $totalMoney = $groupJoin->buy_price * $groupJoin->buy_count;
            if ($groupId > 0) {
                $billData = [
                    'title' => $g->title,
                    'group_id' => $groupJoin->group_id,
                    'join_time' => $groupJoin->join_time,
                    'join_id' => $groupId,//参团编号
                    'price' => $groupJoin->buy_price,
                    'count' => $groupJoin->buy_count,
                    'remark' => $groupJoin->remarks
                ];
                $payTitle = '参团' . $g->title;
                //TODO 创建订单 更新数据
                $sn = BillTool::Instance()->Create(
                    $totalMoney,
                    $this->getLoginMemberId(),
                    $payTitle,
                    BillType::$TuanGou,
                    $billData
                );
                $groupJoin->update(['id' => $groupId], ['bill_sn' => $sn]);
                ajaxSuccess(['bill_sn' => $sn]);
            }
        } catch (\Exception $e) {
            $groupJoin->delete(['id' => $groupId]);
        }
        ajaxError('参加团购失败!');
    }

    public function saveTuanGouData()
    {
        $this->needAjax();
        $this->checkMemberLogin();
        $this->checkDataNull(array(
            array('title', 2, '请输入团购标题'),
            array('main_image', 3, '请选择团购主图'),
            array('content', 4, '请输入团购详情'),
        ));

        $mid = $this->getLoginMemberId();
        $t = new UserGroupBuy();
        $t->setProperty($this->input()->post());
        if ($t->end_time) {
            $time = strtotime($t->end_time . ' 23:59:59');
            if ($time <= REQ_TIME) ajaxError('结束时间必须大于当前日期', 5);
            $t->end_time = $time;
        }
        $goods = new GoodsModel();
        if ($this->input()->post('save_action') == 're_save') {
            $t->id = 0;
            if ($t->goods_id > 0) {
                if ($goods->findByPrimary($t->goods_id)) {
                    if ($goods->status == GoodsStatus::$Invisible) {
                        $goods = new GoodsModel();
                        $t->goods_id = 0;
                    }
                }
            }
        }

        if ($t->goods_id > 0) {
            //选择的商品,需要判断是否存在
            if (!$goods->find(['g.id' => $t->goods_id])) ajaxError('团购的商品不存在,请重新选择团购商品', -1);
            if ($goods->status == GoodsStatus::$Invisible) { //可以更新商品
                $goods = new GoodsModel();
                $goods->price_sale = $this->input()->post('buy_price');
                $goods->price_original = $this->input()->post('buy_price');
                $goods->goods_content = $_POST['content'];
                $goods->update(['id' => $t->goods_id]);
            }
        } else {
            //没有选择商品则需要保存
            $goods->title = $t->title;
            $goods->goods_image = json_encode([$t->main_image]);
            $goods->created_date = REQ_TIME;
            $goods->status = GoodsStatus::$Invisible;
            $goods->price_sale = $this->input()->post('buy_price');
            $goods->price_original = $this->input()->post('buy_price');
            $goods->member_id = $mid;
            $goods->goods_content = $_POST['content'];
            $gid = $goods->insert()->lastInsertId;
            if ($gid < 1) ajaxError('保存团购商品信息失败', -1);
            $t->goods_id = $gid;
        }
        $t->create_time = REQ_TIME;
        $t->member_id = $mid;
        $t->group_status = $this->input()->get('action') == 'save' ? GroupBuyStatus::$Saved : GroupBuyStatus::$Published;
        if ($t->id > 0) {
            try {
                if ($t->update()) {
                    ajaxSuccess([
                        'id' => $t->id
                    ]);
                }
            } catch (\Exception $e) {
                ajaxException($e, 1, DB()->all_queryItem());
            }
        } else {
            try {
                if ($t->insert()) {
                    ajaxSuccess([
                        'id' => $t->lastInsertId
                    ]);
                }
            } catch (\Exception $e) {
                ajaxException($e);
            }
        }
        ajaxError('保存团购商品信息失败', -1);
    }

    public function showPublish()
    {
        $this->checkMemberLogin();
        $tuanInfo = new UserGroupBuy();
        if ($this->input()->get('src') == 'index') {
            $tuanInfo->setProperty($this->input()->post());
            $tuanInfo->extraData['buy_count'] = $this->input()->post('buy_count');
            $tuanInfo->extraData['tuan_price'] = $this->input()->post('tuan_price');
        }

        $join = new UserGroupJoin();
        $goods_status = 2;
        if ($this->input()->get('id')) {
            $tid = $this->getNumber('id');
            if ($tuanInfo->findByPrimary($tid)) {
                $tuanInfo->end_time = date('Y-m-d', $tuanInfo->end_time);
                $data = $join->find(['group_id' => $tid, 'member_id' => $this->getLoginMemberId()]);
                $g = new GoodsModel();
                $g->findByPrimary($tuanInfo->goods_id);
                $tuanInfo->extraData['tuan_price'] = $g->price_sale;
                if ($data) {
                    $tuanInfo->extraData['buy_count'] = $join->buy_count;
                    $tuanInfo->extraData['tuan_price'] = $g->price_sale;
                }
                $tuanInfo->extraData['tuan_content'] = $g->goods_content;
                if ($g && $g->status != GoodsStatus::$Invisible) {
                    $goods_status = 1;
                }
            }

        }
        $this->assign('goods_status', $goods_status);
        $this->assign('save_action', $this->input()->get('action'));
        $this->render('publish', ['t' => $tuanInfo, 'join' => $join]);
    }


    private function getTuanDataById()
    {
        //TODO 创建订单 更新数据
        $gid = $this->input()->get('id');
        if (!$gid || preg_match('/^[\d]+$/', $gid) == false) {
            ajaxError('该团购不存在或者已经取消', 1);
        }
        $g = new UserGroupBuy();
        if (!$g->find(['t.id' => $gid])) ajaxError('该团购不存在或者已经取消', 1);
        return $g;

    }

    public function changeTuanStatus($updateData)
    {
        $this->needAjax();
        $this->checkMemberLogin();
        $tuan = new UserGroupBuy();
        $tg = $this->getTuanDataById();
        if ($tg->member_id != $this->getLoginMemberId()) {
            ajaxError('您没有权限操作此团购');
        }
        try {
            $tg->update(['id' => $tg->id], $updateData);
            ajaxSuccess();
        } catch (\Exception $e) {
            ajaxException($e);
        }
    }

    public function publishTuan()
    {
        $this->changeTuanStatus(['group_status' => GroupBuyStatus::$Published]);
        ajaxError('发起团购失败');
    }

    public function cancelTuan()
    {
        $this->changeTuanStatus(['group_status' => GroupBuyStatus::$Saved]);
        ajaxError('取消团购失败');
    }

    public function deleteTuan()
    {
        $this->changeTuanStatus(['status' => DataStatus::DELETE]);
        ajaxError('删除团购数据失败');
    }
}