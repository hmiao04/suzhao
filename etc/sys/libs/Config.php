<?php
/**
 * File: Config.php:YCMS
 * User: xiaoyan f@yanyunfeng.com
 * Date: 15-5-17
 * Time: 下午3:39
 * @Description
 */

class Config extends YC
{
    private $configObj = null;
    public function __construct($key){
        $this->configObj = YCFCore::getInstance()->getConf($key);
    }
    public function getConfig($key){
        if($this->configObj == null){
            $this->configObj = YCFCore::getInstance()->getConf($key);
        }else{
            if(is_array($this->configObj)){
                $this->configObj = isset($this->configObj[$key]) ?$this->configObj[$key]:null;
            }
        }
        return $this;
    }
    public function getValue(){
        return $this->configObj;
    }
}