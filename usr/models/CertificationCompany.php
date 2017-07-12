<?php
/**
 * File: CertificationCompany.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-02-13 16:19
 */

namespace Models;


class CertificationCompany extends Certification
{
    public static $TYPE = 'company';
    public $type = 'company';

    /**
     * @var string 营业执照号码
     */
    public $businessLicenseNo;
    /**
     * @var string 公司地址
     */
    public $companyAddress;
    /**
     * @var string 营业执照照片
     */
    public $licensePhoto;
    /**
     * @var string 税务登记证照片
     */
    public $taxLicensePhoto;
    /**
     * @var string 组织机构代码证照片
     */
    public $organizationPhoto;

    public function  validateRule($step = 1)
    {
        $rules = parent::validateRule($step);
        return array_merge($rules,array(
            array('businessLicenseNo', 1, '参数错误,需要营业执照号码(MISSING_PARAM_businessLicenseNo)'),
            array('companyAddress', 2, '参数错误,需要公司营业地址(MISSING_PARAM_companyAddress)'),
            array('licensePhoto', 3, '参数错误,需要营业执照照片(MISSING_PARAM_licensePhoto)'),
            array('taxLicensePhoto', 3, '参数错误,需要税务登记证照片(MISSING_PARAM_taxLicensePhoto)'),
            array('organizationPhoto', 3, '参数错误,需要组织机构代码证照片(MISSING_PARAM_organizationPhoto)'),
        ));
    }

}