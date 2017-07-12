<?php
/**
 * File: Certification.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-02-13 16:19
 */

namespace Models;

class CertificationData
{
    //认证状态(3:认证成功2:认证中;1:未通过认证;0:未认证)
    public static $CERT_STATUS_PASSED = 3;
    public static $CERT_STATUS_PROCESS = 2;
    public static $CERT_STATUS_FAILED = 1;
    public static $CERT_STATUS_NO = 0;
}

class Certification extends \Model
{
    public static $TYPES = array('company', 'person');

    /**
     * @var string QQ
     */
    public $personQQ;
    /**
     * @var string 真实(法人)名字
     */
    public $personName;
    /**
     * @var string 性别
     */
    public $personGender;
    /**
     * @var string 出生年月
     */
    public $personBirthDate;
    /**
     * @var string 电话
     */
    public $personPhone;
    /**
     * @var string 身份证
     */
    public $IdCard;
    /**
     * @var string 现居地址
     */
    public $personAddress;
    /**
     * @var string 身份证户籍地址
     */
    public $IdCardAddress;
    /**
     * @var string 身份证正面照片
     */
    public $IdCardFrontPhoto;
    /**
     * @var string 身份证反面照片
     */
    public $IdCardBackPhoto;

    public function validateRule($step = 1)
    {
        return array(
            array('personQQ', 1, '参数错误,需要QQ号码(MISSING_PARAM_personQQ)'),
            array('personName', 2, '参数错误,需要真实姓名(MISSING_PARAM_personName)'),
            array('personGender', 3, '参数错误,需要性别(MISSING_PARAM_personGender)'),
            array('personBirthDate', 3, '参数错误,需要出生日期(MISSING_PARAM_personBirthDate)'),
            array('personPhone', 3, '参数错误,需要联系电话(MISSING_PARAM_personPhone)'),
            array('IdCard', 3, '参数错误,需要身份证号码(MISSING_PARAM_IdCard)'),
            array('personAddress', 3, '参数错误,需要居住地址(MISSING_PARAM_personAddress)'),
            array('IdCardAddress', 3, '参数错误,需要身份证户籍地址(MISSING_PARAM_IdCardAddress)'),
            array('IdCardFrontPhoto', 3, '参数错误,需要身份证正面照片(MISSING_PARAM_IdCardFrontPhoto)'),
            array('IdCardBackPhoto', 3, '参数错误,需要身份证反面照片(MISSING_PARAM_IdCardBackPhoto)'),
        );
    }
}