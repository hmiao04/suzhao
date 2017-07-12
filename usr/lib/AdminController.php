<?php

/**
 * File: Administrator.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2016-09-11 17:50
 */
abstract class AdminController extends BaseController
{
    protected $adminBaseUrl = APP_MANAGE_NAME;
    private $isInit = false;
    const ROLE_ADMIN = 1;
    const ROLE_OPERATOR = 0;

    /**
     * set base url for admin
     * @param $baseUrl
     */
    protected function setAdminBaseUrl($baseUrl)
    {
        $this->adminBaseUrl = $baseUrl;
    }

    public function before()
    {
        $loginInfo = $this->getLoginInfo();
        if ($loginInfo->id < 1) {
            $url = URL(1) . '/account/admin.login.html';
            if (isAjax()) {
                $style = ' style="text-decoration:underline;color:#00f;" ';
                ajaxResponse(403, stringFormat('登录信息丢失，请<a href="javascript:location.reload();"{1}>刷新</a>网页或者<a href="{0}"{1}>重新登录</a>', $url, $style));
            }
            $url = URL(1) . '/account/admin.login.html?callback=' . urlencode(URL(1) . YCF::Instance()->getRouterCore()->getPath());
            if (@@headers_sent()) {
                die('<meta http-equiv="refresh" content="5; url=' . $url . '">');
            }
            header('Location: ' . $url);
            exit;
        }
        $urlPath = YCF::Instance()->getRouterCore()->getPath();
//
        $roleId = $this->getAdminRole();
        $roleModel = new \Models\RoleResources();
        $res = new \Models\AdminResources();
        if ($roleModel->findByPrimary($roleId) && $res->find(array('res_url' => $urlPath))) {
            $permission = explode(',', $roleModel->res_id);
            if (!in_array($res->id, $permission)) {
                $this->error('您没有权限访问此页面');
            }

        }
    }

    protected function isSuperAdmin()
    {
        //TODO 设置管理员为1
        return in_array($this->getLoginInfo()->role_id, array(1, 2));
    }

    protected function apiTest($data = array())
    {
        ajaxResponse(1, 'api data test', $data);
    }

    protected function debug($data)
    {
        echo '<pre>', print_r($data, 1), '</pre>';
    }

    /**
     * @param string $url
     * @param callback $callable
     */
    protected function addAdminUrl($url, $callable)
    {
        if (!$this->isInit) {

            $this->isInit = true;
        }
        $this->addRoute($this->adminBaseUrl . '/' . $url, $callable);
    }


    protected function error($message, $errorDetail = '')
    {
        $this->render('error', array(
            'error_message' => $message,
            'error_message_detail' => $errorDetail
        ));
    }

    protected function throwErrorMessage($msg)
    {
        header('HTTP/1.1 500 Internal Server Error');
        die($msg);
    }

    private $cstNavPath = array();

    protected function pushNavPath($name, $url = '#')
    {
        $this->cstNavPath[] = array('res_name' => $name, 'res_url' => $url);
    }

    protected function  renderAjax($templateFile, $vars = array())
    {
        $path = $this->getConfig('template')->getConfig('tpl_config')->getConfig('admin')->getValue();
        $this->setRenderPath($path);
        parent::render($templateFile, $vars);
        exit;
    }

    protected function  render($templateFile, $vars = array())
    {
        $loginInfo = $this->getLoginFullInfo();
        $path = $this->getConfig('template')->getConfig('tpl_config')->getConfig('admin')->getValue();
        $this->setRenderPath($path);
        //获取权限
        $roleId = $this->getAdminRole();
        $querySQL = "SELECT * FROM yc_admin_resources where
 FIND_IN_SET(id,(select res_id from yc_admin_role where id = '{$roleId}'))
 and type ='m'
 and state=1
order by sort desc";
        $list = DB()->fetchAll($querySQL);
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

//        $navList = include(USR_DIR . 'var/admin_nav.php');
        $this->assign('userInfo', $loginInfo);
        foreach ($this->cstNavPath as $np) {
            $nav_path[] = $np;
        }
        $lastNavPath = array_pop($nav_path);
        $this->assign('nav_path', $nav_path);
        $this->assign('lastNavPath', $lastNavPath);
        //获取菜单数据
        $this->assign('navDataList', $navDataList);
        parent::render($templateFile, $vars);
    }

