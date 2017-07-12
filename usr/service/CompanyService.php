<?php
/**
 * File: CompanyService.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-04-15 1:27
 */

namespace service;


use Models\DataStatus;
use models\MemberCompany;

class CompanyService
{
    /**
     * @var \models\MemberCompany
     */
    private $companyModel;

    public function __construct()
    {
        $this->companyModel = new MemberCompany();
    }

    /**
     * @param $memberId
     * @return \Models\MemberCompany|null
     */
    public function getCompanyByMemberId($memberId)
    {
        return $this->companyModel->find(['member_id' => $memberId, 'status[!]' => DataStatus::DELETE]);
    }

    public function getTopBySeq($count, $start = 0)
    {

        return $this->companyModel->findByCondition(['status' => DataStatus::NORMAL,
            'invalid_date[>]' => REQ_DATETIME], [$start, $count], ['seq DESC']);
    }

    public function getCount($condition = array())
    {
        return $this->companyModel->count($condition);
    }

}