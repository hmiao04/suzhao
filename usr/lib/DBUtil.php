<?php
/**
 * File: DBUtil.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2016-11-02 22:08
 */

namespace Lib;


use Models\CityModel;

class DBUtil
{
    public static function GetParentData(&$parentData, $tableName, $data, $key = 'id', $parentKey = 'parent_id')
    {
        $data = DB()->table($tableName)->field('*')->where($key, $data)->get();
        if ($data) {
            $parentData[] = $data;
            if ($data[$parentKey]) {
                self::GetParentData($parentData, $tableName, $data[$parentKey], $key, $parentKey);
                return;
            }
        }
    }

    public static function GetAreaAddress($province, $city, $country)
    {
        $cityModel = new CityModel();
        if($country){
            $cityModel->id = $country;
//            $countryData = DB()->table($cityModel->getTableName())->field('*')->where('id', $country)->get();
//            if($countryData) return array($countryData);
        }elseif($city){
            $cityModel->id = $city;
//            $countryData = DB()->table($cityModel->getTableName())->field('*')->where('id', $city)->get();
//            if($countryData) return array($countryData);
        }elseif($province){
            $cityModel->id = $province;
        }
        if (!$cityModel->find()) return array();
        return array($cityModel->toArray());
//        if (!$cityModel->find()) return $cityArray;
//        $cityArray[] = $cityModel->toArray();
//        if (!$city) return $cityArray;
//        $cityData = DB()->table($cityModel->getTableName())->field('*')->where('id', $city)->get();
//        if (!$cityData) return $cityArray;
//        $cityArray[] = $cityData;
//        if (!$country) return $cityArray;
//        if (!$countryData) return $cityArray;
//        $cityArray[] = $countryData;
//        return $cityArray;
    }

    public static function getParentCity($cityId)
    {
        if(!$cityId || $cityId < 0){
            return array();
        }
        $cityDataArray = array();
        $cityModel = new CityModel();
        $cityModel->id = $cityId;
        if(!$cityModel->find()) return array();
        $cityDataArray[] = $cityModel->toArray();
        $parent_id = $cityModel->parent_id;
        while($parent_id > 1){//查询父级
            $city = DB()->table($cityModel->getTableName())->where('id',$parent_id)->get();
            if($city){
                $cityDataArray[] = $city;
                $parent_id = $city['parent_id'];
            }
        }

        return array_reverse($cityDataArray);
    }

    public static function getIdCardArea($idCard)
    {
        if (!$idCard && strlen($idCard) < 6) return array(0, 0, 0);
        $cityModel = new CityModel();
        $cityModel->id_card_code = substr($idCard, 0, 6);
        if ($cityModel->find()) {
            $cityData = self::getParentCity($cityModel->id);
            $ids = array();
            foreach($cityData as $c){
                $ids[] = $c['id'];
            }
            return $ids;
        }
        return array(0, 0, 0);
    }
}