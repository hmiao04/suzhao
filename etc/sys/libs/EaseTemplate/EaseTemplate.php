<?php
/**
 * Created by PhpStorm.
 * User: home
 * Date: 2015/11/21
 * Time: 22:59
 */
if (is_file(dirname(__FILE__).'/template.ease.php')){
    include dirname(__FILE__).'/template.ease.php';
}else {
    die('Sorry. Load core file failed.');
}

/**
 * Class EaseTemplate
 * the config example
array(
    'TemplateDir' => 'static/tpl',
    'TplType' => 'twig',
    'CacheDir'	 =>'usr/cache',				//缓存目录
    'Compress' => 'off',
    'LeftDelimiter' => '{{',
    'RightDelimiter' => '}}',
    'cache' => '/usr/cache'
)
 */
class EaseTemplate extends TemplateDriver{
    private $engine = null;
    private $conf = null;
    public function init($config, $debug = false)
    {
        $config['WebURL'] = URL();
        $this->conf = $config;
        $this->engine = new ETCore($config);
        $this->assign('SITE_URL',URL());
    }

    private $vars = array();
    public function assign($key, $value)
    {
        $this->vars[$key] = $value;
    }

    public function render($file, $vars = array())
    {
        $vars = array_merge($this->vars, $vars);
        if($this->engine == null)  $this->engine = new ETCore($this->conf);
        $this->engine->set_file($file);
        $this->engine->ThisValue = $vars;
        $this->engine->p();
        $this->vars = null;
        unset($vars);
        unset($this);
    }

    /**
     * @param $path
     * @return mixed
     */
    public function setTemplatePath($path)
    {

    }
}