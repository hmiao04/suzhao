<?php
use Models\FileResource;
use Models\MemberModel;
use Models\MemberCertification;
use Models\PictureModel;

/**
 * Created by PhpStorm.
 * User: yancheng (cheng@love.xiaoyan.me)
 * Date: 15/5/10
 * Time: 下午10:26
 */
abstract class BaseController extends Controller
{

    /**
     * 批量验证数据合法性
     * @param array $checkRules
     *  array('post_key','error_code')
     * or
     * array('post_key',function(){},'error_code')
     * @param bool|true $returnDataWithKey
     * if true returned data has key
     * or no key just index
     * @param array|null $requestData
     * @param bool|false $checkAll
     * @return array post data sort by given array
     * @throws Exception
     */
    protected function checkDataNull($checkRules = array(), $returnDataWithKey = true, $requestData = null, $checkAll = false)
    {
        if (is_array($checkRules)) {
            $retData = array();
            $errorList = array();
            foreach ($checkRules as $rule) {
                $data = $this->getInput()->request($rule[0]);
                if ($requestData != null && isset($requestData[$rule[0]])) {
                    $data = $requestData[$rule[0]];
                }
                $dataKey = $rule[0];
                if (strpos($rule[0], '.') !== false) {
                    $dataKey = explode('.', $rule[0]);
                    $data = $this->getInput()->post($dataKey[0]);
                    $data = isset($data[$dataKey[1]]) ? $data[$dataKey[1]] : null;
                }
                $codeObj = $rule[1];
                $retPostData = YCFCore::getInstance()->isDev() ? $_POST : array();
                if (!is_numeric($codeObj) && $codeObj instanceof Closure) {//判断是否是回调
                    if (call_user_func_array($codeObj, array($data)) == false) {
                        $errorList[$codeObj] = $rule[2];
                        if (false == $checkAll) ajaxResponse($rule[2], $retPostData);
                    }
                } elseif (is_array($codeObj) && !$data) {//验证数据传递是否正确

                    if (is_numeric($data) && $data == '0') {
                        continue;
                    }
                    $errorList[$codeObj[0]] = $codeObj[1];

                    if (false == $checkAll) ajaxResponse($codeObj[0], $codeObj[1], $retPostData);
                } elseif (!trim($data)) {
                    if (is_numeric($data) && $data == '0') {
                        continue;
                    }
                    $null_msg = isset($rule[2]) ? $rule[2] : $rule[0] . '不能为空';
                    $errorList[$rule[0]] = $null_msg;
                    if (false == $checkAll) ajaxResponse(-1, $null_msg, $retPostData);
                }
                if (count($errorList) != 0 && $checkAll) {
                    ajaxResponse(-1, $errorList, $retPostData);
                }
                //根据是否要key构建数据
                if ($returnDataWithKey) {
                    if (is_array($dataKey)) {
                        if (!isset($retData[$dataKey[0]])) $retData[$dataKey[0]] = array();
                        $retData[$dataKey[0]][$dataKey[1]] = $data;
                    } else $retData[$rule[0]] = $data;
                } else $retData[] = $data;
            }
            return $retData;
        } else {
            throw new Exception('need array');
        }
    }

    /**
     * 获取当前登录用户的uid
     */
    protected function getUserId()
    {
		if($obj=$this->getLoginUser())//有用户数据 并将数据保存到$obj以方便使用
			return $obj->id;
		else
			return null;
       // return getLoginUid();//TODO 登录用户id
    }

    protected function render($templateFile, $vars = array())
    {
        $this->assign('res_url',$this->getConfig('upload')->getConfig('url')->getValue());
        $this->assign('cdn',$this->getConfig('site')->getConfig('cdn')->getValue());
        parent::render($templateFile, $vars);
    }

    protected function groupIs($groupAlias)
    {
        $user = $this->getLoginUser();
        return $user['group_alias'] == $groupAlias;
    }

