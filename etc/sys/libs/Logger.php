<?php
/**
 * File: Logger.php:YCMS
 * User: xiaoyan f@yanyunfeng.com
 * Date: 15-8-6
 * Time: 下午5:22
 * @Description
 */
class Logger{
    private static $obj;
    private $tagName = 'LOG';
    private $logState = false;

    public static $LEVEL_SYS = 0;
    public static $LEVEL_INFO = 1;
    public static $LEVEL_DEBUG = 2;
    public static $LEVEL_ERROR = 3;
    private $logList = array();

    private $level = 1;

    private function __construct($tagName){
        if($tagName){
            $this->tagName = $tagName;
        }
//        RouterCore::getInstance()->addInterceptor('ended',array($this,'showLogList'));
        register_shutdown_function(array($this,'showLogList'));
    }

    public function turnOn(){}
    public static function setPriority($level){
        self::getLogger('')->level = $level;
        return self::getLogger('');
    }
    public static function getLogger($tagName = null){
        if(self::$obj != null){
            self::$obj->setTagName($tagName);
            return self::$obj;
        }
        self::$obj = new Logger($tagName);
        return self::$obj;
    }
    public function setTagName($tagName){
        if($tagName){
            $this->tagName = $tagName;
        }
    }
    public function error($message){
        $this->log($message,self::$LEVEL_ERROR);
    }
    public function debug($message){
        $this->log($message,self::$LEVEL_DEBUG);
    }
    public function sys($message){
        $this->log($message,self::$LEVEL_SYS);
    }
    public function info($message){
        $this->log($message,self::$LEVEL_INFO);
    }
    private function log($message,$level){
        if(YCFCore::getInstance()->isDev() && !YCFCore::getInstance()->getConf('show_log')) return;
        $traces = debug_backtrace();
        $line = $traces[1]['line'];
        $color = '#000';
        if($level == self::$LEVEL_ERROR){
            $color = '#f00';
        }
        $time = date('Y-m-d H:i:s');
        $line = sprintf('%s [%s] (%s):<span style="color:%s">%s</span>     File:%s',
            $time,$this->tagName.'('.$line.')',$this->getLevelText($level),$color,$message,$traces[1]['file']);
        if($level >= $this->level){
            $this->logList[] = $line."<br> \n";
        }
    }
    private function getLevelText($level){
        $text = array(
            self::$LEVEL_DEBUG => 'DEBUG',
            self::$LEVEL_ERROR => 'ERROR',
            self::$LEVEL_INFO => 'INFO',
            self::$LEVEL_SYS => 'SYS'
        );
        return isset($text[$level])?$text[$level]:$level;
    }

    public function showLogList(){
        if(YCFCore::getInstance()->isDev() && !YCFCore::getInstance()->getConf('show_log')) return;
        echo '<style>
body{margin-bottom: 201px;}
#show_log_list{  height: 200px;
  color: #4F5155;
  font-family: "microsoft yahei";
  font-size: 14px !important;
  overflow: auto;
  position: fixed;
  left: 0;
  right: 0;
  bottom: 0;
  border-top: solid 1px #DDD;
  box-shadow: 0px 1px 2px #000;
  background-color: #FFF;}
#show_log_list .li{  border-bottom: solid 1px #DDD;
  padding: 5px;}</style>
<div id="show_log_list">';

        if(count($this->logList) > 0){
            $logList = array_reverse($this->logList);
            foreach($logList as $index=>$log){
                $index++;
                echo "<div class='li'>{$index}.{$log}</div>";
            }
        }else{
            echo '<h2 class="li">no log record and output.</h2>';
        }
        echo '</div>';
    }

}