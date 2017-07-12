<?php
/**
 * File: Member.php:newsys
 * User: xiaoyan f@yanyunfeng.com
 * Date: 2017/1/4
 * Time: 22:33
 * @Description
 */
namespace Controller\FrontPage;

use Lib\AccountUtil;
use Lib\Bill\BillTool;
use Lib\WebController;
use Models\BillModel;
use Models\BillType;
use Models\CommonCategory;
use Models\CommonMode;
use Models\DataStatus;
use Models\GoodsColor;
use models\GoodsModel;
use models\GoodsStatus;
use Models\LocalAccount;
use models\MemberCompany;
use Models\MemberModel;
use Models\PictureModel;
use Models\ReportCategory;
use Models\ReportItem;
use Models\SharePicture;
use Models\UserGroupBuy;
use Models\UserGroupJoin;
use Service\BillService;
use service\CompanyService;
use Service\IdentificationService;
use Service\TaskService;

class Member extends WebController
{
    /**
     * @param string $url
     * @param callback $processMethod
     */
    private function addRouteUrl($url, $processMethod)
    {
        $this->addRoute('/member' . $url, $processMethod);
    }

    public function init()
    {
        $this->addRouteUrl('/identification.html', 'showIdentification');
        $this->addRouteUrl('/bill.html', 'bills');

        $this->addRouteUrl('/tuan.html', 'tuanList');
        $this->addRouteUrl('/tuan-join.html', 'tuanJoinList');
        $this->addRouteUrl('/tuan-accept.html', 'tuanAcceptList');

        $this->addRouteUrl('/picture.html', 'showPictures');
        $this->addRouteUrl('/upload-picture.html', 'showPictureUpload');
        $this->addRouteUrl('/goods.html', 'showGoods');
        $this->addRouteUrl('/avatar.upload', 'uploadAvatar');
        $this->addRouteUrl('/info.html', 'info');
        $this->addRouteUrl('/count.html', 'count');
        $this->addRouteUrl('/center.html', 'index');
        $this->addRouteUrl('/setting.html', 'setting');
        $this->addRouteUrl('/member.update', 'updateMemberInfo');
        $this->addRouteUrl('/member.password', 'changePassword');

        $this->addRouteUrl('/task.html', 'myTaskList');
        $this->addRouteUrl('/task-my.html', 'myGetTaskList');

        $this->addRouteUrl('/vip.html', 'vipInfo');
        $this->addRouteUrl('/join-vip.html', 'joinVip');
        $this->addRouteUrl('/join-vip2.html', 'joinVip2');

        $this->addRouteUrl('/', 'index');
        $this->addRouteUrl('', 'index');
    }

    public function before()
    {
        $this->checkMemberLogin();
        $this->setControllerRenderPath('front_page/member');
    }

    public function vipInfo()
    {
        $companyService = new CompanyService();
        $data = $companyService->getCompanyByMemberId($this->getLoginMemberId());
        $this->assign('company', $data);
        $this->assign('vip_status', 0);
        if ($data) {
            $this->assign('vip_status', $data->certification_status);
            if ($data->certification_status == 2) {
                $billService = new BillService();
                $billData = $billService->getLastBillDataByType(BillType::$Certification);
                if ($billData) {
                    $this->assign('billData', $billData);
                } else {
                    $this->assign('vip_status', 0);
                }
            }
        } else {
            $this->showIdentificationError();
        }
        $this->render('vip');
    }

    private function showIdentificationError()
    {
        $this->render('identification-error');
        exit;
    }

    /**
     * 实地认证
     */
    public function joinVip2()
    {
        $member = $this->getLoginMemberInfo();
        $memberId = $member->id;
        $company = new MemberCompany();
        if (!$company->find(['member_id' => $memberId, 'status[!]' => DataStatus::DELETE])) {
            $this->showIdentificationError();
//            throw new \AppException('请先完成商家入驻操作');
        }
        $billService = new BillService();
        $certificationBillData = $billService->getLastBillDataByType(BillType::$Certification);
        if ($certificationBillData) {
            jump_url('bill.html?bill_sn=' . $certificationBillData->bill_sn);
        }
        $billData = [
            'title' => '实地认证手续费',
            'member_id' => $memberId,
            'company_id' => $company->company_id,
            'price' => CERTIFICATION_PRICE
        ];
        print_r(['member_id' => $company->company_id]);
        //更新认证状态
        $company->update(['company_id' => $company->company_id], ['certification_status' => 2]);
        //TODO 创建订单 更新数据
        $sn = BillTool::Instance()->Create(CERTIFICATION_PRICE, $memberId, '实地认证手续费', BillType::$Certification, $billData);
        jump_url('bill.html?bill_sn=' . $sn);
    }