    public function getQueryParam()
    {
        $where = array('query' => array(), 'page' => array());
        $arrGets = $this->input()->get();
        if (null == $arrGets) return $where;
        foreach ($arrGets as $strKey => $strValue) {
            if (in_array($strKey, array('per_page', 'per_page_size', 't', 'sn',
                '_size_ck', '_sort', '_type', 'runner', 'act', 'action'))) continue; //排除系统使用字段
            $where['page'][$strKey] = $strValue;
            $strKey = str_replace('#', '.', $strKey);
            $arrTemp = explode("-", $strKey);//分解
            $keyName = $arrTemp[0];
            $type = 'normal';
            if (count($arrTemp) == 2) $type = strtolower($arrTemp[1]);
            if ($type == 'like') {
                $keyName .= '[~]';
            } else if ($type == 'not_like') {
                $keyName .= '[!~]';
            } else if ($type == 'in') {
                $strValue = explode(',', $strValue);
            } else if ($type == 'between') {
                $strValue = explode(',', $strValue);
                $keyName .= '[<>]';
            }

            $where['query'][$keyName] = $strValue;
        }
        return $where;
    }

    private function fetchCondition()
    {
    }

    /**
     * 记录日志
     * @param $action
     * @param array $data
     * @param int $uid
     * @param int $state
     * @param string $remark
     */
    protected function recordLog($action, $uid = 0, $state = 1, $data = array(), $remark = '')
    {
        $log = new \Models\AdminLog();
        $log->log_time = REQ_TIME;
        $queryString = isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] != '' ? '?'.$_SERVER['QUERY_STRING'] : '';
        $log->log_item = YCFCore::getInstance()->getPath() . $queryString;
        $log->log_type = $action;
        $log->log_data = is_array($data) ? json_encode($data) : $data;
        $log->log_state = $state;
        $log->admin_id = $uid;
        $log->ip_address = getClientIP();
        $log->remarks = $remark;
        try {
            $log->insert();
        } catch (Exception $e) {
            throw $e;
        }
    }

    protected function getUserListByGroup($groupName)
    {
        $tablePre = $this->getConfig('db_config')->getConfig('db_prefix')->getValue();
        $querySQL = "SELECT
	u.*, g.title
FROM
	{$tablePre}user u,
	{$tablePre}auth_group g,
	{$tablePre}auth_group_access a
WHERE
	g.id = a.group_id
AND a.uid = u.uid
AND g. STATUS = 1
AND g.alias = '{$groupName}'";
        return DB()->fetchAll($querySQL);
    }

    private function initLoginUserInfo()
    {
        $userInfo = $this->getLoginUser();
        $this->assign('login_user_info', $userInfo);
        $this->assign('current_date', date('Y-m-d', REQ_TIME));
    }

    /**
     * 根据uid获取用户编号
     * @param $uid
     * @return array
     */
    protected function getUserById($uid)
    {
        $cache_id = 'U_C_I_' . $uid;
        if ($this->cacheExists($cache_id)) {
            return $this->getCache($cache_id);
        }
        $user = DB()
            ->field('g.title as group_name,g.alias as group_alias,a.group_id,u.*')
            ->table('user u')
            ->join(array(
                '[>]auth_group_access#a' => array('u.uid' => 'a.uid'),
                '[>]auth_group#g' => array('g.id' => 'a.group_id'),
            ))
            ->where('u.uid', $uid)
            ->get();
        if ($user) unset($user['login_pass']);
        $this->setCache($cache_id, $user);
        return $user;
    }

    /**
     * @return \Models\MemberModel
     */
    protected function getLoginUser(){
        $userInfo = new MemberModel();
        $userInfo->id = 0;
        if(isset($_SESSION[USER_SES_KEY]) && $_SESSION[USER_SES_KEY]){
            $userInfo->id = $_SESSION[USER_SES_KEY];
            if(!$userInfo->find()){
                $userInfo->id = 0;
                return $userInfo;
            }
            $memberCert = new \Models\MemberCertification();
            $certData = $memberCert->findByPrimary($userInfo->id);
            if($certData != null) $certData = $memberCert->getCertificationData();
            $userInfo->extraData['Certification'] = $certData;
            return $userInfo;
        }
        return $userInfo;
    }

    /**
     * 批量获取post的数据并重命名
     * @param $saveData
     * @param $keys
     */
    protected function getPostData(&$saveData, $keys)
    {
        if (is_string($keys))	$keys = explode(',', $keys);
		else if(count(array_diff(array_keys($keys), array_keys(array_keys($keys))))){
			foreach ($keys as $k=>$newKey) $saveData[$newKey] = $this->getInput()->post($k);
			return $saveData;			
		}
        foreach ($keys as $k) $saveData[$k] = $this->getInput()->post($k);
        return $saveData;
    }
	
    /**
     * 批量获取get的数据
     * @param $saveData
     * @param $keys
     */
    protected function getData(&$saveData, $keys)
    {
        if (is_string($keys)) $keys = explode(',', $keys);
		else if(count(array_diff(array_keys($keys), array_keys(array_keys($keys))))){
			foreach ($keys as $k=>$newKey) $saveData[$newKey] = $this->getInput()->get($k);
			return $saveData;			
		}
        foreach ($keys as $k) $saveData[$k] = $this->getInput()->get($k);
        return $saveData;
    }

