<?php
/**
 * File: FrontManage.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-03-02 1:36
 */

namespace controller\Admin;


use Models\Article;
use Models\ArticleCategory;
use Models\DataStatus;
use Models\Links;
use Models\LinkType;
use Models\LogAction;
use Models\LogState;

class FrontManage extends \AdminController
{
    public function init()
    {
        $this->addAdminUrl('front/view.partner', 'showPartner');
        $this->addAdminUrl('front/view.news', 'showNews');
        $this->addAdminUrl('front/view.product', 'showProduct');
        $this->addAdminUrl('front/view.about-us', 'showAbout');

        $this->addAdminUrl('front/article.add', 'showAddArticle');
        $this->addAdminUrl('front/article.edit', 'showEditArticle');
        $this->addAdminUrl('front/article.delete', 'deleteArticle');
        $this->addAdminUrl('front/article.save', 'saveArticle');


        $this->addAdminUrl('front/partner.info', 'infoPartner');
        $this->addAdminUrl('front/partner.delete', 'deletePartner');
        $this->addAdminUrl('front/partner.save', 'savePartner');
    }

    public function showPartner()
    {
        $link_types = LinkType::$ALLType;
        $categoryId = $this->input()->get('category_id');
        if (!$categoryId || !isset($link_types[$categoryId])) {
            $categoryId = LinkType::$Customer;//默认为公司新闻
        }
        $this->pushNavPath(LinkType::Text($categoryId));
        $link = new Links();
        $condition = array(
            'status' => '1',
            'category_id' => $categoryId
        );
        $queryParam = $this->getQueryParam();
        $queryString = http_build_query($queryParam['page']);
        $condition = array_merge($condition, $queryParam['query']);
        list($page, $size, $star) = $this->getPageAndSize(15); //array(page,size,start)
        $linkList = $link->findByCondition($condition, array($star, $size), ['seq DESC','id DESC']);

        $totalCount = $link->count($condition);
        $this->createWindowPageLink('view.partner?category_id='.$categoryId.'&' . $queryString, $totalCount, $size);
        $this->assign('current_id',$categoryId);
        $this->assign('link_types',$link_types);
        $this->assign('link_list',$linkList);
        $this->render('front-partner');
    }

    public function showNews()
    {
        $allNewsType = ArticleCategory::$NewsType;
        $categoryId = $this->input()->get('category_id');
        if (!$categoryId || !isset($allNewsType[$categoryId])) {
            $categoryId = ArticleCategory::$CompanyNews;//默认为公司新闻
        }
        $this->getArticleByCategoryId($categoryId);
        $this->assign('new_types', $allNewsType);
        $this->render('front-news');
    }

    public function showProduct()
    {
        $this->getArticleByCategoryId(ArticleCategory::$Product);
        $this->assign('show_product', true);
        $this->assign('new_types', []);
        $this->render('front-news');
    }
    public function showAbout()
    {
        $this->getArticleByCategoryId(ArticleCategory::$AboutUs);
        $this->assign('show_about_us', true);
        $this->assign('new_types', []);
        $this->render('front-news');
    }


    private function getArticleByCategoryId($categoryId)
    {
        $article = new Article();
        $condition = array(
            'status' => '1',
            'category_id' => $categoryId
        );
        $queryParam = $this->getQueryParam();
        $queryString = http_build_query($queryParam['page']);
        $condition = array_merge($condition, $queryParam['query']);
        list($page, $size, $star) = $this->getPageAndSize(15); //array(page,size,start)
        $articleList = $article->findByCondition($condition, array($star, $size), ['seq DESC','id DESC'],'id,title,alias,create_time,is_edit,is_delete,seq');
        $totalCount = $article->count($condition);
        $url = $categoryId == ArticleCategory::$Product ? 'view.product' :'view.news';
        $this->createWindowPageLink($url.'?' . $queryString, $totalCount, $size);
        $this->assign('current_id', $categoryId);
        $this->assign('article_list', $articleList);
    }

