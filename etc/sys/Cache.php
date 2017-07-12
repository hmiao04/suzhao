<?php
/**
 * File: Cache.php:YCMS
 * User: xiaoyan f@yanyunfeng.com
 * Date: 15-5-4
 * Time: 下午3:42
 * @Description
 */
class Cache{

    private static $cache = null;
    private $cacheList = array();

    private $cacheType = 'session';
    private $mem;

    private function __construct($type){
        $this->setCacheType($type);
    }
    private function initCache(){
        $memConfig = YCFCore::getInstance()->getConf('memcache');
        if($this->cacheType == 'mem' && $memConfig && $memConfig['server']){
            if($this->mem == null){
                $this->mem = new Memcache();
                $this->mem->connect($memConfig['server'],$memConfig['port'],$memConfig['timeout']);
            }
        }else{
            $this->cacheType = 'session';
        }
    }
    public function getCacheType(){
        return $this->cacheType;
    }
    public function setCacheType($cacheType){
        if(in_array($cacheType,array('session','mem','file'))){
            $this->cacheType = $cacheType;
        }
        $this->initCache();
        return $this;
    }
    public static function getInstance($cacheType='session')
    {
        if (self::$cache != null) {
            return self::$cache;
        }
        self::$cache = new Cache($cacheType);
        return self::$cache;
    }

    private function isMem(){
        return $this->cacheType == 'mem';
    }

    public function exists($key){
        if($this->isMem()) return $this->mem->get($key) !== FALSE;
        return isset($this->cacheList[$key]);
    }
    public function set($key,$data){
        if($this->isMem()) return $this->mem->set($key,$data);
        else $this->cacheList[$key]= $data;
        return $this;
    }
    public function get($key){
        if($this->isMem()) return $this->mem->get($key);
        return $this->exists($key) ? $this->cacheList[$key] : null;
    }

    public function delete($key){
        if($this->exists($key)){
            unset($this->cacheList);
        }
    }
}