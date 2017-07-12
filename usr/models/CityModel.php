<?php
/**
 * File: ${FILE_NAME}.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2016-11-02 22:46
 */

namespace Models;


class CityModel extends \Model
{

    public $id;
    public $city_name;
    public $parent_id;
    public $state;
    public $remark;
    public $city_sort;
    public $city_area;
    public $id_card_code;
    public $first_pinyin;
    public $full_pinyin;
    public $simple_pinyin;

    /**
     * CityModel constructor.
     */
    public function __construct()
    {
        $this->setTableName('yc_city');
        $this->setPrimaryKey('id');
    }
    public function getCityParentText($province = null,$city = null,$country = null){
        if(is_array($province) && $city == null && $country == null){
            $country = $province['country_id'];
            $city = $province['city_id'];
            $province = $province['province_id'];
        }
        $cityId = 0;
        if($country){$cityId=$country;}
        elseif($city){$cityId=$city;}
        elseif($province){$cityId=$province;}
        else{
            return '中国';
        }
        $cityData = $this->find(array('id'=>$cityId));
        if($cityData) return $cityData->remark;
        return '';
    }
}