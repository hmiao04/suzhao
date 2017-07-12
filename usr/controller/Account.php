<?php

/**
 * File: Account.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2016-09-11 23:48
 */
namespace Controller;

use Lib\AccountUtil;
use Models\AdministratorModel;
use \Models\LogAction;
use \Models\LogState;

class Account extends \BaseController
{

    public function init()
    {
        $this->addRoute('/account/admin.login', 'adminLogin');
        $this->addRoute('/account/admin.login.html', 'adminLoginView');
        $this->addRoute('/account/admin.logout', 'adminLogout');
        $this->addRoute('/account/', 'memberLogin');
        $this->addRoute('/account.status', 'memberStatus');
    }

    public function memberStatus()
    {
        $this->checkRequestMethod('post');
        ajaxSuccess(['status' => $this->getLoginUser()]);
    }

    public function memberLogin()
    {
        $this->render('front_page/member-login', ['login' => true]);
    }

    public function adminLoginView()
    {
        $this->render('admin/login');
    }

    public function adminLogin()
    {
        if (isAjax()) {
            $this->checkDataNull(array(
                array('loginId', 1, '请填写登录账号'),
                array('loginPwd', 2, '请填写登录密码')
            ));
            $admin = new AdministratorModel();
            $account = $this->input()->post('loginId');
            $loginPwd = $this->input()->post('loginPwd');
            $errorMessage = '登录失败，用户名错误或者该用户已被禁用!';
            $uid = 0;
            if ($admin->find(array('account' => $account, 'status[!]' => -1)) && $admin->status == 1) {
                $pwd = AccountUtil::GetAdminPassword($admin->account, $loginPwd, $admin->type);
                $uid = $admin->id;
                if ($pwd == $admin->pwd) {
                    try {
                        $this->recordLog(LogAction::LOGIN, $admin->id, LogState::SUCCESS, array(
                            'loginId' => $admin->account, 'loginPwd' => $admin->pwd
                        ));
                    } catch (Exception $e) {
                        ajaxException($e);
                    }
                    $_SESSION['Admin'] = $admin->id;
                    ajaxSuccess();
                }
                $errorMessage = '登录失败,该账号的密码错误';
            }
            ajaxResponse(-1, $errorMessage);
            $this->recordLog(LogAction::LOGIN, $uid, LogState::FAILED, $this->input()->post());
        }
        ajaxError('哈哈,你懂得！');
    }

    public function adminLogout()
    {
        $_SESSION['Admin'] = null;
        unset($_SESSION['Admin']);
        header('Location: ' . URL() . '/account/admin.login.html');
        exit;
    }

}