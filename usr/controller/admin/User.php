<?php
/**
 * File: User.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2016-11-23 16:34
 */

namespace Controller\Admin;


use Lib\AccountUtil;
use Models\AdministratorModel;
use Models\LogAction;
use Models\LogState;

class User extends \AdminController
{

    public function init()
    {
        $this->addAdminUrl('user-setting','pageView');
        $this->addAdminUrl('api/update-user','updateUser');
    }

    /**
     * 
     */
    public function pageView(){
        $allowView = array(
            'user-setting'=>'getUserInfo',
        );
        parent::processPageView('user-setting',$allowView);
    }

    protected function getUserInfo()
    {
        $this->pushNavPath('信息修改');

        $this->assign('roleInfo',$this->getRoleData());
        $this->assign('user',$this->getLoginInfo()->toArray());
    }

    public function updateUser(){
        $avatar = $this->saveUploadImage('avatar');
        $adminModel = new AdministratorModel();
        $adminModel->id = $this->getLoginAdminId();
        if($adminModel->exists()){
            $adminInfo = $this->getLoginInfo();
            $adminModel->nick_name = $this->input()->post('nick_name');
            if($this->input()->post('pwd')){
                $adminModel->pwd = AccountUtil::GetAdminPassword(
                    $adminInfo->account,
                    $this->input()->post('pwd'),
                    $adminInfo->type
                );
            }
            $adminModel->avatar = $avatar;
            if ($adminModel->update()) { //更新数据
                $this->recordAdminLog(LogAction::UPDATE, LogState::SUCCESS, $adminModel->toArray());
                ajaxSuccess();
            }
            $this->recordAdminLog(LogAction::UPDATE, LogState::FAILED, $adminModel->toArray());
        }
        ajaxResponse(1, '保存数据失败!', $adminModel->toArray());
    }
}