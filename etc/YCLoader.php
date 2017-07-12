<?php
/**
 * File: YCLoader.php:YCMS
 * User: xiaoyan f@yanyunfeng.com
 * Date: 15-5-5
 * Time: 下午2:36
 * @Description
 */

class YCLoader{
    private static $loader = null;
    private static $loadFiles = array();
    private function __construct(){
        Logger::getLogger()->setTagName('YCLoader');
    }
    private $includeFiles = array();

    public static function init(){
        if(self::$loader != null) return self::$loader;
        $loader = new YCLoader();
        $loader->loadLibrary();
        self::$loader = $loader;
        return $loader;
    }
    public static function loadLib($lib){
        $libPath = str_replace('.','/',$lib);
        $libFile = LIB_DIR.'/libs/'.$libPath.'.php';
        if(!isset(self::$loadFiles[$lib]) && file_exists($libFile)) {
            self::$loadFiles[$lib] = $libFile;
            Logger::getLogger()->sys('Load Lib:' . $libFile);
            include(LIB_DIR.'/libs/'.$libPath.'.php');
        }
        return self::init();
    }
    public function allLoadLib(){
        return self::$loadFiles;
    }
    public function loadUserLib($libs = null){
        if(!empty($libs)){
            foreach($libs as $f){
//                $f = APP_PATH.$f;
                if(file_exists($f)) {
                    Logger::getLogger()->sys('Load File:' . $f);
                    include($f);
                }
            }
        }
        return $this;
    }
    public function import($files = null){
        if($files){
            if(is_string($files)) $files = array($files);

            foreach($files as $f){
                if(file_exists(LIB_DIR.'/libs/'.$f.'.php')) {
                    include_once(LIB_DIR.'/libs/'.$f.'.php');
                }
            }
        }
        return $this;
    }
    private function loadLibrary(){

        $libPath = array(
            'sys' => LIB_DIR.'/',
            'lib' => LIB_DIR.'/libs/',
            'twig' => LIB_DIR.'/libs/Twig/'
        );
        foreach($this->includeFiles as $k => $fs){
            foreach($fs as $f){
                Logger::getLogger()->sys($libPath[$k].$f.'.php');
                if(file_exists($libPath[$k].$f.'.php')) {
                    include($libPath[$k].$f.'.php');
                }
            }
        }
    }

    public function getInput(){
        if(Cache::getInstance()->exists('Input')) return Cache::getInstance()->get('Input');
        $int = new Input(); Cache::getInstance()->set('Input',$int);
        return $int;
    }
    public function getSec(){
        if(Cache::getInstance()->exists('Security')) return Cache::getInstance()->get('Security');
        $sec = new Security(); Cache::getInstance()->set('Security',$sec);
        return $sec;
    }
    public static  function getRouterCore(){
        return YCFCore::getInstance();
    }

    /**
     * @param $key
     * @return null
     */
    public function getAppConfig($key){
        return YCFCore::getInstance()->getConf($key);
    }
    public function getConfig($key){
        return new Config($key);
    }

    public function getCopyright(){
        if(!Cache::getInstance()->exists('site_copyright')){
            $file = APP_DIR.'/usr/var/copyright.php';
            $copyright = file_get_contents(file_exists($file) ? $file : APP_DIR.'/etc/var/copyright.php');
            Cache::getInstance()->set('site_copyright',$copyright);
        }
        echo Cache::getInstance()->get('site_copyright');
    }
}