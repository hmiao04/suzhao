<?php
/**
 * File: Company.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-04-16 18:11
 */

namespace Controller\FrontPage;


use Lib\Bill\BillTool;
use Lib\WebController;
use Models\BillType;
use Models\CertificationCompany;
use Models\CertificationData;
use Models\CompanyType;
use Models\DataStatus;
use models\GoodsModel;
use Models\MemberCertification;
use models\MemberCompany;
use models\MemberType;
use service\CompanyService;

class Company extends WebController
{

    /**
     * @param string $url
     * @param callback $processMethod
     */
    private function addRouteUrl($url, $processMethod)
    {
        $this->addRoute('/company' . $url, $processMethod);
    }

    public function init()
    {
        $this->addRouteUrl('', 'showIndex');
        $this->addRouteUrl('/', 'showIndex');
        $this->addRouteUrl('/list.html', 'showIndex');
        $this->addRouteUrl('/info.html', 'viewCompanyInfo');
        $this->addRouteUrl('/update.html', 'updateCompanyInfo');
        $this->addRouteUrl('/search.html', 'searchGoodsSimple');
        $this->addRouteUrl('/pay.html', 'payYearFee');
    }
    public function payYearFee()
    {
        $memberId = $this->getLoginMemberId();
        $company = new MemberCompany();
        if(!$company->find(['member_id' => $memberId,'status[!]'=>DataStatus::DELETE])){
            throw new \AppException('请先完成商家入驻操作');
        }
        $startTime = $company->invalid_date;
        if (!$startTime || REQ_TIME >= strtotime($startTime)) {
            $startTime = REQ_DATETIME;
        }
        $endTime = date('Y-m-d H:i:s',strtotime($startTime . ' +' . COMPANY_YEAR_MONTH . ' month'));
        $billData = [
            'title' => '企业年费',
            'member_id' => $memberId,
            'company_id' => $company->company_id,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'vip_month' => COMPANY_YEAR_MONTH,
            'price' => COMPANY_YEAR_PRICE
        ];
        //TODO 创建订单 更新数据
        $sn = BillTool::Instance()->Create(COMPANY_YEAR_PRICE, $memberId, '企业年费', BillType::$CompanyFee, $billData);
        jump_url('../member/bill.html?bill_sn='.$sn);
    }
    public function before()
    {
        $this->setControllerRenderPath('front_page/company');
    }

    public function showIndex()
    {
        list($page, $size, $start) = $this->getPageAndSize(30, null, 30); //array(page,size,start)
        $company = new CompanyService();
        $companyList = $company->getTopBySeq($size,$start);
        $totalCount = $company->getCount(['status[!]' => DataStatus::DELETE]);
        $this->assign('companyList',$companyList);
        $this->createWindowPageLink('list.html', $totalCount, $size);
        $this->render('list');
    }

    public function viewCompanyInfo()
    {
        $id = $this->getNumber('id');
        if (!$id || $id < 1) throw new \Exception('要查看的公司信息不存在(INVALID_PARAM_ID)');
        $company = new MemberCompany();
        if (!$company->findByPrimary($id)) throw new \Exception('要查看的公司信息不存在(ERROR_PARAM_ID)');
        $g = new GoodsModel();
        list($page, $size, $start) = $this->getPageAndSize(30, null, 30); //array(page,size,start)
        $condition = ['g.status' => DataStatus::NORMAL];
        $goodsList = $g->findByCondition(['g.status' => DataStatus::NORMAL], [$start, $size], 'id ASC');
        foreach ($goodsList as &$item) {
            $item['goods_image'] = $item['goods_image'] ? json_decode($item['goods_image'], 1) : [];
            $item['main_image'] = count($item['goods_image']) > 0 ? $item['goods_image'][0] : 'default-image.jpg';
        }
        $totalCount = $g->count($condition);
        $this->createWindowPageLink('list.html', $totalCount, $size);
        $this->assign('goodsList', $goodsList);
        $company->company_desc = nl2br($company->company_desc);
        $this->render('info', ['company' => $company]);
    }

    public function updateCompanyInfo()
    {
        $this->checkMemberLogin();
        $data = $this->getLoginMemberInfo();
        $cert = $data->getCertificationData();
        if (!$cert || $cert->certification_status != CertificationData::$CERT_STATUS_PASSED) {
            jump_url(URL(1).'/member/identification.html');
            //throw new \AppException('请先实名后再进行此操作(ERROR_NEED_IDENTIFICATION)');
        }
//        if($cert->type != CertificationCompany::$TYPE && $data->type_id != MemberType::$Company){
//            throw new \AppException('此登录帐号无权访问此页面(ERROR_PERMISSION_COMPANY)');
//        }
        $company = new MemberCompany();
        $data = $company->find(['member_id' => $this->getLoginMemberId(),'status[!]'=>DataStatus::DELETE]);
        if (isAjax()) {
            $this->formHashIsValid();
            $company->setProperty($this->input()->post());
            $company->member_id = $this->getLoginMemberId();
            $company->status = 2;
            $company->update_date = date('Y-m-d');
            if ($data) {
                $company->update();
            } else {
                $company->create_date = date('Y-m-d');
                $company->insert();
            }
            ajaxSuccess();
        }
        $isCheck = $this->input()->get('action');
        if(!$company->company_id && $isCheck != 'checked'){
            $this->render('settled-in', ['company' => $company]);
        }else{
            $this->assign('companyTypes',CompanyType::$AllType);
            $status = 2;
            $showFee = false;
            if(!$company->company_id){
                $company->company_image = 'company-logo.jpg';
            }
            if(!$company->company_id && $isCheck && $isCheck == 'checked'){
                $status = 3;
            }else{
                $status = $company->status;
            }
            if($status != 3 && $status != 1){
                if(!$company->invalid_date || REQ_TIME > strtotime($company->invalid_date)){
                    $showFee = true;
                }
            }
            $this->render('update', ['company' => $company,'status'=>$status,'showFee' => $showFee]);
        }
    }
}