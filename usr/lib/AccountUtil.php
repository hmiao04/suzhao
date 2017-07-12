<?php
/**
 * File: AccountUtil.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2016-11-01 22:33
 */
namespace Lib;

use Models\DataStatus;
use Models\LocalAccount;

class AccountUtil
{
    /**
     * 加密密码
     * @param string|int $memberId
     * @param string $account
     * @param string $password
     * @param string $salt
     * @return array 返回机密后的密码及加密盐
     */
    public static function GetUserPassword($memberId,$account,$password,$salt = null)
    {
        if(null == $salt){
            $salt = substr(md5($memberId.uniqid().SALT_KEY),10,10);
        }
        $salt = strtolower($salt);
        return array(strtolower(md5(sha1($account.$salt).md5($password))),$salt );
    }

    public static function GetAdminPassword($account,$password,$type){
        return md5($account.$password.$type);
    }

    public static function CreateLocalAccount($memberId,$account,$password)
    {
        $accountData = new LocalAccount();
        $accountData->member_id = $memberId;
        $accountData->login_account = $account;
        list($pwd,$salt) = self::GetUserPassword($memberId,$account,$password);
        $accountData->login_pwd = $pwd;
        $accountData->salt = $salt;
        $accountData->create_time = REQ_TIME;
        $accountData->state = DataStatus::NORMAL;

        $accountData->insert();
        return $accountData->lastInsertId;
    }

    public static function AccountExists($account)
    {
        $localAcc = new LocalAccount();
        $localAcc->login_account = $account;
        $localAcc->state = DataStatus::NORMAL;
        return $localAcc->exists();
    }

    public static function GetMemberAccountInfo()
    {
        
    }

    /**
     * 更新本地系统账号
     * @param LocalAccount $accountData
     */
    public static function UpdateMemberAccountInfo(LocalAccount $accountData)
    {

    }
}