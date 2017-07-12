<?php

class YCRException extends Exception
{
    function __construct($message, $code = -1, Exception $previous = null)
    {
        $this->code = $code;
        $this->message = $message;
    }
}

class RouterException extends YCRException
{

    function __construct($message)
    {
        parent::__construct($message, $this->getCode() + 5000);
    }
}

class FileException extends YCRException
{
    function __construct($message)
    {
        parent::__construct($message, $this->getCode() + 4000);
    }
}

class NotFoundException extends YCRException
{
    function __construct($message, $code = 404)
    {
        parent::__construct($message, $code);
    }
}


class DBException extends YCRException
{
    function __construct($message, $code, Exception $previous = null)
    {
        parent::__construct($message, $this->getCode() + 6000, $previous);
    }
}

class TemplateException extends YCRException
{
}

class ServiceException extends YCRException
{
}

class AppException extends YCRException
{
    function __construct($message, $code = 0, Exception $previous = null)
    {
        if ($message instanceof Exception) {
            $previous = $message;
            $code = $message->getCode();
            $message = $message->getMessage();
        }
        $code = $code == 0 ? $this->getCode() : $code;
        parent::__construct($message, $code, $previous);
    }
}

class PermissionException extends AppException
{
    function __construct($message = '非法请求(INVALID_REQUEST)', $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}