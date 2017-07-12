<?php

/**
 * File: ApiProcess.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-02-13 14:18
 */
class ApiMethod{
    public $name;
    /**
     * @var \ReflectionMethod
     */
    public $object;
    public $document;

    public function __construct($method, $handler,$document = '')
    {
        $this->document = $document;
        $this->object = $handler;
        $this->name = $method;
    }
}
class ApiProcess
{
    /**
     * @var \ApiProcess
     */
    private static $instance = null;
    private static $apiMethods = array();

    /**
     * @param string $method
     * @param callable $handler
     * @param string $document
     * @throws AppException
     */
    public static function AddApi($method, $handler,$document = '')
    {
        if (isset(self::$apiMethods[$method])) throw new AppException('api method exists!');
        self::$apiMethods[$method] = new ApiMethod($method,$handler,$document);
    }

    public static function Instance()
    {
        if (null == self::$instance) self::$instance = new ApiProcess();
        return self::$instance;
    }

    public static function ApiExists($method)
    {
        return isset(self::$apiMethods[$method]);
    }

    public static function GetAll(){
        return self::$apiMethods;
    }

    /**
     * @param $method
     * @return \ApiMethod
     */
    public static function GetApi($method)
    {
        return self::$apiMethods[$method];
    }
}