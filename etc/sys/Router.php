<?php
/**
 * Created by PhpStorm.
 * User: yancheng (cheng@love.xiaoyan.me)
 * Date: 14-9-16
 * Time: 下午3:53
 */
class Router extends YC{
    private $routeList = array();
    private $routerRecordList = array();
    private $currentFile = null;
    private $routerListFile = SYS_ROUTE_FILE;

    private $controllerPath = './usr/controller';
    private $controllerNamespace = 'Controller';

    /**
     * 获取路由表
     * @param string $controllerModulePath
     * @param string $namespace
     * @return array
     * @throws AppException
     */
    public function getRouteList($controllerModulePath = null,$namespace = null){
        $this->controllerPath = $controllerModulePath ? $controllerModulePath : $this->controllerPath;
        $this->controllerNamespace = $namespace ? $namespace : $this->controllerNamespace;;
        $this->initRouters();
        return $this->routeList;
    }

    public function afterAddRoute($url){
        $this->routerRecordList[$url] = $this->currentFile;
    }

    private function initRouters(){
        $controllerModulePath = $this->controllerPath;
        Logger::getLogger()->sys('init routers on @'.$controllerModulePath);
        if(!@file_exists($controllerModulePath)){
            throw new AppException("Controller path {$controllerModulePath} not exist");
        }
        $files = array_merge(glob($controllerModulePath.'/*.php'),glob($controllerModulePath.'/*/*.php'));

        foreach ($files as $file) {
            $this->currentFile = $file;
            Logger::getLogger()->sys('init controller :'.$file);
            $this->initControl();
        }
        $this->saveRouter();
    }

    private function initControl(){
        Logger::getLogger('Router')->debug($this->currentFile);
        if(file_exists($this->currentFile)){
            $arr = include($this->currentFile);
            $dir = dirname($this->currentFile);
            $currentNameSpace = str_replace('/','\\',substr($dir,strlen($this->controllerPath)));
            $clsName = getFileNameWithOutSuffix($this->currentFile);

            $cls = new ReflectionClass($this->controllerNamespace.$currentNameSpace.'\\'.$clsName);
            $obj = $cls->newInstance();
            //check the object is controller
            if($obj instanceof \Controller){
                $method = new ReflectionMethod($obj,'init');
                $method->invoke($obj);
            }
        }
    }

    private function saveRouter(){
        $content = "<?php\n\t"."return array(\n";
        $urls = array();
        foreach($this->routerRecordList as $url => $file){

            $urls[] ="\t\t'$url'=>'$file'";
        }
        $content .= implode(",\n",$urls)."\n\t);";
        file_put_contents($this->routerListFile,$content);
    }
    public function addRoute($url,$func){
        $this->routeList[$url] = $func;
        $this->afterAddRoute($url);
    }
    public function initRouter(){
        if (file_exists($this->routerListFile)) {
            $this->routeList = include($this->routerListFile);
            return $this->routeList;
        }
        return false;
    }
    public function getRouteFunction($url){
        if(isset($this->routeList[$url])){
            $this->currentFile = $this->routeList[$url];
            $this->initControl();
            return $this->routeList[$url];
        }
        return null;
    }
}