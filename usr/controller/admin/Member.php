<?php
/**
 * Created by PhpStorm.
 * User: yancheng<cheng@love.xiaoyan.me>
 * Date: 16/10/31
 * Time: 下午11:00
 */

namespace Controller\Admin;


use Lib\AccountUtil;
use Lib\DBUtil;
use Models\AdministratorModel;
use Models\AMPReport;
use Models\CertificationCompany;
use Models\CertificationData;
use Models\CompanyModel;
use Models\LocalAccount;
use Models\LogAction;
use Models\LogState;
use Models\MemberCertification;
use Models\MemberModel;
use models\MemberType;
use Models\PartnerAccount;
use Models\SocialAccount;

class Member extends \AdminController
{
    private $resetPwd = '123456';

    public function init()
    {
        $this->addAdminUrl('member/view.{page}', 'pageView');
        $this->addAdminUrl('api/member.info', 'getMemberInfo');
        $this->addAdminUrl('api/member.certification-info', 'getMemberCertification');
        $this->addAdminUrl('api/member.certification.verify', 'verifyMemberCertification');
        $this->addAdminUrl('api/member.save', 'saveMemberInfo');
        $this->addAdminUrl('api/member.delete', 'deleteMemberInfo');
        $this->addAdminUrl('api/member.local_account.reset', 'resetLocalAccount');
        $this->addAdminUrl('api/member.partner_account.unbind', 'unbindPartnerAccount');
        $this->resetPwd = $this->getConfig('app')->getConfig('member')->getConfig('reset')->getValue();
    }

    public function resetLocalAccount()
    {
        $aid = $this->input()->get('aid');
        if (!$aid || $aid < 1) {
            throw new \AppException('请求的资源暂未找到(aid null)');
        }
        $account = new LocalAccount();
        $account->id = $aid;
        if (!$account->find()) {
            throw new \AppException('请求的资源暂未找到(aid error)');
        }

        list($pwd, $salt) = AccountUtil::GetUserPassword($account->mid, $account->login_account, md5($this->resetPwd));
        $account->login_pass = $pwd;
        $account->salt = $salt;
        if ($account->update()) {
            ajaxSuccess(array(
                'login_pwd' => $this->resetPwd,
                'message' => '密码已经被重置为:' . $this->resetPwd));
        }
        ajaxResponse(1, '重置密码失败了');
    }

    public function unbindPartnerAccount()
    {
        $aid = $this->input()->get('aid');
        if (!$aid || $aid < 1) {
            throw new \AppException('请求的资源暂未找到(aid null)');
        }
        $account = new SocialAccount();
        $account->id = $aid;
        if ($account->find()) {
            $account->delete(array('id' => $account->id));
        }
        ajaxSuccess(array('message' => '解除绑定成功'));
    }

    public function getMemberInfo()
    {
        $mid = $this->input()->get('id');
        if (!$mid || $mid < 1) {
            ajaxResponse(-1, '没有找到要获取的数据(id null)', array());
        }
        $memberModel = new MemberModel();
        $memberModel->id = $mid;
        if (!$memberModel->find()) {
            ajaxResponse(-1, '没有找到要获取的数据(id error)', array());
        }
//        $memberModel->register_time = date('Y-m-d H:i',$memberModel->register_time)
        ajaxSuccess($memberModel);
    }

    public function verifyMemberCertification()
    {

        $mid = $this->input()->post('mid');
        if (!$mid || $mid < 1) {
            ajaxResponse(-1, '没有找到要获取的数据(id is null)', array());
        }
        $status = $this->input()->post('certification_status');
        $memberCert = new MemberCertification();
        if (!in_array($status, array(1, 2, 3))) {
            ajaxError('认证状态不正确', 2);
        }
        $remark = $this->input()->post('remark');
        $certData = $memberCert->findByPrimary($mid);
        if ($certData == null) ajaxError('该用户暂时还没有提交过认证资料');
        try {
            $memberCert->update(array('mid' => $mid), array(
                'certification_status' => $status,
                'certification_time' => REQ_TIME,
                'remark' => $remark
            ));
            if ($certData->type == CertificationCompany::$TYPE) {
                $m = new MemberModel();
                $m->update(array('id' => $mid), array('type_id' => MemberType::$Company));//如果认证为公司则更改用户类型
            }
            ajaxSuccess();
        } catch (\Exception $e) {
            ajaxError('认证用户资料失败,请刷新网页或者重试');
        }
    }

    public function getMemberCertification()
    {
        $mid = $this->input()->get('mid');
        if (!$mid || $mid < 1) {
            ajaxResponse(-1, '没有找到要获取的数据(id is null)', array());
        }
        $memberCert = new MemberCertification();
        $certData = $memberCert->findByPrimary($mid);
        if ($certData == null) {
            ajaxError('该用户暂时还没有提交过认证资料');
        }
        $certData = $memberCert->getCertificationData();
        $this->render('member-certification', array('cert_data' => $certData));
    }

    public function deleteMemberInfo()
    {
        $mid = $this->input()->get('id');
        if (!$mid || $mid < 1) {
            ajaxResponse(-1, '没有找到要删除的会员信息(id null)', array());
        }
        $memberModel = new MemberModel();
        $memberModel->id = $mid;
        if (!$memberModel->find()) {
            ajaxResponse(-1, '没有找到要删除的会员信息(id error)', array());
        }
        try {
            $memberModel->createQuery()->exec('update yc_member set status=0 where id=' . $mid);
            ajaxSuccess();
        } catch (\Exception $e) {
            ajaxError(stringFormat('删除会员信息失败({0})', $e->getMessage()));
        }

    }

