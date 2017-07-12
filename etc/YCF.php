<?php
/**
 * Created by JetBrains PhpStorm.
 * User: home
 * Date: 15-8-27
 * Time: 下午12:41
 * To change this template use File | Settings | File Templates.
 */
include_once dirname(dirname(__FILE__)).'/etc/usr/Env.php';
spl_autoload_register(array('YCF','__autoLoader'));

class YCF{
    private static $loadedFile = array();
    public static function __autoLoader($class){
        if(in_array($class,self::$loadedFile)) return;
        $classFileName = $class;
        if(strpos($class,'\\')){
            $classInfo = explode('\\',$class);
            if(strtolower($classInfo[0]) == 'lib') $classInfo[0] = 'lib';
            $className = $classInfo[count($classInfo)-1];
            $classInfo[count($classInfo)-1] ='';
            $classFileName = (count($classInfo) == 2 ? strtolower(implode('\\',$classInfo)):implode('\\',$classInfo)).$className;
            $classFileName = str_replace('\\','/',$classFileName);
        }
        $loaded = true;

        if(file_exists(LIB_DIR.$class.'.php')){include(LIB_DIR.$class.'.php');}
        elseif(file_exists(SYS_LIB.$class.'.php')){include(SYS_LIB.$class.'.php');}
        elseif(file_exists(SYS_DIR.$class.'.php')){include(SYS_DIR.$class.'.php');}
        elseif(file_exists(APP_PATH.DIRECTORY_SEPARATOR.$class.'.php')){include(APP_PATH.DIRECTORY_SEPARATOR.$class.'.php');}
        elseif(file_exists(APP_PATH.DIRECTORY_SEPARATOR.$classFileName.'.php')){include(APP_PATH.DIRECTORY_SEPARATOR.$classFileName.'.php');}
        if($loaded) self::$loadedFile[] = $class;return;
    }
    public static function Loader(){
        return YCLoader::init();
    }
    public static function Instance(){
        return YCLoader::init();
    }
}