<?php
/**
 * File: WebController.php:newsys
 * User: xiaoyan f@yanyunfeng.com
 * Date: 2017/1/4
 * Time: 22:36
 * @Description
 */

namespace Lib;


use Models\CertificationData;
use Models\MemberModel;
use models\MemberType;
use models\MemberCertification;
use Service\MemberService;
use YCF;

abstract class WebController extends \BaseController
{
    private $renderPath = null;

    protected function setControllerRenderPath($path)
    {
        $this->renderPath = $path;
    }

    protected function  render($templateFile, $vars = array(), $basePath = null)
    {
        $path = $this->renderPath == null ? $this->getConfig('template')->getConfig('tpl_config')
            ->getConfig('front_page')->getValue() : $this->renderPath;
        if ($basePath) $path = $basePath;
        $this->assign('page_path', $templateFile);
        $this->setRenderPath($path);
        $memberInfo = $this->getLoginMemberInfo();
        $cert = $memberInfo->getCertificationData();
        $is_vip = false;
        $is_cert = MemberService::HasCertification($memberInfo);
        if ($memberInfo->vip_time && REQ_TIME < strtotime($memberInfo->vip_time)) {
            $is_vip = true;
        }
        $this->assign('IS_VIP', $is_vip);
        $this->assign('MEMBER_INFO', $memberInfo);
        $memberType = $memberInfo != null && $memberInfo->type_id == MemberType::$Company ? 'company' : 'person';
        $this->assign('MEMBER_TYPE', $memberType);
        $this->assign('MEMBER_CERT', $is_cert);

        parent::render($templateFile, $vars);
    }

    protected function checkMemberLogin()
    {
        if ($this->getLoginMemberId() > 0) return;
        $url = URL() . '/account/';
        if (isAjax()) {
            //$this->render('login-frame');exit;
            ajaxResponse(403, '请先<a href="' . $url . '">登录</a>后在进行操作');
        }
        header('Location: ' . $url . '?callback=' . urlencode(URL(1) . YCF::Instance()->getRouterCore()->getPath()));
        exit;
    }

    protected function checkVIPMemberLogin()
    {

      $userInfo = new MemberCertification();
       $userInfo->mid = 0;

       if(isset($_SESSION[USER_SES_KEY]) && $_SESSION[USER_SES_KEY]){
           $userInfo->mid = $_SESSION[USER_SES_KEY];
           if(!$userInfo->find()){
               $userInfo->mid = 0;
               return $userInfo;
           }
           $memberCert = new \Models\MemberCertification();
           $certData = $memberCert->findByPrimary($userInfo->id);
           if($certData != null) $certData = $memberCert->getCertificationData();
           $userInfo->extraData['Certification'] = $certData;
           echo "1=" . $userInfo;
           return $userInfo;
       }
        echo "2=" . $userInfo;
       return $userInfo;
     /*
       if (false) {
           return 1;
       }else{
           return 0;

       }
       */
    }

    /**
     * @param $key
     * @return array|null|string
     * @throws \AppException
     */
    public function getNumber($key)
    {
        $v = $this->input()->request($key);
        if (is_numeric($v)) return $v;
        throw new \AppException('请求的参数有误');
    }

    private $hashId = null;


    protected function encode($id)
    {
        return instanceHashId()->encode(func_get_args());
    }

    /**
     * @param string $hash the hash to decode
     * @return array
     */
    protected function decode($hash)
    {
        if(!$hash) return false;
        $data = instanceHashId()->decode($hash);
        return $data && count($data) == 1 ? $data[0] : $data;
    }
}