    public function saveMemberInfo()
    {
        if (isAjax()) {
            $memberModel = new MemberModel();
            $checkModel = new MemberModel();
            $this->checkDataNull(array(
                array('name', 301, '请填写会员姓名'),
//                array('province',302,'请填写省份'),
//                array('city',303,'请填写城市'),
//                array('country',304,'请选择区县'),
//                array('login_account',305,'请输入登录账号'),
//                array('login_pwd',306,'请输入登录密码')
            ));
            $postData = $this->input()->post();
            $memberModel->setProperty($postData);
//            $this->apiTest($memberModel);
            //TODO 如果已经选择归属地,是否需要
            if ($memberModel->idcard) {//根据身份证号码确定户籍所在地
                $cityData = DBUtil::getIdCardArea($memberModel->idcard);
                if (isset($cityData[0]) && $cityData[0] > 0) $memberModel->province = $cityData[0];
                if (isset($cityData[1]) && $cityData[1] > 0) $memberModel->city = $cityData[1];
                if (isset($cityData[2]) && $cityData[2] > 0) $memberModel->country = $cityData[2];
            }
            if ($memberModel->id < 1) { // 新增

                $memberModel->report_count = 0;

                if (!$postData['login_account']) {
                    ajaxResponse(305, '请输入登录账号');
                }
                if (!$postData['login_pwd']) {
                    ajaxResponse(306, '请输入登录密码');
                }
                if (AccountUtil::AccountExists($postData['login_account'])) {
                    ajaxResponse(107, '填写的登录账号已经存在了');
                }
                $memberModel->register_time = date('Y-m-d H:i:s', REQ_TIME);
                $memberModel->register_by = $this->getLoginAdminId();
                $memberModel->company_id = $this->getAdminCompanyId();

                if ($memberModel->insert()) {
                    AccountUtil::CreateLocalAccount($memberModel->id, $postData['login_account'], $postData['login_pwd']);
                    $this->recordAdminLog(LogAction::INSERT, LogState::SUCCESS, $memberModel->toArray());
                    ajaxSuccess();
                }
                $this->recordAdminLog(LogAction::INSERT, LogState::FAILED, $memberModel->toArray());
            } else {
                if ($memberModel->update()) { //更新数据
                    $this->recordAdminLog(LogAction::UPDATE, LogState::SUCCESS, $memberModel->toArray());
                    ajaxSuccess();
                }
                $this->recordAdminLog(LogAction::UPDATE, LogState::FAILED, $memberModel->toArray());
            }

        }
        ajaxResponse(-1, 'not support method');
    }

    /**
     * @param $page
     */
    public function pageView($page)
    {
        $allowView = array(
            'member-list' => 'getAllMember',
            'member-account-info' => 'getMemberAccount',
            'member-register' => '',
            'member-report' => 'getMemberReport',
        );
        parent::processPageView($page, $allowView);
    }

    public function getMemberAccount()
    {
        $mid = $this->input()->get('mid');
        if (!$mid || $mid < 1) {
            throw new \AppException('请求的资源暂未找到(mid error)');
        }
        $local = new LocalAccount();
        $partner = new SocialAccount();

        $local->mid = $mid;
        $list = $local->findConditionAll();
        $partner->mid = $mid;
        $partnerList = $partner->findConditionAll();

        $this->assign('local_account', $list);
        $this->assign('partner_account', $partnerList);
    }

    protected function getMemberReport()
    {
        $this->pushNavPath('会员报告查询');
        $this->setCurrentNav('/admin/member/view.member-list');
        $mid = $this->input()->get('uid');
        $report = new AMPReport();
        $report->member_id = $mid;

        list($page, $size, $star) = $this->getPageAndSize(15);//array(page,size,start)
        $report_list = $report->findByCondition(null, array($star, $size));
        $totalCount = $report->count();
        $this->assign('reportList', $report_list);
        $this->createWindowPageLink('view.member-report?uid=' . $mid, $totalCount, $size);
    }


    private function birthday($birthday)
    {
        if (!$birthday) return '';
        $age = strtotime($birthday);
        if ($age === false) return '';
        list($y1, $m1, $d1) = explode("-", date("Y-m-d", $age));
        $now = strtotime("now");
        list($y2, $m2, $d2) = explode("-", date("Y-m-d", $now));
        $age = $y2 - $y1;
        if ((int)($m2 . $d2) < (int)($m1 . $d1))
            $age -= 1;
        return $age;
    }

    protected function getAllMember()
    {
        $memberModel = new MemberModel();
        list($page, $size, $star) = $this->getPageAndSize(15);//array(page,size,start)
        $condition = array('m.status' => '1');

        $queryParam = $this->getQueryParam();
        $queryString = http_build_query($queryParam['page']);
        $condition = array_merge($condition, $queryParam['query']);
        list($list, $totalCount) = $memberModel->getMemberList($condition, array($star, $size));

        $this->createWindowPageLink('view.member-list?' . $queryString, $totalCount, $size);
        $this->assign('memberList', $list);

        if (isAjax()) {
            $this->render('member-list-item');
            exit;
        }
    }
}