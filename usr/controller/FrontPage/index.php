<?php
/**
 * File: Tuan.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-02-27 19:46
 */

namespace Controller\FrontPage;


use Lib\WebController;
use Models\Article;
use Models\CommonCategory;
use Models\DataStatus;
use Models\FileResource;
use Models\PictureModel;
use Models\Task;
use Models\UserGroupBuy;
use service\CompanyService;
use Service\GoodsService;
use Service\TaskService;

class Index extends WebController
{
    public function before()
    {
    }

    public function init()
    {
		$this->addRoute('/', 'showIndex');
		$this->addRoute('/index.picture', 'showSharePicture');
		$this->addRoute('/page/view.html', 'showArticleDetail');
		$this->addRoute('/init_pic', function(){
            $file = new FileResource();
            $fs = $file->findByCondition(['member_id'=>3]);
            $pic = new PictureModel();
            foreach($fs as $f){
                $pic->file_id = $f['file_id'];
                $pic->member_id = 3;
                $pic->category_id = 1;
                $pic->upload_time = REQ_TIME;
                $pic->insert();
            }
            echo 'success';
        });
    }
    public function showArticleDetail(){
        $alias = $this->input()->get('id');
        $article = new Article();
        $article->alias = $alias;
        $article->status = DataStatus::NORMAL;
        $found = false;
        if($article->find()){
            $articleList = $article->findByCondition(['status'=>1],[0,10],'seq DESC');
            $this->assign('article',$article);
            $this->assign('articleList',$articleList);
            $found = true;
        }
        $this->render('article-detail',['found'=>$found]);
    }

    public function showIndex()
    {
        $tuan = new UserGroupBuy();
        $this->assign('tuan_list',$tuan->getTopList());

        $cate = new CommonCategory();
        $cateList = $cate->findByCondition(['type'=>'picture','state'=>1]);
        $this->assign('cateList',$cateList);
        $pic = new PictureModel();
        $list = $pic->getTop(8,$cateList[0]['id']);
        $this->assign('picture_list',$list);
        $sz = new TaskService();

        $taskList = $sz->getTopBySeq(8);
        $this->assign('taskList',$taskList);

        $company = new CompanyService();
        $companyList = $company->getTopBySeq(8);
        $this->assign('companyList',$companyList);

        $gs = new GoodsService();
        $g_list = $gs->getTopBySeq(8);
        $this->assign('goodsList',$g_list);

        $this->render('index');
    }
    public function showSharePicture(){
        $cateId = $this->input()->get('cate_id');
        if (!$cateId || preg_match('/^[\d]+$/', $cateId) == false) {
            throw new \AppException('您浏览的数据不存在');
        }
        $pic = new PictureModel();
        $list = $pic->getTop(8,$cateId);
        $this->assign('picture_list',$list);
        if(isAjax()){
            $this->render('part/index-share-picture');exit;
        }
    }
}