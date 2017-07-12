<?php
/**
 * File: Article.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-03-02 2:47
 */

namespace Models;


class Article extends \Model
{
    public $id;
    public $alias;
    public $title;
    public $picture;
    public $category_id;
    public $category_name;
    public $is_edit;
    public $is_delete;
    public $brief;
    public $content;
    public $create_time;
    public $seq;
    public $status;

    public function __construct()
    {
        $this->setPrimaryKey('id');
        $this->setTableName('xkl_article');
    }

}