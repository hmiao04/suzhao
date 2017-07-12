<?php
/**
 * File: Resource.php:newsys
 * User: xiaoyan f@yanyunfeng.com
 * Date: 2017/1/12
 * Time: 14:04
 * @Description
 */

namespace Controller;


use Lib\WebController;
use PHPThumb\GD;

class Resource extends WebController
{
    private $prefix_path = '/resource';

    public function init()
    {
        $this->addRoute($this->prefix_path . '/{path}', 'processPicture');
        $this->addRoute('/file/upload','uploadFile');
    }

    public function processPicture($path)
    {
        $requestPath = $_SERVER['REQUEST_URI'];
        $queryString = '';
        //$queryString = $_SERVER['QUERY_STRING'];
        if(strpos($requestPath,'?') != false){
            $queryString = substr($requestPath,strpos($requestPath,'?')+1);
        }
        $context = \YCF::Instance()->getRouterCore()->getContextPath();
        if($context == '/') $context = '';
        if(substr($requestPath,0,2) == '//') $requestPath = substr($requestPath,1);
        $url = substr(substr($requestPath, strlen($context)), strlen($this->prefix_path));

        if (strlen($queryString) > 0) {
            $url = substr($url, 0, 0 - strlen($queryString) - 1);
        }else if(substr($url,-1) == '?'){
            $url = substr($url,0,-1);
        }

        header('Content-Path:'.$url);
        $file_path = APP_DIR . $url;
        if (!file_exists($file_path)) {
            //header('Content-Type:application/json');
            //die('{"error":"Document not found"}');
            $file_path = APP_DIR.'/assets/images/no_file.jpg';
        }
        $key = '';
        $options = array('quality' => 90);
        $suffix = substr($file_path,lastindexof($file_path,'.'));
        if (strlen($queryString) > 0) {
            $key = hash ('crc32', $queryString);
            $all_param = explode('&',$queryString);
            foreach($all_param as $paramString){
                $param_item = explode('=',$paramString);
                if(count($param_item) == 2 && $param_item[0] == 'x-oss-process') continue;
                $params = explode('/', $paramString);
                foreach ($params as $param) {
                    $p = explode('=', $param);
                    if (count($p) == 2) {
                        $options[$p[0]] = $p[1];
                    }else if(count($p) == 1){
                        $options[$param] = true;
                    }
                }
            }
        }
        $use_cache = false;
        if($use_cache){
            if($key){
                $cache_path = '/assets/cache/'.$key.$suffix;
                if(!file_exists(APP_DIR.$cache_path)){
                    $thumb = new GD($file_path);
                    $this->resize($thumb,$options);
                    $thumb->setOptions($options);
                    $thumb->save(APP_DIR.$cache_path);
                }
                $url = URL().$cache_path;
            }
            header('Location: '.$url);exit;
        }
        ob_clean();
        $thumb = new GD($file_path);
        $this->resize($thumb,$options);
        $thumb->setOptions($options);
        $formats = ['jpg','png','gif','webp'];
        $o = $this->input()->get('o');
        if($o && in_array(strtolower($o),$formats)){
            $o = strtoupper($o);
        }else $o = null;
        $thumb->show(false,$o);
    }

    /**
     * @param GD $thumb
     * @param array $option
     */
    private function resize(GD &$thumb, $option = array())
    {
        if (isset($option['resize']) &&
            (isset($option['w']) || isset($option['h']))) {
            $data = $thumb->getCurrentDimensions();
            $width = $data['width'];
            $height = $data['height'];
            $w = isset($option['w']) ? $option['w'] : 0;
            $h = isset($option['h']) ? $option['h'] : 0;
            if ($w > $width) $w = $width;
            if ($h > $height) $h = $height;
            if ($w == 0 && $h == 0) return;
            if ($w == $width && $h == $height) return;
            $thumb->resize($w, $h);
        }
    }

    public function uploadFile()
    {
        $file_key = $this->input()->get('file_key');
        if(!$file_key){
            $file_key = 'Filedata';
        }
        $file = $this->saveUploadImage($file_key);
        if($file){
            ajaxSuccess($file);
        }
        ajaxError('上传文件失败(ERROR_UPLOAD)',1,$_FILES);
    }
}