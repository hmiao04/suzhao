<?php

/**
 * File: AdminMain.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2016-09-07 21:02
 */
namespace Controller\Admin;

use Service\GoodsService;
use Service\MemberService;
use Service\TaskService;
use Service\TuanService;

class AdminMain extends \AdminController
{
    public function init()
    {
        $this->addRoute($this->adminBaseUrl, 'adminIndex');
        $this->addAdminUrl('', 'adminIndex');
    }

    public function adminIndex()
    {
        if(isset($_GET['server'])){
            phpinfo();die();
        }
        $server_soft = explode(' ', $_SERVER['SERVER_SOFTWARE']);
        $this->assign('member_count',MemberService::AvailableMemberCount());
        $this->assign('task_count',TaskService::AvailableCount());
        $this->assign('tuan_count',TuanService::AvailableCount());
        $this->assign('goods_count',GoodsService::AvailableCount());

        $software_info = array(
            'server_soft' => $server_soft[0],
            'php_version' => phpversion(),
            'db_info' => DB()->info(),
            'app_version' => YCPF_VER,
            'max_upload' => ini_get('post_max_size'),
            'os' => PHP_OS
        );
        $this->render('index', $software_info);
    }

}