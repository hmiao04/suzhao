<?php

/**
 * Created by PhpStorm.
 * User: home
 * Date: 2015/11/28
 * Time: 11:11
 */
if (is_file(dirname(__FILE__) . '/Twig/Autoloader.php')) {
    include dirname(__FILE__) . '/Twig/Autoloader.php';
} else {
    throw new AppException('Sorry. Load Twig core file failed.');
}

class Twig extends TemplateDriver
{
    private $init = false;
    /**
     * @var \Twig_Environment
     */
    private $twig;
    private $currentPath = null;

    private $vars = array();

    public function assign($key, $value)
    {
        $this->vars[$key] = $value;
    }

    public function  __construct($config = array(), $debug = false)
    {
        $this->init($config, $debug);
    }

    public function render($templateFile, $vars = array())
    {
        $vars = array_merge($this->vars, $vars);
        if (!str_endwith('.twig', $templateFile)) {
            $templateFile .= '.twig';
        }
        echo $this->twig->render($templateFile, $vars);
    }

    public function init($config, $debug = false)
    {
        if ($this->init) {
            return $this->twig;
        }
        Twig_Autoloader::register();
        $this->currentPath = $config['path'];
        $loader = new Twig_Loader_Filesystem($config['path']);

        $twig = new Twig_Environment($loader, array(
            'cache' => $config['cache'],
            'debug' => $debug
        ));
        $twig->addExtension(new TwigExt());
        $this->init = true;
        $this->twig = $twig;
        return $twig;
    }

    /**
     * @param $path
     * @return mixed
     */
    public function setTemplatePath($path)
    {
        if ($this->currentPath == $path) return $this;
        $newLoader = new Twig_Loader_Filesystem($path);
        $this->twig->setLoader($newLoader);
        return $this;
    }
}