    public function showEditArticle(){
        $id = $this->input()->get('id');
        $article = new Article();
        $article->id = $id;
        if($article->find()){

        }
        $this->assign('article_data',$article->toArray());
        $this->assign('current_cid',$article->category_id);
        $this->pushNavPath('修改'.$article->title);
        if ($article->category_id == 1) {
            $this->setCurrentNav('/admin/front/view.news');
        }elseif ($article->category_id == ArticleCategory::$AboutUs) {
                $this->setCurrentNav('/admin/front/view.about-us');
        } else {
            $this->setCurrentNav('/admin/front/view.product');
        }
        $this->render('front-article');
    }
    public function showAddArticle()
    {
        $categoryId = $this->input()->get('category_id');
        if ($categoryId != ArticleCategory::$Product) {
            $this->setCurrentNav('/admin/front/view.news');
        }elseif ($categoryId == ArticleCategory::$AboutUs) {
            $this->setCurrentNav('/admin/front/view.about-us');
        } else {
            $this->setCurrentNav('/admin/front/view.product');
        }
        $this->pushNavPath('添加'.ArticleCategory::Text($categoryId));
        $this->assign('current_cid',$categoryId);
        $this->render('front-article');
    }

    public function deleteArticle(){
        if(isAjax()){
            $id = $this->getRequestId();

            $article = new Article();
            if(!$article->exists(['id'=>$id])){
                ajaxSuccess();
            }
            $article->update(['id'=>$id],['status'=>DataStatus::DELETE]);
            ajaxSuccess();
        }
        throw new \PermissionException();
    }
    public function saveArticle()
    {
        $article = new Article();
        $article->setProperty($this->input()->post());
        $article->picture = $this->saveUploadImage('article_picture');
        $article->content = $_POST['content'];
        $action = LogAction::INSERT;

        if(!$article->brief){
            $article->brief = getArticleBrief($article->content,300);
        }else if(strlen($article->brief) > 300){
            $article->brief = mb_strimwidth($article->brief,0,300);
        }
        try{
            if($article->id > 0){
                if($article->exists(['alias'=>$article->alias,'status'=>DataStatus::NORMAL,'id[!]'=>$article->id])){
                    ajaxError('别名已经存在了');
                }
                $action = LogAction::UPDATE;
                $article->update();
            }else{
                if($article->exists(['alias'=>$article->alias,'status'=>DataStatus::NORMAL])){
                    ajaxError('别名已经存在了');
                }
                $article->create_time = REQ_TIME;
                $article->insert();
            }
            $this->recordLog($action,$this->getLoginAdminId(),LogState::SUCCESS);
            ajaxSuccess();
        }catch (\Exception $e){
            $this->recordLog($action,$this->getLoginAdminId(),LogState::FAILED);
            ajaxException($e);
        }
    }

    public function infoPartner(){
        $id = $this->input()->get('id');
        $lnk = new Links();
        $lnk->id = $id;
        $lnk->status = 1;
        if(!$lnk->find()){
            ajaxError('没有找到要修改的数据');
        }
        $lnk->image = $this->getConfig('upload')->getConfig('url')->getValue().$lnk->image;
        ajaxSuccess($lnk);
    }
    public function deletePartner(){
        $id = $this->input()->get('id');
        if(!$id) ajaxError('删除的数据不存在(ERROR_MISSING_PARAM)');
        $lnk = new Links();
        try{
            $lnk->update(['id'=>$id],['status'=>0]);
            $this->recordLog(LogAction::DELETE,$this->getLoginAdminId(),LogState::SUCCESS);
        }catch (\Exception $e){
            $this->recordLog(LogAction::DELETE,$this->getLoginAdminId(),LogState::FAILED);
            ajaxException($e);
        }
        ajaxSuccess();
    }
    public function savePartner(){
        $lnk = new Links();
        $lnk->setProperty($this->input()->post());
        $lnk->image = $this->saveUploadImage('link-image');
        $lnk->updated = date('Y-m-d H:i:s',REQ_TIME);
        $action = LogAction::INSERT;
        try{
            if($lnk->id > 0){
                $action = LogAction::UPDATE;
                $lnk->update();
            }else{
                $lnk->insert();
            }
            $this->recordLog($action,$this->getLoginAdminId(),LogState::SUCCESS);
            ajaxSuccess();
        }catch (\Exception $e){
            $this->recordLog($action,$this->getLoginAdminId(),LogState::FAILED);
            ajaxException($e);
        }
    }
}
