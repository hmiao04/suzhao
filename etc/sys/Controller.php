<?php

/**
 * Created by PhpStorm.
 * User: yancheng (cheng@love.xiaoyan.me)
 * Date: 14-9-16
 * Time: 下午3:58
 */
abstract class Controller
{
    /**
     * @var \TemplateCore
     */
    private $templateInstance;

    /**
     * @var \Auth
     */
    private $authLib;

    public abstract function init();

    protected function getFunction($name)
    {
        if (!method_exists($this, $name)) {
            //throw new AppException('not found method '.$name);
            return null;
        }
        $method = new ReflectionMethod($this, $name);
        return $method;
    }

    /**
     * @return Auth
     */
    public function getAuth()
    {
        if (!class_exists('Auth')) {
            include LIB_DIR . '/libs/Auth.php';
        }
        if ($this->authLib != null) {
            return $this->authLib;
        }
        $lib = new Auth();
        $this->authLib = $lib;
        return $this->authLib;
    }

    public function __setTemplateInstance($templateInstance)
    {
        $this->templateInstance = $templateInstance;
    }

    protected function getInput()
    {
        return YCLoader::init()->getInput();
    }

    protected function input()
    {
        return YCLoader::init()->getInput();
    }

    private $vars = array();

    protected function getAssign($key = null)
    {
        if (null == $key) return $this->vars;
        return isset($this->vars[$key]) ? $this->vars[$key] : null;
    }

    protected function assign($key, $value = null)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->vars[$k] = $v;
                $this->templateInstance->assign($k, $v);
            }
        } else {
            $this->vars[$key] = $value;
            $this->templateInstance->assign($key, $value);
        }
    }

    protected function checkRequestMethod($allowMethod = null)
    {
        if (null == $allowMethod || strtoupper($allowMethod) == 'ALL') return true;
        if (!is_array($allowMethod)) $allowMethod = explode(',', $allowMethod);
        foreach ($allowMethod as $k => $v) {
            $allowMethod[$k] = strtoupper($v);
        }
        $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
        if (in_array(strtoupper($method), $allowMethod)) return true;
        ajaxError('方法未允许(ERROR_NOT_ALLOWED_METHOD)');
    }

    /**
     * 检查权限
     * @param string|array $name 需要验证的规则列表,支持逗号分隔的权限规则或索引数组
     * @param int $uid 认证用户的id
     * @param  string $relation
     *      如果为 'or' 表示满足任一条规则即通过验证;
     *      如果为 'and'则表示需满足所有规则才能通过验证
     * @param string $mode 执行check的模式
     * @param int $type
     * @return bool           通过验证返回true;失败返回false
     */
    protected function verifyPermission($name, $uid, $relation = 'or', $mode = 'url', $type = 1)
    {
        return $this->getAuth()->check($name, $uid, $type, $mode, $relation);
    }

    /**
     * 添加匹配的求求
     * @param $url
     * @param callback $function <p>
     * The function to be called. Class methods may also be invoked
     * statically using this function by passing
     * array($classname, $methodname) to this parameter.
     * Additionally class methods of an object instance may be called by passing
     * array($objectinstance, $methodname) to this parameter.
     * </p>
     */
    protected function addRoute($url, $function)
    {
        $obj = $function;
        if (is_string($function)) {
            $obj = $this->getFunction($function);
        }
        YCFCore::getRouter()->addRoute($url, $obj);
    }

    protected function setRenderPath($path)
    {
        $this->templateInstance->setTemplatePath($path);
    }

    protected function getRunTimes()
    {
        global $start_time;
        $endTime = microtime(true);
        $mem = sprintf('%.2f M', memory_get_usage() / 1024 / 1024);
        return array(
            'current' => REQ_TIME,
            'time' => round($endTime - $start_time, 3),
            'sql' => round(DB()->getRunTime(), 3),
            'datetime' => array(
                'current' => date('Y-m-d h:i:s'),
                'year' => date('Y'),
                'month' => date('m')
            ),
            'mem' => $mem
        );
    }

    /**
     * 非法表达提交
     * @param string $name
     * @param bool|false $return
     * @return bool
     * @throws PermissionException
     */
    protected function formHashIsValid($name = 'form_hash', $return = false)
    {
        $hash = $this->input()->post($name);
        if (!$hash || count(explode('_', $hash)) != 2) {
            if ($return) return false;
            throw new \PermissionException();
        }
        $hash = explode('_', $hash);
        $hash_data_time = $hash[0];
        $hash = $hash[1];
        $check_hash = substr(md5(ACCESS_KEY . $hash_data_time), 8, 16);
        if ($check_hash != $hash) {
            if ($return) return false;
            throw new \PermissionException();
        }
        return true;
    }

    private function createFormHash()
    {
        $hash = substr(md5(ACCESS_KEY . REQ_TIME), 8, 16);//var_dump( ACCESS_KEY.REQ_TIME);
        $hash = REQ_TIME . '_' . $hash;
        $this->assign('FROM_HASH', $hash);
        $_SESSION['FROM_HASH'] = array('time' => REQ_TIME, 'hash' => $hash);
    }

    protected function render($templateFile, $vars = array())
    {
        if ($this->templateInstance == null) {
            throw new TemplateException('Template engine not initialization');
        }
        $this->assign('RunTimes', $this->getRunTimes());
        $this->assign('SITE_URL', URL(true));
        $uploadUrl = $this->getConfig('upload')->getConfig('url')->getValue();
        $this->assign('IMAGE_URL', $uploadUrl);
        $this->createFormHash();
        if (strpos($templateFile, '.') != -1) $templateFile = str_replace('.', '/', $templateFile);
        try {
            $this->templateInstance->render($templateFile, $vars);
        } catch (Exception $e) {
            throw $e;
        }
    }

    protected function getConfig($key)
    {
        return new Config($key);
    }

    private $startTime;

    protected function spentStart()
    {
        $this->startTime = microtime(true);
    }

    public function before()
    {
    }

    protected function calcSpent()
    {
        printf(" total run: %.2f s" .
            "memory usage: %.2f M<br> ",
            microtime(true) - $this->startTime,
            memory_get_usage() / 1024 / 1024);
    }

    protected function cacheExists($key)
    {
        return Cache::getInstance()->exists($key);
    }

    protected function getCache($key)
    {
        return Cache::getInstance()->get($key);
    }

    protected function setCache($key, $v)
    {
        return Cache::getInstance()->set($key, $v);
    }
}