    public function joinVip()
    {
        $member = $this->getLoginMemberInfo();
        $startTime = $member->vip_time;
        $memberId = $member->id;
        //strtotime('+1 d',strtotime('2009-07-08'))
        $startTime = $member->vip_time;
        if (!$startTime || REQ_TIME >= strtotime($member->vip_time)) {
            $startTime = REQ_DATETIME;
        }
        $endTime = date('Y-m-d H:i:s', strtotime($startTime . ' +' . VIP_MONTH . ' month'));
        $billData = [
            'title' => '开通会员',
            'member_id' => $memberId,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'vip_month' => VIP_MONTH,
            'price' => VIP_PRICE
        ];
        //TODO 创建订单 更新数据
        $sn = BillTool::Instance()->Create(VIP_PRICE, $memberId, '账号开通会员', BillType::$Vip, $billData);
        jump_url('bill.html?bill_sn=' . $sn);
    }

    public function index()
    {

        $userInfo = $this->getLoginUser();
        $this->assign('user_info', $this->getLoginUser());
//        if ($userInfo != null) {
//            if ($userInfo->extraData['Certification'] && $userInfo->extraData['Certification']['certification_data']) {
//                $userInfo->extraData['Certification']['certification_data'] =
//                    print_r($userInfo->extraData['Certification']['certification_data'], 1);
//            }
//            $this->assign('user_info_array', print_r($userInfo->toArray(), 1));
//        }


        $this->render('index', [
            'ses_key' => $this->getConfig('session')->getConfig('id')->getValue(),
            'ses_id' => session_id(),
            'timestamp' => REQ_TIME,
            'token' => md5(REQ_TIME . '_unique_salt')
        ]);
    }

    public function myGetTaskList()
    {
        $tskService = new TaskService();
        list($page, $size, $start) = $this->getPageAndSize(15); //array(page,size,start)
        $status = 0;
        $this->assign('taskList', $tskService->getAcceptTask($this->getLoginMemberId(), $status, $start, $size));
        $this->render('my-task-list');
    }

    public function myTaskList()
    {
        $tskService = new TaskService();
        list($page, $size, $start) = $this->getPageAndSize(15); //array(page,size,start)
        $status = 0;
        $this->assign('taskList', $tskService->getTaskByMember($this->getLoginMemberId(), $status, $start, $size));
        $this->render('task-list');
    }

    public function info()
    {
        $this->render('member-info');
    }

    public function count()
    {
        $this->render('member-count');
    }

    public function bills()
    {
        $bill = new BillModel();
        $bill_sn = $this->input()->get('bill_sn');
        if ($bill_sn && preg_match('/^\d+$/', $bill_sn)) {
            if (!$bill->findByPrimary($bill_sn)) {
                throw new \AppException('该账单不存在');
            }
            if ($bill->status != DataStatus::NORMAL) throw new \AppException('该账单不存在');
            if ($bill->member_id != $this->getLoginMemberId()) throw new \AppException('该账单不存在(Invalid_Member)');
            $bill->bill_data = BillTool::Instance()->GetProcess($bill->bill_type)->parse($bill->bill_data);
            $this->assign('bill_info', $bill);
            $this->render('bill-info');
            exit;
        }

        $billList = $bill->findByCondition(['member_id' => $this->getLoginMemberId(), 'status' => DataStatus::NORMAL], null, ['create_time DESC']);
        $this->render('bill-list', array(
            'timestamp' => REQ_TIME,
            'bill_list' => $billList,
            'token' => md5(REQ_TIME . '_unique_salt')
        ));
    }

