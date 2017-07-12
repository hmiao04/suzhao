<?php
/**
 * User: yancheng (cheng@love.xiaoyan.me)
 * Date: 14-9-16
 * Time: 下午3:49
 */
class YCFCore
{
    private static $_instance;

    private static $router = null;

    private $init = false;
    private $conf;
    private $mode = RunMode::PRODUCT; //1:product,0:dev
    private $pathInfo = '/';
    private $contextPath = '/';
    private $routerList = array();
    private $controllerModule = null;
    private $twig = null;
    private $tpl = null;
    private $filter = array();

    private $interceptors = array(
        'before' => array(),
        'end' => array(),
        'ended' => array()
    );


    public static function getRouter()
    {
        if (self::$router != null) {
            return self::$router;
        }
        self::$router = new Router();
        return self::$router;
    }

    public function getTwig(){
        return $this->twig;
    }

    public function beforeInterceptor($func){
        $this->addInterceptor('before',$func);
        return $this;
    }

    /**
     * add action to interceptor
     * @param $hook
     * @param callback $function <p>
     * The function to be called. Class methods may also be invoked
     * statically using this function by passing
     * array($classname, $methodname) to this parameter.
     * Additionally class methods of an object instance may be called by passing
     * array($objectinstance, $methodname) to this parameter.
     * </p>
     * @return $this
     */
    public function addInterceptor($hook,$function){
        $this->interceptors[$hook] = $function;
        return $this;
    }
    public  function processInterceptor($hook,$params = null){
        if(isset($this->interceptors[$hook])){
            if(is_array($this->interceptors[$hook])){
                foreach($this->interceptors[$hook] as $callable){
                    call_user_func($callable,$params);
                }
            }else{
                call_user_func($this->interceptors[$hook],$params);
            }
        }
    }

    public function setModule($moduleDir)
    {
        $this->controllerModule = $moduleDir;
        return $this;
    }

    /**
     * set app run mode
     * @param $mode
     * @return $this
     * @throws YCRException
     */
    public function setRunMode($mode)
    {
        if($mode != RunMode::DEV && $mode != RunMode::PRODUCT){
            throw new YCRException('not support runtimes');
        }
        $this->mode = $mode;
        if($this->isDev()){
            error_reporting(E_ALL);
            header("Access-Control-Allow-Origin: *");
        }else{
            error_reporting(E_ERROR);
        }
        return $this;
    }

    public function isDev(){
        return $this->mode == RunMode::DEV;
    }

    static function getInstance()
    {
        if (self::$_instance != null) {
            return self::$_instance;
        }
        self::$_instance = new YCFCore();
        return self::$_instance;
    }

    private function __construct()
    {
       // $this->initPathInfo();
    }

    /**
     * 获取当前访问路径
     * @return string
     */
    public function getPath(){
        return $this->pathInfo;
    }
    public function getContextPath(){
        return $this->contextPath;
    }
    public function init($config = null)
    {
        try{
            if($this->init) return $this;
            $this->init = true;
            Logger::getLogger()->sys('RouteCore INIT');
            $appConfig = include(SYS_DIR.'var/conf.php');
            if ($config) {
                if(is_string($config)){
                    Logger::getLogger()->sys('init config '.$config);
                    if(!file_exists($config)){$config = APP_DIR.$config;}
                    if(!file_exists($config) ) $this->error(0,new YCRException('Config Not Found'));
                    $config = include($config);
                }
                if (isset($config['template']) && $config['template']) {
                    Logger::getLogger()->sys('init template engine');
                    $this->tpl = new TemplateCore();
                    $this->twig = $this->tpl->initEnv($config['template']['engine'],$config['template']['tpl_config'],$this->mode);
                }
                $appConfig = array_merge($appConfig,$config);
            }
            $this->conf = $appConfig;
            $this->startInitialEnv();
        }catch (Exception $e){
            $this->error($e->getCode(),$e);
        }
        return $this;
    }

    private function startInitialEnv(){
        $timezone = $this->getConf('timezone');
        //set timezone
        if(is_numeric($timezone)) ini_set('date.timezone','Etc/GMT-'.$timezone);
        else date_default_timezone_set($timezone);
        $ses = $this->getConf('session');

        if($ses['auto']){
            if($ses['id']) {
                if(isset($_COOKIE[$ses['id']])){
                    $sesId = $_COOKIE[$ses['id']];
                }elseif(isset($_POST[$ses['id']])){
                    $sesId = $_POST[$ses['id']];
                }else{
                    $sesId = uniqid();
                    setcookie($ses['id'],$sesId,null,'/',null,null,true);
                }
                session_id($sesId);
            }

            if(isset($ses['type']) && $ses['type'] == 'mysql'){
                $handler = new YCSessionHandler();
                session_set_save_handler(
                    array($handler, 'open'),
                    array($handler, 'close'),
                    array($handler, 'read'),
                    array($handler, 'write'),
                    array($handler, 'destroy'),
                    array($handler, 'gc')
                );
                Logger::getLogger()->debug('init-session');
                register_shutdown_function('session_write_close');
//                session_set_save_handler($handler);
            }
            start_the_session();
        }
        //
        $this->initPathInfo();
    }

