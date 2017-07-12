<?php
/**
 * File: AdministratorModel.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2016-11-02 21:49
 */

namespace Models;


class AdministratorModel extends \Model
{
    public $id;
    public $account;
    public $nick_name;
    public $avatar;
    public $pwd;
    public $type;
    public $role_id;
    public $note;
    public $status;

    public function __construct()
    {
        $this->setTableName('yc_admin');
        $this->setPrimaryKey('id');
    }


    public function getListByCondition($condition = array(),$limit = array()){
        if(isset($condition['status[!]'])){
            $condition['a.status[!]'] = $condition['status[!]'];
            unset($condition['status[!]']);
        }
        return $this->createQuery()->table($this->getTableName().'(a)')
            ->field('a.*,r.role_name')->where($condition)->join(array(
                '[><]yc_admin_role(r)'=>array('r.id'=>'a.role_id')
            ))->orderBy('id ASC')->limit($limit)->select();
    }

    /**
     * @return \Models\RoleResources|null
     */
    public function getRoleInfo(){
        if($this->role_id <= 0) return null;
        $role = new RoleResources();
        return $role->find(array('id'=>$this->role_id));
    }

    public function getCountByCondition($condition = array()){
        if(isset($condition['status[!]'])){
            $condition['a.status[!]'] = $condition['status[!]'];
            unset($condition['status[!]']);
        }

        return $this->createQuery()
            ->where($condition)
            ->join(array(
                '[><]yc_admin_role(r)'=>array('r.id'=>'a.role_id')
            ))->count($this->getTableName().'(a)');
    }


    /**
     * @param $adminId
     * @return $this
     */
    public function getInfoAndCompany($adminId){
        $condition = array(
            'a.id'=>$adminId
        );
        $data = $this->createQuery()->where($condition)->field('a.*,r.role_name')
            ->join(array(
                '[><]yc_admin_role(r)'=>array('r.id'=>'a.role_id')
            ))->table($this->getTableName().'(a)')->get();
        if($data){
            $this->setProperty($data);
            $this->extraData = $data;
        }
        return $this;
    }
}