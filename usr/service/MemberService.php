<?php
/**
 * File: MemberService.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-05-06 0:43
 */

namespace Service;


use Models\CertificationData;
use Models\DataStatus;
use Models\MemberModel;

class MemberService
{
    private static $memberModel = null;

    /**
     * @return \Models\MemberModel
     */
    private static function GetModel()
    {
        if(null != self::$memberModel) return self::$memberModel;
        self::$memberModel = new MemberModel();
        return self::$memberModel;
    }
    public static function IsVip()
    {

    }

    public static function AvailableMemberCount()
    {
        $condition = ['status'=>DataStatus::NORMAL];
        return self::GetModel()->count($condition);
    }
    public static function HasCertification(MemberModel $memberInfo)
    {
        $cert = $memberInfo->getCertificationData();
        $is_cert = false;
        if ($cert && $cert->certification_status == CertificationData::$CERT_STATUS_PASSED) {
            $is_cert = true;
        }
        return $is_cert;
    }
}