    private function initRouter()
    {
        if ($this->mode == RunMode::PRODUCT && ($routerList = self::getRouter()->initRouter()) != false) {
            $this->routerList = $routerList;
        } else {
            Logger::getLogger()->sys('start initial router list');
            $this->routerList = self::getRouter()->getRouteList($this->controllerModule);
        }
    }

    private function initPathInfo()
    {
        $urlMode = $this->getConf('url_mode');
        $reqUri = $_SERVER['REQUEST_URI'];
        if (strpos($reqUri, '?')) {
            $reqUri = substr($reqUri, 0, strpos($reqUri, '?'));
        }
        $fileName = $_SERVER['SCRIPT_NAME'];
        if ($fileName == '/') {
            $fileName = '';
        }
        $this->pathInfo = substr($reqUri, strlen(dirname($fileName)));
        $this->contextPath = str_replace('\\','/',dirname($fileName).'');
        if(!str_startwith('/',$this->pathInfo))  $this->pathInfo = '/'.$this->pathInfo;
        if($urlMode == 'default' && isset($_GET['__r'])) {
            $this->pathInfo = $_GET['__r'];
        }
        return $this->pathInfo;
    }

    public function invoke($function,$params = null){
        call_user_func($function,$params);
        return $this;
    }

    public function dispatch()
    {
        try{
            if(!$this->init) $this->init();
            $this->initRouter();
        }catch (Exception $e){
            $this->error($e->getCode(),$e);
        }
        $router = $this->getMatchRouter();
        if ($router) {
            Logger::getLogger()->sys("dispatch @".$this->getPath());
            if ($router['function'] instanceof ReflectionMethod) {
                $cls = $router['function']->getDeclaringClass()->newInstance();
                $method = new ReflectionMethod($cls,'__setTemplateInstance');
                $method->invoke($cls,$this->tpl);//默认初始化__setTwig方法
                try{
                    $this->processInterceptor('before');
                    $this->invokeControllerMethod($cls,'before');
                    $router['function']->invokeArgs($cls, $router['pathParams']);
                    $this->invokeControllerMethod($cls,'after');
                    $this->processInterceptor('after');
                }catch (Exception $e){
                    $this->error($e->getCode(),$e);
                }
            } else {
                $this->processInterceptor('before');
                call_user_func_array($router['function'], $router['pathParams']);
                $this->processInterceptor('after');
            }
        }else{
            $this->error(404,new RouterException("Not Found Resource(未找到资源)"));
        }
    }

    private function invokeControllerMethod($cls,$methodName){
        if(method_exists($cls,$methodName)){
            $method = new ReflectionMethod($cls,$methodName);
            if($method->isPublic()){
                $method->invoke($cls);
            }
        }
    }

    private function send_http_status($code) {
        $_status = array(
            // Success 2xx
            200 => 'OK',
            // Redirection 3xx
            301 => 'Moved Permanently',
            302 => 'Moved Temporarily ',  // 1.1
            // Client Error 4xx
            400 => 'Bad Request',
            403 => 'Forbidden',
            404 => 'Not Found',
            // Server Error 5xx
            500 => 'Internal Server Error',
            503 => 'Service Unavailable',
        );
        if(isset($_status[$code])) {
            header('HTTP/1.0 '.$code.' '.$_status[$code]);
            // 确保FastCGI模式下正常
            header('Status:'.$code.' '.$_status[$code]);
        }
    }

