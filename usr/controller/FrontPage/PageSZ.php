<?php
/**
 * File: SZfast.php:newsys
 * User: xiaoyan f@yanyunfeng.com
 * Date: 2017/1/4
 * Time: 22:33
 * @Description
 */

namespace Controller\FrontPage;

use Models\SZfast;
use Lib\WebController;
use models\TaskStatus;

class PageSZ extends WebController
{
    /**
     * @param string $url
     * @param callback $processMethod
     */
    private function addRouteUrl($url, $processMethod)
    {
        $this->addRoute('/member' . $url, $processMethod);
    }


    public function init()
    {
        $this->addRouteUrl('/mysznew.html', 'MySZAdd');    //添加
        $this->addRouteUrl('/myszedit.html', 'MySZEdit');//重新发布

        $this->addRouteUrl('/mysz.html', 'MySZListView');//我的速找列表展示
        $this->addRouteUrl('/showSZ.html', 'SZViewById');//所有速找列表展示

        $this->addRouteUrl('/allsz.html', 'AllSZListView');//所有速找列表展示
        //$this->addRouteUrl('/getsz.html','GetSZById');//接速找页说明页

        $this->addRoute('/api/sz.delete', "PostSZDelect");//禁用
        $this->addRoute('/api/sz.down', "PostSZDown");//接速找

        $this->addRoute('/api/sz.save', "PostSZSave");    //保存数据

    }

    public function before()
    {
        $this->setControllerRenderPath('front_page/sz');
    }

    public function MySZAdd()
    {
        $this->checkMemberLogin();
        $this->render('sz-post', array("acviteIndex" => 4));
    }

    public function MySZListView()
    {
        $sz = new SZfast();
        $mid = $this->getUserId();
        //if($mid)
        //print_r($sz->findByCondition(array("member_id"=>$mid)));
        $this->render('sz-list', array("acviteIndex" => 5, "logList" => array("LogId" => "a")));
    }

    public function SZViewById()
    {
        $sz = new SZfast();
        $member_id = $this->getUserId();
        $sz->id = $this->input()->get('id');

        //如果能找到且是自己的或是(发布状态且有查看权限)
        if (($sz->find() && $sz->member_id == $member_id) || ($sz->status == 1 && true))
            print_r($sz->toArray());
        else
            ajaxResponse(-1, '没有这篇文章或你没有查看它的权限!');

    }


    public function AllSZListView()
    {
        //$this->render('sz-list',array("logList"=>array("LogId"=>"a")));
        $sz = new SZfast();
        print_r($sz->findByCondition(array("status" => 1)));


    }

    public function GetSZById()
    {

        //$this->render();
    }

    public function PostSZDelect()
    {
        $sz = new SZfast();
        $sz->member_id = $this->getUserId();
        $sz->id = $this->input()->get('id');

        if (!$sz->isMyPage())
            ajaxResponse(-1, '没有这篇文章或你没有编辑它的权限!');

        $sz->status = 0;
        $sz->update();
        ajaxSuccess();
    }

    public function PostSZDown()
    {
        $sz = new SZfast();
        $sz->id = $this->input()->get('id');
        if ($sz->isMyPage($this->getUserId()))
            ajaxResponse(-1, '不能接自己的速找!');
        if ($sz->status != 1)
            ajaxResponse(-1, '这个速找现在不能被接!');
        $sz->status = 2;
        $sz->update();
        ajaxSuccess();
    }

    public function MySZEdit()
    {
        $this->checkMemberLogin();
        $sz = new SZfast();
        $sz->member_id = $this->getUserId();
        $sz->id = intval($this->input()->get("id"));

        if (!$sz->isMyPage()) {
            $this->render('sz-post', array("acviteIndex" => 4, "error" => "未找到要编辑的数据或你没有编辑它的权限!"));
        } elseif ($sz->status != TaskStatus::$Done && $sz->status != TaskStatus::$Cancel) {
            $this->render('sz-post', array("acviteIndex" => 4, "error" => "本文现在状态不能被编辑,请先将它结束!"));
        } else {
            $this->assign('save_action', $this->input()->get('action'));
            $this->render('sz-post', array("acviteIndex" => 4, "pageinfo" => $sz));
        }
    }

    public function PostSZSave()
    {
        $this->checkMemberLogin();
        $postData = array(
            'main_image' => $this->saveUploadImage('image'),    //主图
            'member_id' => $this->getUserId()        //当前用户ID
            //'created_date'=>REQ_TIME
        );
        $this->getPostData(
            $postData,
            array(
                "id" => "id",
                'title' => 'find_title',        //标题
                'date' => 'wish_finish_time',    //在以下时间内完成
                'price' => 'paid_price',        //完成酬劳
//				'brief'=>'find_brief',		//要求
                'content' => 'find_content',    //详情内容
                'main_image' => 'main_image',    //详情内容
                'home_image' => 'home_image',    //详情内容
                //'seq'=>'seq',				//排序
                'status' => 'status'
            )
        );
        $sz = new SZfast();
        if (!$postData["find_title"])
            ajaxResponse(-1, '不能没有标题!');
        if (!$postData["wish_finish_time"] || !is_numeric($postData["wish_finish_time"]))
            ajaxResponse(-1, '请以数字(天)来表示所需用时间!');
        if (!$postData["paid_price"] || !is_numeric($postData["paid_price"]))
            ajaxResponse(-1, '请一个数字(人民币:元)来表示完成后可得到的酬劳!');
//		if(!$postData["find_brief"])
//			ajaxResponse(-1, '请给出任务要求!');
//		ajaxResponse(-1, '请给出任务要求!',$postData);

        if ($this->input()->post('save_action') == 're_save') {
            $postData["id"] = 0;
        }
        try{
            if (isset($postData["id"]) && $postData["id"] > 0) {
                if (!$sz->isMyPage($postData["member_id"], $postData["id"]) || $sz->status != 0){
                    ajaxError('进行中的速找不能被编辑(ERROR_STATUS)');
                }
                $sz->setProperty($postData);
                $sz->update();
            } else {
                $sz->setProperty($postData);
                $sz->created_date = date("Y-m-d H:s", REQ_TIME);
                $sz->insert();
            }
            ajaxSuccess();
        }catch (\Exception $e){
            ajaxException($e);
        }
        ajaxError('保存速找数据失败');
    }

    public function uploadAvatar()
    {
        $targetFolder = $this->getConfig('upload')->getConfig('path')->getValue(); // Relative to the root
        $targetUrl = $this->getConfig('upload')->getConfig('url')->getValue(); // Relative to the root

        $verifyToken = md5($this->input()->post('timestamp') . '_unique_salt');
        if ($this->input()->post('token') == $verifyToken) {
            $fileName = $this->saveUploadImage('Filedata');
            if ($fileName) {
                ajaxSuccess(URL() . '/resource/' . $fileName);
            }
            ajaxError('上传失败');
        }
        ajaxError('您上传的图片非法');
    }


}