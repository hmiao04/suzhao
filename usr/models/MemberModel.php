<?php
/**
 * File: MemberModel.php:suixinlv
 * User: xiaoyan f@yanyunfeng.com
 * Date: 16-11-18
 * Time: 上午6:33
 * @Description
 */

namespace Models;

class MemberModel extends \Model
{
    public $id;
    public $name;
    public $avatar;
    /**
     * @var 用户类型ID(1:company;0:person)
     */
    public $type_id;
    public $gender;
    public $idcard;
    public $birth_date;
    public $phone;
    public $email;
    public $register_time;
    public $vip_time;
    public $province;
    public $city;
    public $country;
    public $points;
    public $status;

    public function __construct()
    {
        $this->setPrimaryKey('id');
        $this->setTableName('yc_member');
    }

    public function getMemberCount(array $condition)
    {
        return $this->createQuery()->table($this->getTableName())->where($condition)->count();
    }

    public function getMemberList(array $condition, $limit = null, $orderBy = null)
    {
        $list = $this->createQuery()->table($this->getTableName() . '(m)')
            ->field('m.*,c.mid,c.type,c.certification_status')->where($condition)->join(array(
                '[>]sz_member_certification(c)' => array('m.id' => 'c.mid')
            ))->orderBy('id DESC')->limit($limit)->select();

        $count = $this->createQuery()
            ->where($condition)
            ->join(array(
                '[>]sz_member_certification(c)' => array('m.id' => 'c.mid')
            ))->count($this->getTableName() . '(m)');
        return array($list, $count);
    }

    public function getMemberList1(array $condition, $limit = null, $orderBy = null)
    {
        $whereSQL = array();
        if ($condition) {
            foreach ($condition as $k => $v) {
                switch ($k) {
                    case 'id_card':
                    case 'email':
                    case 'name':
                    case 'phone':
                        $whereSQL[] = " {$k} like '%$v%' ";
                        break;
                    case 'company_code':
                        $whereSQL[] = " ({$k} between {$v[0]} and {$v[1]}) ";
                        break;
                    case 'company_code_max':
                        break;
                    case 'birth_date_start':
                        $whereSQL[] = " DATE_ADD(now(),INTERVAL -{$v} YEAR) >= birth_date ";
                        break;
                    case 'birth_date_end':
                        $whereSQL[] = " DATE_ADD(now(),INTERVAL -{$v} YEAR) <= birth_date ";
                        break;
                    default:
                        $whereSQL[] = " {$k}='$v' ";
                        break;
                }
            }
        }
        $whereSQL = implode('and', $whereSQL);
        $querySQL = "SELECT
	count(1)
FROM
	member AS m
WHERE
	{$whereSQL}";
        $list = $this->createQuery()->fetchAll(str_replace('count(1)', 'm.*', $querySQL) . ' LIMIT 0,10');
        $count = $this->createQuery()->countBySql($querySQL);
        return array($list, $count);
    }

    /**
     * @return MemberCertification|null
     */
    public function getCertificationData()
    {
        if ($this->id > 0) {
            $cert = new MemberCertification();
            if ($cert->find(['mid' => $this->id])) {
                return $cert->getCertificationObject();
            }
        }
        return null;
    }
}