    private function error($code,Exception $e){
        if($code == 0) $code = 500;
        if(isAjax()){
            ob_clean();
			$error_msg = $e->getMessage();
			if($this->mode == RunMode::PRODUCT){$error_msg='';}
            $error_msg = $error_msg ? $error_msg : 'access resource was bad';
            $data = '';
            if($e instanceof DBException && $this->isDev()){
                $data = DB()->last_query();
            }
            ajaxResponse($code,$error_msg,$data);
        }
        if($this->mode == RunMode::PRODUCT) //ob_clean();
        $trace = $e->getTrace();
        $trace = $e->getTrace();
        if(!isset($trace[0]['file'])){
            $trace[0]['file'] = $e->getFile();
            $trace[0]['line'] = $e->getLine();
        }
        $traceInfo = '';
        $time = date("Y-m-d H:i:s");
        $len = strlen(APP_DIR);
        if($e instanceof DBException){
            $traceInfo = DB()->last_query().'<br>';
        }

        foreach($trace as $t) {
            if($t){
                $traceInfo .= '['.$time.'] ';
                if(isset($t['file'])){
                    $traceInfo .= substr($t['file'],$len+1).' ('.$t['line'].') ';
                }
                if(isset($t['class'])){
                    $traceInfo .= $t['class'].$t['type'];
                }
                $traceInfo .= (isset($t['function'])?$t['function']:'').'(';
                if(isset($t['args']) && is_array($t['args'])){
                    $args = array();
                    foreach($t['args'] as $arg){
                        if(is_string($arg)){
                            $args[] = $arg;
                        }else if(is_object($arg)){
                            if($arg instanceof Controller) {
                                $args[] =get_class($arg);
                            }else{
                                $args[] = gettype($arg);
                            }
                        }else if(is_array($arg)){
                           // print_r($arg);
                            //TODO
                            //$args[] = 'Array('.implode(', ', $arg).')';
                        }
                    }
                    $traceInfo .= implode(', ', $args);
                }
                $traceInfo .= ")<br />\n";
            }
        }

        $message = stringFormat('{0} : {1}',get_class($e),$e->getMessage());
        if($this->hasConf('error')
            && $this->hasConf($code,$this->conf['error'])
            && file_exists($this->conf['error'][$code])){
            $message = $e->getMessage();
            include($this->conf['error'][$code]);
        }else{
            $this->send_http_status($code);
            if($this->mode == RunMode::PRODUCT){
                echo $message;
            }else{
                $subMessage = $traceInfo;
                include LIB_DIR.'/page/Error.php';
            }
        }
        $this->processInterceptor('ended');
        exit;
        //exit;
    }

    private function hasConf($key,$confs = null){
        if(!$confs){
            $confs = $this->conf;
        }
        return isset($confs[$key]);
    }
    public function setConf($key,$value){
        if($this->hasConf($key)){
            $this->conf[$key] = $value;
        }
    }
    public function getConf($key = null){
        if(!$key) return $this->conf;
        if($this->hasConf($key)){
            return $this->conf[$key];
        }
        return null;
    }

    private function getMatchRouter()
    {
        $path = $this->pathInfo;
        //构建首页路径
        if( $this->getConf('default_page') && $path == "/"){
            $path = $this->getConf('default_page');
        }
        if (isset($this->routerList[$path])) {
            $func = $this->routerList[$path];
            if(!$this->isDev()){
                $func = self::getRouter()->getRouteFunction($path);
            }
            return array(
                'function' => $func,
                'pathParams' => array()
            );
        } else {
            foreach ($this->routerList as $req => $func) {
                $currentUrl = $req;
                Logger::getLogger()->debug('match '.$req);
                $req = str_replace('/', '\/', $req);
                $reqRegex = preg_replace('/{([A-Za-z_-]+)}/', "([A-Za-z0-9_-]+)", $req);
                if (preg_match_all('/{([A-Za-z_-]+)}/', $req, $rem)) {
                    $paramsList = $rem[1];
                    if (preg_match('/' . $reqRegex . '/', $path, $pathParams)) {
                        Logger::getLogger()->sys('matched '.$req);
                        if(!$this->isDev()){
                            $func = self::getRouter()->getRouteFunction($currentUrl);
                        }
                        array_shift($pathParams);
                        $router = array(
                            'function' => $func,
                            'pathParams' => array()
                        );
                        $functionNeedParam = array();
                        foreach ($pathParams as $index => $p) {
//                            $this->getParam($functionNeedParam,$paramsList[$index],$p);
                            $functionNeedParam[$paramsList[$index]] = $p;
                        }
                        $ref = $router['function'];

                        if (!($router['function'] instanceof ReflectionMethod)) {
                            $ref = new ReflectionFunction($router['function']);
                        }
                        $params = $ref->getParameters();
                        foreach ($params as $functionParam) {
                            $paramsName = $functionParam->getName();
                            $optional = $functionParam->isOptional();
                            if(!$this->getParam($router['pathParams'],$paramsName)){
                                //检测特殊参数
                                if (isset($functionNeedParam[$paramsName])) {
                                    $router['pathParams'][$paramsName] = $functionNeedParam[$paramsName];
                                } else {
                                    if (!$optional) {
                                        throw new Exception('need param ' . $paramsName);
                                    }
                                    $router['pathParams'][$paramsName] = $functionParam->getDefaultValue();
                                }
                            }
                        }
                        return $router;
                    }
                }
            }
        }
        return null;
    }

    private function getParam(&$params,$name){
        if($name == 'render'){
            $params[$name] = $this->twig;
            return true;
        }
        if($name == 'uri'){
            $params[$name] = $this->getPath();
            return true;
        }
        return false;
    }
}