<?php
/**
 * File: Certification.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-02-13 16:19
 */

namespace Models;

class MemberCertification extends \Model
{
    public $mid;
    public $type;
    public $certification_status;
    public $certification_data;
    public $post_time;
    public $certification_time;
    public $remark;
    public $status;

    public function __construct()
    {
        $this->setPrimaryKey('mid');
        $this->setTableName('sz_member_certification');
    }

    private function parseCertificationData(){
        if($this->certification_data){
            $certData = unserialize(base64_decode($this->certification_data));
            $cert = $this->type == CertificationPerson::$TYPE ? new CertificationPerson():new CertificationCompany();
            if(is_array($certData))$cert->setProperty($certData);
            $this->certification_data = $cert;
        }
    }
    /**
     * 获取认证数据
     * @return MemberCertification
     */
    public function getCertificationObject(){
        $this->parseCertificationData();
        return $this;
    }
    /**
     * 获取认证数据
     * @return CertificationCompany|CertificationPerson|null
     */
    public function getCertificationData(){
        $this->parseCertificationData();
        return $this->toArray();
    }
}