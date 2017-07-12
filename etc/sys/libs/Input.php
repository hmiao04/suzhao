<?php
/**
 * File: Input.php:YCMS
 * User: xiaoyan f@yanyunfeng.com
 * Date: 15-5-5
 * Time: 下午2:34
 * @Description
 */
class Input extends YC{
    public function get($key = null,$xss_clean = true){
        if ($key === NULL && ! empty($_GET))
        {
            $get = array();

            foreach (array_keys($_GET) as $key)
            {
                $get[$key] = $this->fetch_from_array($_GET, $key, $xss_clean);
            }
            return $get;
        }

        return $this->fetch_from_array($_GET, $key, $xss_clean);
    }
    public function getInt($key){
        return intval($this->get($key));
    }
    public function post($key = NULL, $xss_clean = true)
    {
        if ($key === NULL && ! empty($_POST))
        {
            $post = array();

            foreach (array_keys($_POST) as $key)
            {
                $post[$key] = $this->fetch_from_array($_POST, $key, $xss_clean);
            }
            return $post;
        }

        return $this->fetch_from_array($_POST, $key, $xss_clean);
    }
    public function cookie($key=null,$xss_clean = true){
        return $this->get_the_data($_COOKIE,$key,$xss_clean);
    }
    public function request($key=null,$xss_clean = true){
        return $this->get_the_data($_REQUEST,$key,$xss_clean);
    }


    private function get_the_data($oridata,$key=null,$xss_clean = true){
        if ($key === NULL && ! empty($oridata))
        {
            $get = array();

            foreach (array_keys($oridata) as $key)
            {
                $get[$key] = $this->fetch_from_array($oridata, $key, $xss_clean);
            }
            return $get;
        }
        return $this->fetch_from_array($_REQUEST, $key, $xss_clean);
    }

    private function fetch_from_array(&$array, $index = '', $xss_clean = FALSE)
    {
        if ( ! isset($array[$index]))
        {
            return null;
        }

        if ($xss_clean === TRUE)
        {
            return YCLoader::init()->getSec()->xss_clean($array[$index]);
//            return YCLoader::init()->getSec()->safeFilter($array[$index]);
        }

        return $array[$index];
    }
}