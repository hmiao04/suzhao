<?php
/**
 * Created by PhpStorm.
 * User: yancheng<cheng@love.xiaoyan.me>
 * Date: 16/10/24
 * Time: 上午10:18
 */

namespace Controller\Admin;


use Models\AdminLog;
use Models\AdminResources;
use Models\LogAction;
use Models\LogState;
use Models\ResourceType;
use Models\RoleResources;

class System extends \AdminController
{

    public function init()
    {
        $this->addAdminUrl('system/view.{page}','pageView');
        $this->addAdminUrl('system/log.info','logInfo');
        $this->addAdminUrl('api/resource.detail','infoResource');
        $this->addAdminUrl('api/resource.save','saveResource');
        $this->addAdminUrl('api/role.info.save','saveRoleData');
        $this->addAdminUrl('api/role.resource.info','infoRoleRes');
        $this->addAdminUrl('api/role.resource.save','saveRoleRes');
    }

    /**
     * @param $page
     */
    public function pageView($page){
        $allowView = array(
            'system-log'=>'getSystemLog',
            'system-resource'=>'getResources',
            'role-list'=>'getRoleList',
        );
        parent::processPageView($page,$allowView);
    }

    protected function getSystemLog()
    {
        $adminLog = new AdminLog();
        $condition = array();
        $queryParam = $this->getQueryParam();
        $queryString = http_build_query($queryParam['page']);
        $queryCondition = array();
        $role = $this->getRoleData();
        if($role->alias != 'root'){
            $queryCondition['admin_id'] = $this->getLoginAdminId();
        }
        foreach($queryParam['query'] as $k=>$c){
            switch($k){
                case 'datetime_start':
                    $queryCondition['log_time[>=]'] = strtotime($c);
                    break;
                case 'datetime_end':
                    $queryCondition['log_time[<=]'] = strtotime($c);
                    break;
                case 'admin_account':
                    $queryCondition['account[~]'] = $c;
                    break;
                default:
                    $queryCondition[$k]=$c;
                    break;
            }
        }
        $condition = array_merge($condition,$queryCondition);
        list($page,$size,$star) = $this->getPageAndSize(13,null,15);//array(page,size,start)
        $list = $adminLog->getLogByCondition($condition,array($star,$size),'log_time DESC');
        foreach($list as &$log){
            $log['log_type'] = LogAction::format($log['log_type']);
        }
        $totalCount = $adminLog->getCountByCondition($condition);
        $this->createWindowPageLink('view.system-log?'.$queryString,$totalCount,$size);
        $this->assign('logTypeList',LogAction::AllAction());
        $this->assign('logList',$list);

        if(isAjax()){
            $this->render('system-log-item');exit;
        }
    }

    public function logInfo(){
        $logTime = $this->input()->request('log_time');
        $adminId = $this->input()->request('admin_id');
        if(!$logTime || !$adminId){
            ajaxResponse(-1,'参数错误');
        }
        $resModel = new AdminLog();
        $resModel->log_time = $logTime;
        $resModel->admin_id = $adminId;
        $data = $resModel->getLogInfoByCondition();
        if(!$data) ajaxResponse(2,'没有找到要获取的数据');
        ajaxSuccess($data);
    }
    public function infoResource()
    {
        $resId = $this->input()->request('resId');
        if(!$resId){
            ajaxResponse(-1,'参数错误');
        }
        $resModel = new AdminResources();
        $resModel->id = $resId;
        if($resModel->find() == null) ajaxResponse(2,'没有找到要获取的数据');
        ajaxSuccess($resModel);
    }

    protected function getResources()
    {
        $parentId = $this->input()->get('pid');
        $res = new AdminResources();
        if(!is_numeric($parentId) || $parentId < 1) $parentId = 0;
        else{
            $parentId = intval($parentId);
            $res->id = $parentId;
            if($res->find()){
                $this->pushNavPath($res->res_name.'的下级资源');
            }
        }
        list($page,$size,$star) = $this->getPageAndSize(15);//array(page,size,start)
        $list = $res->findByCondition(array('parent_id'=>$parentId),array($star,$size));
        foreach($list as &$l){
            $l['type'] = ResourceType::format($l['type']);
        }
        $totalCount = $res->count(array('parent_id'=>$parentId));
        $this->createWindowPageLink('view.system-resource',$totalCount,$size);
        $this->assign('parentId',$parentId);
        $this->assign('resList',$list);
        $this->assign('typeList',ResourceType::getAllTypes());
    }

