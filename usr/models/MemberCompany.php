<?php
/**
 * File: MemberCompany.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-04-15 1:19
 */

namespace models;


class MemberCompany extends \Model
{
    public $company_id;
    public $company_name;
    public $member_id;
    public $company_type;
    public $company_image;
    public $company_phone;
    public $company_fax;
    public $company_qq;
    public $company_address;
    public $company_desc;
    public $update_date;
    public $create_date;
    public $invalid_date;
    public $seq;
    public $certification_status;
    public $status;

    public function __construct()
    {
        $this->setPrimaryKey('company_id');
        $this->setTableName('sz_member_company');
    }

    public function getCompanyAndMember($condition = null,$limit = null,$orderBy = null){
        if (null == $condition) $condition = $this->getNotNullArray();
        if(isset($condition['status'])) {
            $condition['c.status'] = $condition['status'];
            unset($condition['status']);
        }
        if(isset($condition['status[!]'])) {
            $condition['c.status[!]'] = $condition['status[!]'];
            unset($condition['status[!]']);
        }

        return DB()->field('c.*,m.name(member_name)')->table($this->getTableName() . '(c)')
            ->join([
                '[><]' . $this->getTableName(MemberModel::class) . '(m)' => ['c.member_id' => 'm.id']
            ])->where($condition)->orderBy($orderBy)->limit($limit)->select();
    }
}