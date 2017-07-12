<?php
/**
 * File: Company.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-05-01 5:02
 */

namespace Controller\Admin;


use Models\CompanyType;
use Models\DataStatus;
use Models\LogAction;
use Models\LogState;
use models\MemberCompany;

class Company extends \AdminController
{

    public function init()
    {
        $this->addAdminUrl('company/view.{page}', 'pageView');
        $this->addAdminUrl('company/company.info', 'companyInfo');
        $this->addAdminUrl('company/company.update', 'companyUpdate');
    }

    /**
     * @param $page
     */
    public function pageView($page)
    {
        $allowView = array(
            'company-list' => 'getAllCompany'
        );
        parent::processPageView($page, $allowView);
    }

    public function companyUpdate(){
        $company = new MemberCompany();
        $data_id = $this->getRequestId();
        $company->setProperty($this->input()->request());
        $status = $this->input()->request('status');
        if($status && $status == 1){
            //TODO add 12 month
            $company->invalid_date = date('Y-m-d H:i:s',strtotime(REQ_DATETIME . ' + 12 month'));
        }
        try {
            $company->update(['company_id' => $data_id]);
            $this->recordLog(LogAction::UPDATE, $this->getLoginAdminId(), LogState::SUCCESS);
            ajaxSuccess();
        } catch (\Exception $e) {
            $this->recordLog(LogAction::UPDATE, $this->getLoginAdminId(), LogState::FAILED);
            ajaxException($e);
        }
    }
    public function companyInfo(){
        $tsk = new MemberCompany();
        $condition = [
            'company_id' => $this->getRequestId(),
            'status[!]' => DataStatus::DELETE
        ];
        $data = $tsk->find($condition);
        if (!$data) ajaxError('请求的数据不存在');
        $action = $this->input()->get('action');
        $tsk->company_type = CompanyType::Format($tsk->company_type);
        $this->assign('company', $tsk);
        $this->assign('operation', $action == 'operation');
        //实地认证状态(0:未认证,1:已认证,2:未支付,待认证,3:已支付,待认证)
        $this->renderAjax('company-info');
    }
    protected function getAllCompany()
    {
        $companyModel = new MemberCompany();
        list($page, $size, $star) = $this->getPageAndSize(15);//array(page,size,start)
        $condition = array('status[!]' => DataStatus::DELETE);

        $queryParam = $this->getQueryParam();
        $queryString = http_build_query($queryParam['page']);
        $condition = array_merge($condition, $queryParam['query']);

        $list = $companyModel->getCompanyAndMember($condition,[$star,$size],['seq DESC','company_id DESC']);

        $totalCount = $companyModel->count($condition);
        $this->createWindowPageLink('view.company-list?' . $queryString, $totalCount, $size);
        foreach($list as &$item){
            $item['company_type'] = CompanyType::Format($item['company_type']);
        }
        $this->assign('companyList', $list);

        if (isAjax()) {
            $this->render('company-list-item');
            exit;
        }
        $this->assign('companyTypes',CompanyType::$AllType);
    }
}