    public function saveResource(){
        //保存资源数据
        $resModel = new AdminResources();
        $checkModel = new AdminResources();
        $this->checkDataNull(array(
            array('res_name',101,'请填写资源名称'),
            array('res_id',102,'请填写资源标识符'),
            array('res_url',103,'请填写资源标内容')
        ));
        $resModel->setProperty($this->input()->post());
        $checkModel->res_id = $resModel->res_id;
        if($resModel->id < 1){ // 新增
            if($checkModel->exists()){
                ajaxResponse(104,'要保存的资源标识符已经存在了');
            }
            $resModel->create_time = REQ_TIME;
            if($resModel->insert()){
                $this->recordAdminLog(LogAction::INSERT,LogState::SUCCESS,$resModel->toArray());
                ajaxSuccess();
            }
            $this->recordAdminLog(LogAction::INSERT,LogState::FAILED,$resModel->toArray());
        }else{
            if($checkModel->find() && $checkModel->id != $resModel->id){
                ajaxResponse(105,'要保存的资源标识符已经存在了');
            }
            if($resModel->update()){
                $this->recordAdminLog(LogAction::UPDATE,LogState::SUCCESS,$resModel->toArray());
                ajaxSuccess();
            }
            $this->recordAdminLog(LogAction::UPDATE,LogState::FAILED,$resModel->toArray());
        }
        ajaxResponse(1,'保存数据失败!',$resModel->toArray());
    }

    protected function getRoleList(){
        $roleModel = new RoleResources();
        list($page,$size,$star) = $this->getPageAndSize(13);//array(page,size,start)
        $list = $roleModel->findByCondition(array(),array($star,$size));
        foreach($list as &$log){

        }
        $totalCount = $roleModel->count();
        $this->createWindowPageLink('view.role-list',$totalCount,$size);
        $this->assign('logList',$list);
    }

    public function saveRoleData(){
        //保存资源数据
        $resModel = new RoleResources();
        $checkModel = new RoleResources();
        $this->checkDataNull(array(
            array('role_name',101,'请填写角色名称')
        ));
        $resModel->setProperty($this->input()->post());
        $checkModel->role_name = $resModel->role_name;
        if($resModel->id < 1){ // 新增
            if($checkModel->exists()){
                ajaxResponse(104,'要保存的角色名称已经存在了');
            }
            if($resModel->insert()){
                $this->recordAdminLog(LogAction::INSERT,LogState::SUCCESS,$resModel->toArray());
                ajaxSuccess();
            }
            $this->recordAdminLog(LogAction::INSERT,LogState::FAILED,$resModel->toArray());
        }else{
            if($checkModel->find() && $checkModel->role_name != $resModel->role_name){
                ajaxResponse(105,'要保存的角色名称已经存在了');
            }
            if($resModel->update()){
                $this->recordAdminLog(LogAction::UPDATE,LogState::SUCCESS,$resModel->toArray());
                ajaxSuccess();
            }
            $this->recordAdminLog(LogAction::UPDATE,LogState::FAILED,$resModel->toArray());
        }
        ajaxResponse(1,'保存数据失败!',$resModel->toArray());
    }

    public function infoRoleRes(){
        $rid = $this->input()->get('id');
        if(!$rid){ajaxResponse(-1,'没有找到角色数据');}
        $roleModel = new RoleResources();
        $roleModel->id = $rid;
        if(!$roleModel->find()){ajaxResponse(-2,'没有找到角色数据');}
        $groupRule = $roleModel->res_id ? explode(',',$roleModel->res_id): array();
        $resModel = new AdminResources();
        $allRes = $resModel->findByCondition(array('state[!]'=>'-1'));
        foreach($allRes as $i => $r){
            $allRes[$i]['checked'] = in_array($r['id'],$groupRule) ? true : false;
        }
        $rules = buildTree($allRes,'id','parent_id');
        $this->render('role-res',array(
            'resList' => $rules,
            'role' => $roleModel->toArray()
        ));
    }
    public function saveRoleRes(){
        $rid = $this->input()->post('id');
        if(!$rid){ajaxResponse(-1,'没有找到角色数据');}
        $roleModel = new RoleResources();
        $roleModel->id = $rid;
        if(!$roleModel->find()){ajaxResponse(-1,'没有找到角色数据');}
        $rules = $this->input()->post('rules');
        try{
            DB()->update($roleModel->getTableName(),array('res_id'=>implode(',',$rules)),array('id'=>$rid));
            ajaxSuccess();
        }catch (DBException $e){
            ajaxResponse(1,'更新数据失败!');
        }
    }
}