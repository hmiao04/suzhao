<?php
/**
 * File: Administrator.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2016-11-02 18:12
 */

namespace Controller\Admin;


use Lib\AccountUtil;
use Lib\DBUtil;
use Models\AdministratorModel;
use Models\AdminRole;
use Models\LogAction;
use Models\LogState;
use Models\RoleResources;

class Administrator extends \AdminController
{
    public function init()
    {
        $this->addAdminUrl('user/view.{page}', 'pageView');
        $this->addAdminUrl('user/delete', 'deleteAdminData');
        $this->addAdminUrl('api/administrator.info', 'getAdminData');
        $this->addAdminUrl('api/administrator.save', 'saveAdminData');
    }

    /**
     * @param $page
     */
    public function pageView($page)
    {
        $allowView = array(
            'admin-list' => 'getAllAdministrator',
            'admin-register' => 'registerAdmin'
        );
        parent::processPageView($page, $allowView);
    }

    protected function getAllAdministrator()
    {
        $this->getAllUser(self::ROLE_ADMIN);
    }

    protected function registerAdmin()
    {
        $this->assign('page_model', '管理员');
        $this->assign('page_model_key', 'admin');
        $this->assign('page_mode_title', '公司');
        $this->getAllRole(self::ROLE_ADMIN);
    }

    private function getAllRole()
    {
        $role = new RoleResources();
        $myRole = $this->getRoleData()->level;
        $roleList = $role->findByCondition(array(
            'status[!]'=>0,
            'id[!]'=>1
        ));
        $this->assign('role_list', $roleList);
    }

    protected function getAllOperator()
    {
        $this->assign('page_model', '操作员');
        $this->assign('page_model_key', 'op');
        $this->assign('page_mode_title', '区域');
        $this->getAllUser(self::ROLE_OPERATOR);
    }

    protected function registerOperator()
    {
        $this->assign('page_model', '操作员');
        $this->assign('page_model_key', 'op');
        $this->assign('page_mode_title', '区域');
        $this->getAllRole(self::ROLE_OPERATOR);
    }

    protected function getAllUser($type)
    {
        $admin = new AdministratorModel();
        list($page, $size, $star) = $this->getPageAndSize(15); //array(page,size,start)
        $condition = array(
            'a.status[!]' => '-1',
            'a.role_id[!]' => '1',
        );
        $adminInfo = $this->getLoginFullInfo()->extraData;
        $queryParam = $this->getQueryParam();
        $queryString = http_build_query($queryParam['page']);
        $condition = array_merge($condition,$queryParam['query']);
        $list = $admin->getListByCondition($condition, array($star, $size));
        $totalCount = $admin->getCountByCondition($condition);
        $this->createWindowPageLink('view.admin-list?'.$queryString, $totalCount, $size);
        $this->assign('adminList', $list);
        $this->getAllRole($type);
        if(isAjax()){
            $this->render('admin-list-item');exit;
        }
    }

    public function deleteAdminData(){
        $mid = $this->input()->get('id');
        if(!$mid || $mid < 1){
            ajaxResponse(-1,'没有找到要删除的数据(id null)',array());
        }
        $memberModel = new AdministratorModel();
        $memberModel->id = $mid;
        if(!$memberModel->find()){
            ajaxResponse(-1,'没有找到要删除的数据(id error)',array());
        }
        try{
            $memberModel->createQuery()->exec('update yc_admin set status=-1 where id='.$mid);
            ajaxSuccess();
        }catch (\Exception $e){
            ajaxError(stringFormat('删除会员信息失败({0})',$e->getMessage()));
        }
    }
    public function getAdminData()
    {

        $itemId = $this->input()->request('id');
        if(!$itemId){ajaxResponse(-1,'参数错误');}
        $admin = new AdministratorModel();
        if(!$admin->find(array(
            'id'=>$itemId,
            'status[!]'=>-1
        ))){
            ajaxError('没有查询到该数据',1);
        }
        $retData = $admin->toArray();
        $retData['role_name'] = $this->getRoleData($admin->role_id)->role_name;
        ajaxSuccess($retData);
    }
    public function saveAdminData()
    {
        $adminModel = new AdministratorModel();
        $checkModel = new AdministratorModel();
        $this->checkDataNull(array(
            array('account', 1, '请填写登录账号'),
            array('id', 5, '发送数据不完整')
        ));
        $adminModel->setProperty($this->input()->post());
        if($adminModel->pwd){//需要更新密码
            $adminModel->pwd = AccountUtil::GetAdminPassword(
                $adminModel->account,
                $adminModel->pwd,
                $adminModel->type
            );
        }
//        $this->apiTest($adminModel);
        $checkModel->account = $adminModel->account;
        $checkModel->status = '[!=]-1';
        if ($adminModel->id < 1) { // 新增
            if (!$adminModel->pwd) ajaxResponse(3, '请填写登录密码');
            if ($checkModel->exists()) {
                ajaxResponse(4, '该登录账号已经存在了(add)');
            }
            //TODO 设置默认头像
            $adminModel->avatar = 'static/images/default_avatar.png';
            if ($adminModel->insert()) {
                $this->recordAdminLog(LogAction::INSERT, LogState::SUCCESS, $adminModel->toArray());
                ajaxSuccess();
            }
            $this->recordAdminLog(LogAction::INSERT, LogState::FAILED, $adminModel->toArray());
        } else {
            if($checkModel->find() && $checkModel->id != $adminModel->id){
                ajaxResponse(4,'该登录账号已经存在了(update)');
            }
            if ($adminModel->update()) { //更新数据
                $this->recordAdminLog(LogAction::UPDATE, LogState::SUCCESS, $adminModel->toArray());
                ajaxSuccess();
            }
            $this->recordAdminLog(LogAction::UPDATE, LogState::FAILED, $adminModel->toArray());
        }
        ajaxResponse(1, '保存数据失败!', $adminModel->toArray());
    }
}