    public function tuanList()
    {
        $mid = $this->getLoginMemberId();
        $t = new UserGroupBuy();
        list($page, $size, $star) = $this->getPageAndSize(15); //array(page,size,start)
        $condition = [
            't.status' => DataStatus::NORMAL,
            't.member_id' => $mid
        ];
        $totalCount = $t->count($condition);
        $this->createWindowPageLink('tuan.html', $totalCount, $size);
        $this->assign('gbList', $t->findByCondition($condition, [$star, $size], 'id DESC'));
        $this->render('tuan-list');
    }

    public function tuanAcceptList()
    {
        $mid = $this->getLoginMemberId();//
        $t = new UserGroupJoin();
        list($page, $size, $star) = $this->getPageAndSize(15); //array(page,size,start)
        list($gbList, $totalCount) = $t->getAcceptTuan($mid, $star, $size);
        $this->createWindowPageLink('tuan-accept.html', $totalCount, $size);
        $this->assign('gbList', $gbList);
        $this->render('my-tuan-list');
    }

    public function tuanJoinList()
    {
        $mid = $this->getLoginMemberId();//
        $t = new UserGroupJoin();
        list($page, $size, $star) = $this->getPageAndSize(15); //array(page,size,start)
        $condition = [
            't.status' => DataStatus::NORMAL,
            'j.status' => DataStatus::NORMAL,
            'j.member_id' => $mid
        ];
        $totalCount = $t->count($condition);
        $this->createWindowPageLink('tuan-join.html', $totalCount, $size);
        $this->assign('gbList', $t->getJoinList($condition, [$star, $size], 'id DESC'));
        $this->render('tuan-join');
    }

    public function showPictureUpload()
    {
        $cate_id = $this->input()->request('cate_id');
        if (!$cate_id || !preg_match('/^\d+$/', $cate_id)) {
            throw new \AppException('上传目标不存在[ERROR_PARAM_CATEGORY_ID]');
        }
        $cate = new CommonCategory();
        if (!$cate->findByPrimary($cate_id)) {
            throw new \AppException('上传目标不存在[ERROR_CATEGORY_NOT_FOUND]');
        }
        $this->assign('current_cate', $cate);
        $this->assign('current_cate_id', $cate_id);
        $picture = new SharePicture();
        $picture->id = 0;
        $this->assign('picture', $picture);
        $this->render('picture-upload');
    }
    public function showPictures()
    {
        $cate = new CommonCategory();

        $mode = new CommonMode();

        $cateList = $cate->findByCondition(['type' => 'picture', 'state' => 1]);
        $modeList = $mode->findByCondition(['type' => 'mode', 'status' => 1]);
        $new_cateList = array_key_values($cateList, 'id');
        $new_modeList = array_key_values($modeList, 'id');

        $this->assign('cateList', $cateList);
        $this->assign('modeList', $modeList);
        $cate_id = $this->input()->request('cate_id');
        $mode_id = $this->input()->request('mode_id');

        if (!$cate_id || !in_array($cate_id, $new_cateList)) {
            $cate_id = $cateList[0]['id'];
        }
        if (!$mode_id || !in_array($mode_id, $new_modeList)) {
            $mode_id = $modeList[0]['id'];
        }

        $this->assign('current_id', $cate_id);
        $this->assign('current_mdid', $mode_id);
     // Now $mode_id and $modeList are ready



        $pic = new SharePicture();
        list($page, $size, $start) = $this->getPageAndSize(20, null, 20); //array(page,size,start)
        $condition = ['status' => DataStatus::NORMAL];

        if ($cate_id > 0) {
            $condition['cate_id'] = $cate_id;
        }
        $list = $pic->findByCondition($condition, [$start, $size], 'seq DESC');
        $totalCount = $pic->count($condition);
        $this->createWindowPageLink('list.html?cate_id=' . $cate_id, $totalCount, $size);
        $this->assign('picture_list', $list);
        $this->render('picture-list');
    }



