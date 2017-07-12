<?php
/**
 * Created by PhpStorm.
 * User: yancheng<cheng@love.xiaoyan.me>
 * Date: 17/4/2
 * Time: 下午10:01
 */

namespace controller\FrontPage;

use Lib\WebController;
use Models\CommonCategory;
use Models\DataStatus;
use Models\PictureModel;
use Models\SharePicture;
use service\CommonCategoryService;

class PictureController extends WebController
{

    function  _construct()
    {

        echo "we are in PictureController";
    }
    public function init()
    {

        $this->addRoute('/picture', 'showPictureIndex');
        $this->addRoute('/picture/', 'showPictureIndex');
        $this->addRoute('/picture/list.html', 'showPictureList');
        $this->addRoute('/picture/save.html', 'savePicture');
	    $this->addRoute('/picture/del_pic.html', 'delPicture');

        $this->addRoute('/picture/upload_pic.html', 'showPictureIndex');
        $this->addRoute('/picture/show-{id}.html', 'showPicture');
    }

    public function before()
    {
        $this->setControllerRenderPath('front_page/picture');
    }

    public function showPictureIndex()
    {
        $cate_id = 0;
        $this->assign('current_id', $cate_id);
        $pic = new SharePicture();
        list($page, $size, $start) = $this->getPageAndSize(30, null, 30); //array(page,size,start)
        $condition = ['status' => DataStatus::NORMAL];
        if ($cate_id > 0) {
            $condition['cate_id'] = $cate_id;
        }
        $list = $pic->findByCondition($condition, [$start, $size], 'seq DESC');
        $totalCount = $pic->count($condition);
        $this->createWindowPageLink('list.html?cate_id=' . $cate_id, $totalCount, $size);
        $this->assign('picture_list', $list);
        $this->render('index');
    }

    public function showPictureList()
    {
        $cate = new CommonCategory();
        $cateList = $cate->findByCondition(['type' => 'picture', 'id[>]' => 1]);
        $cateList = array_merge([array('id' => 0, 'cate_name' => '所有图片')], $cateList);
        $new_cateList = array_key_values($cateList, 'id');
        $this->assign('cateList', $cateList);
        $cate_id = $this->input()->request('cate_id');
        if (!$cate_id || !in_array($cate_id, $new_cateList)) {
            $cate_id = $cateList[0]['id'];
        }
        $this->assign('current_id', $cate_id);
        $pic = new SharePicture();
        list($page, $size, $start) = $this->getPageAndSize(20, null, 20); //array(page,size,start)
        $condition = ['status' => DataStatus::NORMAL];
        if ($cate_id > 0) {
            $condition['cate_id'] = $cate_id;
        }
        $tag = $this->input()->request('tag');
        list($list, $totalCount) = $pic->queryListByCateAndTag($cate_id, $tag, $start, $size);
        $this->createWindowPageLink('list.html?cate_id=' . $cate_id, $totalCount, $size);

        foreach ($list as &$item) {
            $item['tag_list'] = isset($item['picture_tag']) && $item['picture_tag'] ? explode(',', $item['picture_tag']) : [];
        }
        

        $this->assign('picture_list', $list);
        $this->render('list');
    }

    public function savePicture()
    {
echo ('enter save pic');
        $this->needAjax();
        $this->checkMemberLogin();
        $this->checkDataNull(array(
            array('picture_tag', 2, '请填写图片标签'),
            array('data-image-value', 3, '请设置分享展示图片'),
            array('picture_brief', 4, '请输入图片描述'),
            array('picture_content', 5, '请输入详情内容'),
        ), 1, null, 1);
        $picture = new SharePicture();
        $picture->setProperty($this->input()->post());

        $picture->main_image = $picture->image_list[0];
        $picture->image_list = json_encode($picture->image_list);
        $picture->picture_content = $_POST['picture_content'];
        try {
            if ($picture->id > 0) {
                $picture->update();
            } else {
                $id = $picture->insert()->lastInsertId;
                if ($id <= 0) {
                    ajaxError('保存分享数据失败');
                }
            }
            ajaxSuccess();
        } catch (\Exception $e) {
            ajaxException($e);
        }
    }

    public function showPicture($id)
    {
        if(ALLOW_VIEW_NO_LOGIN == false){//不允许未登录查看
            $this->checkMemberLogin();
        }
        $id = $this->decode($id);
        if (!$id) {
            throw new \NotFoundException('浏览的图片不存在', MODULE_PICTURE_ID);
        }
        $pic = new SharePicture();
        if (!$pic->findByPrimary($id)) {
            throw new \NotFoundException('浏览的图片不存在', MODULE_PICTURE_ID);
        }
        $pic->image_list = json_decode($pic->image_list, 1);
        array_shift($pic->image_list);
        $this->assign('picture', $pic);
        $cate = new CommonCategoryService();
        $this->assign('category', $cate->getCateById($pic->cate_id));

        $this->render('show');
    }
   public function delPicture()
    {
        $pic_id = $this->input()->request('picture_id');
        if (!$pic_id || !preg_match('/^\d+$/', $pic_id)) {
            throw new \AppException('删除图不存在[ERROR_PARAM_CATEGORY_ID]');
        }
        $cate = new CommonCategory();
        if (!$cate->findByPrimary($pic_id)) {
            throw new \AppException('删除图不存在[ERROR_CATEGORY_NOT_FOUND]');
        }
        $this->changeStatus(['status' => DataStatus::DELETE], $pic_id);
       // ajaxError('删除图片失败');
    }



    private function changeStatus($status, $pic_id)
    {
      //  $pid = $this->input()->get('id');

        $pid = $pic_id;

        if (!$pid || preg_match('/^[\d]+$/', $pid) == false) {
            throw new \AppException('您浏览的图不存在');
        }
        $p = new SharePicture();
        $condition = ['id' => $pid];

        if (!$p->find($condition)) {
            throw new \AppException('您浏览的图不存在');
        }
//        if ($p->member_id != $this->getLoginMemberId()) {
//            throw new \PermissionException();
//        }
        $p->update(['id' => $pid], ['status' => $status]);
//        ajaxSuccess();
    }
}