/* 数组打印		数组,分割符*/
function arrayToString($array,$t=""){
	if($t == "")
		$t ="\n";
	else
		$t .="\t";
	if (is_object($array))    //对象转换成数组
        $array = get_object_vars($array);	
	else if (!is_array($array))
		return false;
		
	$associative = count(array_diff(array_keys($array), array_keys(array_keys($array))));
	if ($associative) {
		$construct = array();
		foreach ($array as $key => $value) {
			if (is_numeric($key))
				$key = "$key";
			$key = '"' . addslashes($key) . '"';//addslashes() 函数返回在预定义字符之前添加反斜杠的字符串。预定义字符是：＿ " \ NULL＿

			if (is_array($value)) // Format the value:
				$value = arrayToString($value,$t);
			else if (is_bool($value))
				$value = $value ? 'TRUE' : 'FALSE';
			else if (is_int($value))
				$value = intval($value);
			else if (is_float($value))
				$value = floatval($value);
			else if ($value === NULL)
				$value = 'NULL';
			else if (is_string($value))
				$value = '"' . addslashes($value) . '"';

			$construct [] = "$key => $value"; // Add to staging array:
		}// Then we collapse the staging array into the JSON form:

		$result = "array($t\t" . implode(",$t\t", $construct) . "$t)";//implode把数组元素组合为字符丿

	} else {
		$construct = array();// If the array is a vector (not associative):
		foreach ($array as $value) {
			if (is_array($value))// Format the value:
				$value = arrayToString($value,$t);
			else if (is_bool($value))
				$value = $value ? 'TRUE' : 'FALSE';
			else if (is_int($value))
				$value = intval($value);
			else if (is_float($value))
				$value = floatval($value);
			else if ($value === NULL)
				$value = 'NULL';
			else if (is_string($value))
				$value = '"' . addslashes($value) . '"';
			
			$construct [] = $value;// Add to staging array:
		}

		// Then we collapse the staging array into the JSON form:
		$result = "array($t\t" . implode(",$t\t", $construct) . "$t)";
	}

	return $result;
}
	
	
    protected function message($message, $url = null, $is_dialog = false, $code = 1)
    {
        if (isAjax()) {
            ajaxResponse($code, $message, array('url' => $url));
        }
        if (!$url || strtolower($url) == 'goback') $url = 'javascript:history.go(-1);';
        $this->render('message.core', array('message' => $message, 'url' => $url, 'is_dialog' => $is_dialog));
        exit;
    }

    protected function ajaxBasicInsert($table, $data, $return = true, $catch = true)
    {
        try {
            $ret = DB()->insert($table, $data);
            if ($return) return $ret;
            ajaxResponse(0, 'success', array('id' => $ret));
        } catch (Exception $e) {
            $data = '';
            if ($e instanceof DBException) {
                $data = DB()->last_query();
                if ($catch) ajaxResponse(RetMessage::$DBError, $data);
            }
            if ($catch) ajaxResponse($e->getCode(), $e->getMessage(), $data);
            throw $e;
        }
        return false;
    }

    protected function ajaxBasicUpdate($table, $data, $where, $return = true)
    {
        try {
            $ret = DB()->update($table, $data, $where);
            if ($return) return $ret;
            ajaxSuccess();
        } catch (Exception $e) {
            $data = '';
            if ($e instanceof DBException) {
                $data = DB()->last_query();
                ajaxResponse(RetMessage::$DBError, $data);
            }
            ajaxResponse($e->getCode(), $e->getMessage(), $data);
        }
        return false;
    }

    protected function debug($data)
    {
        echo '<pre>';
        print_r($data);
        echo '</pre>';
    }

    /**
     * 获取分页、条数、起始位置
     * @param int $defaultSize 10
     * @param int $currentPage null
     * @param int $needSize 0
     * @return array array(page,size,start)
     */
    protected function getPageAndSize($defaultSize = 10, $currentPage = null, $needSize = 0)
    {
        $page = $this->getInput()->get('per_page');
        $size = $this->getDefaultSize($defaultSize);
        if ($needSize > 0) $size = $needSize;
        if (!$page || !is_numeric($page) || $page < 1) {
            $page = 1;
        }
        if (!$size || !is_numeric($size) || $size < 1) {
            $size = $defaultSize;
        }
        if ($currentPage) $page = $currentPage;
        return array($page, $size, ($page - 1) * $size);
    }

    protected function getDefaultSize($defaultSize = 10, $sizeKey = 'yc_default_size')
    {
        $defaultSize = 10;
        if ($this->getInput()->cookie($sizeKey)) {
            $defaultSize = (int)$this->getInput()->cookie($sizeKey);
            if ($defaultSize < 1) {
                $defaultSize = 10;
                setcookie($sizeKey, $defaultSize, time() + 3600 * 24 * 30);//30d exp
            }
        } else {
            setcookie($sizeKey, $defaultSize, time() + 3600 * 24 * 30);//30d exp
        }
        if ($this->input()->get('_size')) {
            $defaultSize = $this->getInput()->get('_size');
            if ($defaultSize > 0) {
                setcookie($sizeKey, $defaultSize, time() + 3600 * 24 * 30);//30d exp
            }
        }
        return $defaultSize;
    }

    protected function createWindowPageLink($baseUrl, $total, $size)
    {
        $this->assign('totalCount', $total);
        $this->assign('currentUrl', $baseUrl);
        $pageString = $this->createPageLink($baseUrl, $total, $size, true, 'pull-right');
        $totalPage = ceil($total / $size);
        $this->assign('pageString', $pageString);
        $this->assign('pageSize', $size);
        $this->assign('totalPage', $totalPage);
        if (strpos($baseUrl, '?') == false) {
            $baseUrl .= '?t=' . time();
        }
        $this->assign('currentUrl', $baseUrl);
        list($curPage, $size, $star) = $this->getPageAndSize();
        if ($curPage > $totalPage && $total > 0) {
            header('Location: ' . $baseUrl . '&per_page=' . $totalPage);
        }
        return $pageString;
    }

    protected function createPageLink($baseUrl, $total, $size, $showPage = TRUE, $style = '', $suffix = '')
    {
        $pagination = Pagination::getPagination(array(
            'anchor_class' => 'ycs-link-pager'
        ));
        $config = array();
        $config['base_url'] = $baseUrl;
        $config['total_rows'] = $total;
        $config['per_page'] = $size;
        $config['use_page_numbers'] = TRUE;
        $config['display_pages'] = $showPage;

        $config['full_tag_open'] = '<ul class="pagination ' . $style . '">';
        $config['full_tag_close'] = '</ul>';
        $config['suffix'] = $suffix;

        $config['cur_tag_open'] = '<li class="active"><span>';
        $config['cur_tag_close'] = '</span></li>';

        $config['num_tag_open'] = '<li>';
        $config['num_tag_close'] = '</li>';

        $config['prev_link'] = '上一页';
        $config['prev_link'] = false;
        $config['prev_tag_open'] = '<li>';
        $config['prev_tag_close'] = '</li>';
        $config['next_link'] = '下一页';
        $config['next_link'] = false;
        $config['next_tag_open'] = '<li>';
        $config['next_tag_close'] = '</li>';

        $config['last_link'] = '末页';
        $config['last_tag_open'] = '<li>';
        $config['last_tag_close'] = '</li>';
        $config['first_link'] = '首页';
        $config['first_tag_open'] = '<li>';
        $config['first_tag_close'] = '</li>';
        $pagination->initialize($config);
        return $pagination->create_links();
    }


    protected function getLoginMemberId()
    {
        $m = $this->getLoginMemberInfo();
        return null == $m ? 0 : $m->id;
    }

    protected function needAjax(){
        if(!isAjax()) ajaxError('not support current request type');
    }

    /**
     * @return \Models\MemberModel
     */
    protected function getLoginMemberInfo()
    {
        return $this->getLoginUser();
//        if (isset($_SESSION[USER_SES_KEY])) {
//            return $_SESSION[USER_SES_KEY];
//        }
//        return null;
    }

    /**
     * 保存文件资源
     * @param $filename
     * @param string $originName
     * @param int $fileSize
     * @param string $remark
     * @param string $type
     * @return $this
     */
    public function saveFileResource($filename, $originName = '', $fileSize = 0, $remark = '', $type = 'application/octet-stream')
    {
        $file = new FileResource();
        $file ->saveResource($filename, $originName, $fileSize, $remark, $type,$this->getLoginMemberId());
        if($file->lastInsertId > 0){
            $pic = new PictureModel();
            $pic->file_id = $file->lastInsertId;
            $pic->member_id = $file->member_id;
            $cate_id = $this->input()->get('cate_id');
            if(!$cate_id || !preg_match('/^\d+$/',$cate_id)){
                $cate_id = 0;
            }
            $pic->category_id = $cate_id;
            $pic->upload_time = REQ_TIME;
            if($pic->insert()->lastInsertId > 0) {
                return true;
            }
            //删除已经上传的数据
            $file->delete(['file_id'=>$file->lastInsertId]);
        }
        return false;
    }

    /**
     * 保存上传的文件
     * @param $imageKey
     * @param string $type
     * @param null $prefix
     * @return null|string
     * @throws AppException
     * @throws FileException
     * @throws \lib\Uploader\UploadException
     */
    protected function saveUploadImage($imageKey, $type = '',$prefix = null)
    {
        $fileName = null;
        if(isset($_FILES[$imageKey]) && $_FILES[$imageKey]['tmp_name']  && $_FILES[$imageKey]['size'] > 0){
            $fileObject = $_FILES[$imageKey];
            $picType = strtolower(file_get_mime($fileObject['tmp_name']));
            $fileName = \Lib\Uploader\UploaderTool::GetUploader()->upload($fileObject,$prefix);
            if(!$this->saveFileResource($fileName,$fileObject['name'],$fileObject['size'],'',$picType)){
                //TODO
                throw new FileException('Upload Success,but save data fail');
            }
        }
        return $fileName;
    }

	private $cstNavPath = array();

    protected function pushNavPath($name, $url = '#')
    {
        $this->cstNavPath[] = array('res_name' => $name, 'res_url' => $url);
    }
	
	protected function MakeMenu($list){
		$navDataList = array();
        foreach ($list as $nav) {
            if ($nav['parent_id'] == 0) {
                $nav['active'] = false;
                $navDataList[] = $nav;
            }
        }
        $nav_path = array();
        //
        if (!$this->getAssign('navCurrent')) {
            $this->assign('navCurrent', YCF::Instance()->getRouterCore()->getPath());
        }
        $navCurrent = $this->getAssign('navCurrent');
        foreach ($navDataList as &$nav) {
            $nav['child'] = array();
            foreach ($list as $n) {
                if ($n['parent_id'] == $nav['id']) {
                    if ($navCurrent == $n['res_url']) {
                        $nav_path[] = $nav;
                        $nav_path[] = $n;
                        $n['active'] = true;
                        $nav['active'] = true;
                    }
                    $nav['child'][] = $n;
                }
            }
        }
        foreach ($this->cstNavPath as $np) {
            $nav_path[] = $np;
        }
		return array($navDataList,$nav_path);
	}
}