    public function showGoods()
    {
        $mid = $this->getLoginMemberId();//

        $g = new GoodsModel();
        list($page, $size, $star) = $this->getPageAndSize(10, null, 10); //array(page,size,start)
        $condition = [
            'g.member_id' => $mid,
            'g.status[>]' => 0
        ];
        $gbList = $g->findByCondition($condition, [$star, $size], 'g.id DESC');
        $totalCount = $g->count($condition);
        $this->createWindowPageLink('goods.html', $totalCount, $size);
        $colors = [];
        foreach ($gbList as &$item) {
            $item['goods_image'] = $item['goods_image'] ? json_decode($item['goods_image'], 1) : [];
            $item['main_goods_image'] = count($item['goods_image']) > 0 ? $item['goods_image'][0] : 'default-image.jpg';
            if ($item['goods_color']) {
                $colors = GoodsColor::ColorName(explode(',',$item['goods_color']));
                $item['goods_color'] = implode(',',$colors );
            } else {
                $item['goods_color'] = '-';
            }
        }
        $this->assign('goodsList', $gbList);
        $this->render('goods-list', array(
            'timestamp' => REQ_TIME,
            'token' => md5(REQ_TIME . '_unique_salt')
        ));
    }

    public function showIdentification()
    {
        $mid = $this->getLoginMemberId();
        $this->render('identification', ['identification' => IdentificationService::GetIdentificationDataByMid($mid)]);
    }

    public function setting()
    {
        $this->render('member-setting', array(
            'timestamp' => REQ_TIME,
            'token' => md5(REQ_TIME . '_unique_salt')
        ));
    }

    public function updateMemberInfo()
    {
        $memberModel = new MemberModel();
        if ($memberModel->birth_date && preg_match('/^\d{4}(\-|\/|\.)\d{1,2}\1\d{1,2}$/', $memberModel->birth_date) == false) {
            ajaxError('出生日期格式有误');
        }
        $mid = $this->getLoginMemberId();
        $postData = $this->input()->post();
        $memberModel->setProperty($postData);
        if ($memberModel->update(['id' => $mid])) { //更新数据
            ajaxSuccess();
        }
        ajaxError('保存数据失败');
    }

    public function changePassword()
    {
        $ret = $this->checkDataNull(array(
            array('login_pwd', 301, '请填写旧密码'),
            array('new_pwd', 302, '请填写新密码'),
            array('new_pwd_1', 303, '请再次输入新密码')
        ), true, $this->input()->post(), true);
        $this->formHashIsValid();
        if ($ret['new_pwd'] != $ret['new_pwd_1']) ajaxError('两次输入的密码不一致');
        if ($ret['new_pwd'] == $ret['login_pwd']) ajaxError('新密码不能与旧密码相同');
        $account = new LocalAccount();
        $account->mid = $this->getLoginMemberId();
        if (!$account->find()) {
            ajaxResponse(-1, '登录信息异常');
        }
        $pwd = $this->input()->post('login_pwd');
        $pwd = strlen($pwd) != 32 ? md5($pwd) : trim($pwd);
        list($check_pwd, $salt) = AccountUtil::GetUserPassword($account->mid, $account->login_account, $pwd, $account->salt);
        if ($account->status != 1) {
            ajaxResponse(2, '该账号已被冻结');
        }
        if ($check_pwd != $account->login_pass) {
            ajaxResponse(3, '旧密码不正确', $check_pwd);
        }
        $new_pwd = $ret['new_pwd'];
        $new_pwd = strlen($new_pwd) != 32 ? md5($new_pwd) : trim($new_pwd);
        list($pwd, $salt) = AccountUtil::GetUserPassword($account->mid, $account->login_account, $new_pwd);
        $account->login_pass = $pwd;
        $account->salt = $salt;
        if ($account->update()) {
            ajaxSuccess();
        }
        ajaxResponse(1, '重置密码失败了');
    }

    public function uploadAvatar()
    {
        $this->checkMemberLogin();
        $targetUrl = $this->getConfig('upload')->getConfig('url')->getValue(); // Relative to the root

        $verifyToken = md5($this->input()->post('timestamp') . '_unique_salt');
        if ($this->input()->post('token') == $verifyToken) {
            $fileName = $this->saveUploadImage('Filedata');
            if ($fileName) {
                $member = new MemberModel();
                $member->avatar = $fileName;
                $member->update(array('id' => $this->getLoginMemberId()));
                ajaxSuccess($targetUrl . $fileName);
            }
            ajaxError('上传失败');
        }
        ajaxError('您上传的图片非法');
    }
}