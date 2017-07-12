<?php
/**
 * File: IdentificationService.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-04-08 9:04
 */

namespace Service;


use Models\CertificationData;
use Models\CertificationPerson;
use Models\MemberCertification;
use Models\MemberModel;

class IdentificationService
{
    public static function GetIdentificationDataByMid($memberId)
    {
        $certification = new MemberCertification();
        if($certification->find(['mid'=>$memberId])){
            $data = [
                'data'  => unserialize(base64_decode($certification->certification_data)),
                'status'=> $certification->certification_status,
                'type'  => $certification->type,
                'remark'=> $certification->remark,
                'post_time'=> $certification->post_time,
                'certification_time'=> $certification->certification_time
            ];
        }else{
            $data = [
                'data'  => new CertificationPerson(),
                'status'=> CertificationData::$CERT_STATUS_NO,
                'type'  => 'person',
                'remark'=> ''
            ];
        }
        return $data;
    }

    /**
     * @param \Models\MemberModel $memberInfo
     * @return array
     */
    public static function GetIdentificationDataByMember(MemberModel $memberInfo)
    {
        $tmp = $memberInfo->extraData['Certification'];
        $data = [
            'status'=> $tmp['certification_status'],
            'data'  => $tmp['certification_data'],
            'type'  => $tmp['type']
        ];
        unset($tmp);
        return $data;
    }
}