<?php

/**
 * File: ApiController.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-02-13 13:12
 */
class ApiController extends BaseController
{
    private $ignoreList = array(
        "saveCaptchaCode","checkCaptchaCode","init","processApi","methodInfo",
        "getLoginUserInfo","checkLogin","checkDataNull","getUserId","render",
        "groupIs","getQueryParam","recordLog","getUserListByGroup","getUserById",
        "getLoginUser","getPostData","getData","message","ajaxBasicInsert",
        "ajaxBasicUpdate","debug","getPageAndSize","getDefaultSize",
        "createWindowPageLink","createPageLink","saveUploadImage",
        "getFunction","getAuth","__setTemplateInstance","getInput",
        "input","getAssign","assign","verifyPermission","addRoute",
        "setRenderPath","getRunTimes","getConfig","spentStart","before",
        "calcSpent","cacheExists","getCache","setCache","arrayToString");

    protected function saveCaptchaCode($code)
    {
        $_SESSION["chk_code"] = $code;
    }

    protected function checkCaptchaCode($code)
    {
        $ret = isset($_SESSION["chk_code"]) && strtolower($_SESSION["chk_code"]) == strtolower($code);
        $_SESSION["chk_code"] = null;
        return $ret;
    }

    public function init()
    {
        $this->addRoute('/api/v1/gateway.do', 'processApi');
        $this->addRoute('/api/document', 'methodInfo');

        $clsName = get_class($this);
        $funcList = get_class_methods($clsName);
        $moduleName = $clsName;
        if (str_index_of($clsName, '\\') != -1) {
            $moduleName = substr($clsName, lastindexof($clsName, '\\') + 1);
        }
        $doc = null;$doc_tmp = '';
        foreach ($funcList as $funcName) {
            if (in_array($funcName, $this->ignoreList)) continue;
            $method = new ReflectionMethod($clsName, $funcName);
            if (!$method->isPublic() || strpos($funcName, '__') === 0) continue;

            $doc = str_replace(array('/','*'),'',$method->getDocComment());
            foreach(explode("\n",$doc) as $line){
                $line = trim($line);
                if(str_startwith('@',$line)) break;
                $doc_tmp .= $line."";
            }
            $doc = $doc_tmp;$doc_tmp = '';
            ApiProcess::AddApi(strtolower($moduleName.'.'.$method->getShortName()),$method,$doc);
        }
    }
    private function parseReturnDoc($document){
        $returns = array();
        $docCommentArr = explode("\n",$document);
        foreach ($docCommentArr as $comment) {
            $comment = trim($comment);
            //@return注释
            $pos = stripos($comment, '@return');
            if ($pos === false)continue;
            $returnCommentArr = explode(' ', substr($comment, $pos + 8));
            if (count($returnCommentArr) < 2) continue;
            if (!isset($returnCommentArr[2])) {
                $returnCommentArr[2] = '';	//可选的字段说明
            } else {
                //兼容处理有空格的注释
                $returnCommentArr[2] = implode(' ', array_slice($returnCommentArr, 2));
            }
            $returns[] = $returnCommentArr;
        }
        return $returns;
    }
    private function parseParamDoc($document){
        $returns = array();
        $docCommentArr = explode("\n",$document);
        foreach ($docCommentArr as $comment) {
            $comment = trim($comment);
            //@param注释
            $pos = stripos($comment, '@param');
            if ($pos === false)continue;
            $commentArr = explode(' ', substr($comment, $pos + 7));
            if (count($commentArr) < 2) continue;
            if (!isset($commentArr[2])) {
                $commentArr[2] = '';	//可选的字段说明
            } else {
                //兼容处理有空格的注释
                $commentArr[2] = implode(' ', array_slice($commentArr, 2));
            }
            $returns[] = $commentArr;
        }
        return $returns;
    }

    public function methodInfo()
    {
        $methodName = $this->input()->request('method');
        $tpl = 'api_list.php';
        if($methodName && ApiProcess::ApiExists($methodName)){
            $tpl = 'api_info.php';
            $method = ApiProcess::GetApi($methodName);
            $description = $method->document;
            $docCommentArr = explode("\n",$method->object->getDocComment());
            $returns = $this->parseReturnDoc($method->object->getDocComment());
            $params = $this->parseParamDoc($method->object->getDocComment());
        }
        include(APP_DIR.'/etc/sys/page/'.$tpl);
        exit;
    }
    public function processApi()
    {
        $method = $this->input()->request('method');
        if(!$method) ajaxError('参数无效(INVALID_PARAMETER)');
        $method = strtolower($method);
        if($method == 'method-list'){
            $list = array();
            foreach(ApiProcess::GetAll() as $key=>$m){
                $list[] = array('name'=>$m->name,'param'=>'method','document'=>$m->document);
            }
            ajaxSuccess($list);
        }
        if(!ApiProcess::ApiExists($method)) ajaxError('接口不存在(METHOD_NOT_EXIST)');
        $method = ApiProcess::GetApi($method)->object;
        $cls = $method->getDeclaringClass()->newInstance();
        $method->invoke($cls);
    }
    /**
     * @return \Models\MemberModel|null
     */
    protected function getLoginUserInfo(){
        if(isset($_SESSION[USER_SES_KEY]) && $_SESSION[USER_SES_KEY]){
            if(is_array($_SESSION[USER_SES_KEY])){
                $userInfo = new MemberModel();
                $userInfo->setPropertyValue($_SESSION[USER_SES_KEY]);
                return $userInfo;
            }
            return $_SESSION[USER_SES_KEY];
        }
        return null;
    }
    protected function checkLogin(){
        if(isset($_SESSION[USER_SES_KEY])) return $_SESSION[USER_SES_KEY];
        else{ ajaxError('用户必须首先登录(ERROR_UN_LOGIN)',403);}
    }
}