    protected function setCurrentNav($url)
    {
        $this->assign('navCurrent', $url);
    }

    protected function processPageView($page, $allowView = array(), $key = null)
    {
        $key = null == $key ? $page : $key;
        if ($page && isset($allowView[$key])) {
            if ($allowView[$key] && method_exists($this, $allowView[$key])) {
                call_user_func(array($this, $allowView[$key]));
            }
            $this->render($page);
        } else {
            if (isAjax()) throw new AppException('没有找到您要访问的页面,也许他被黑洞吞噬了！');
            $this->error('没有找到您要访问的页面,也许他被黑洞吞噬了！');
        }
    }

    /**
     * @param $companyCode
     * @param $level
     * @return array(min,max)
     */
    protected function getCompanyMinMaxCode($companyCode, $level)
    {
        if ($level <= 1) {
            return array(0, 99999999);
        }
        if ($level >= 5) {
            return array($companyCode, $companyCode);
        }
        $prefix = substr($companyCode, 0, ($level - 1) * 2);
        return array(
            $prefix . implode('', array_fill(0, 8 - strlen($prefix), '0')),
            $prefix . implode('', array_fill(0, 8 - strlen($prefix), '9'))
        );
    }

    protected function getAdminType()
    {
        return $this->getLoginInfo()->type;
    }

    protected function getAdminRole()
    {
        return $this->getLoginInfo()->role_id;
    }

    /**
     * @param null $roleId
     * @return \Models\RoleResources|null
     */
    public function getRoleData($roleId = null)
    {
        if (!$roleId) $roleId = $this->getAdminRole();
        $roleModel = new \Models\RoleResources();
        $data = $roleModel->find(array('status' => 1, 'id' => $roleId));
        return $data;
    }

    protected function getAdminCompanyId()
    {//
        return $this->getLoginInfo()->coid;
    }

    /**
     * 获取当前登录管理员账号
     * @return mixed
     */
    protected function getLoginAdminId()
    {
        return $this->getLoginInfo()->id;
    }

    /**
     * @return \Models\AdministratorModel|null
     */
    protected function getLoginInfo()
    {
        if (isset($_SESSION['Admin']) && $_SESSION['Admin'] > 0) {
            return $this->getAdminById($_SESSION['Admin']);
        }
        $m = new \Models\AdministratorModel();
        $m->id = 0;
        $m->role_id = 0;
        $m->coid = 0;
        return $m;
    }

    /**
     * @return \Models\AdministratorModel|bool|null
     */
    protected function getLoginFullInfo()
    {
        //判断是否已经登录
        if (isset($_SESSION['Admin']) && $_SESSION['Admin'] > 0) {
            $key = 'm_f_i_' . $_SESSION['Admin'];
            if (Cache::getInstance()->get($key)) {//是否已经缓存
                return Cache::getInstance()->get($key);
            }
            $m = new \Models\AdministratorModel();
            $data = $m->getInfoAndCompany($_SESSION['Admin']);
            Cache::getInstance()->set($key, $data);
            return $data;
        }
        return null;
    }

    protected function getAdminById($id)
    {
        $m = new \Models\AdministratorModel();
        $m->id = $id;
        $key = 'm_' . $m->id;
        if (Cache::getInstance()->get($key)) {//是否已经缓存
            $m->setProperty(Cache::getInstance()->get($key));
            return $m;
        }
        if ($m->find()) {
            Cache::getInstance()->set($key, $m->toArray());
            return $m;
        }
        return null;
    }

    /**
     * @param string $action
     * @param int $state
     * @param array $data
     * @param string $remark
     */
    public function recordAdminLog($action, $state = \Models\LogState::SUCCESS, $data = array(), $remark = '')
    {
        parent::recordLog($action, $this->getLoginAdminId(), $state, $data, $remark);
    }

    public function getRequestId($errorMessage = null, $key = 'id')
    {
        if (null == $errorMessage) $errorMessage = '没有查询到数据(ERROR_MISSING_PARAM_' . strtoupper($key) . ')';
        $id = $this->input()->get($key);
        if ($id != 0 && !$id) {
            ajaxError($errorMessage);
        }
        return $id;
    }
}