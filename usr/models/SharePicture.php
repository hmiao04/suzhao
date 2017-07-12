<?php
/**
 * File: SharePicture.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-06-01 11:06
 */

namespace Models;


class SharePicture extends \Model
{
    public $id;
    public $picture_title;
    public $picture_tag;
    public $cate_id;
    public $member_id;
    public $main_image;
    public $image_list;
    public $picture_brief;
    public $picture_content;
    public $created_date;
    public $seq;
    public $status;

    public function __construct()
    {
        $this->setPrimaryKey('id');
        $this->setTableName('sz_share_picture');
    }

    public function queryListByCateAndTag($cate_id = 0, $tag = null, $start = 0, $size = 10)
    {
        $condition = 'where 1 = 1 ';
        if ($cate_id > 0) {
            $condition .= ' and cate_id = 2';
        }
        if ($tag) {
            $condition .= ' and find_in_set(\'' . $tag . '\',picture_tag)';
        }
        $columns = 'id,
	picture_title,
	picture_tag,
	cate_id,
	member_id,
	main_image,
	created_date,
	seq,
	`status`';
        $sql = "SELECT
	count(1)
from sz_share_picture
  {$condition}";
        $db = DB();
        $list = $db->fetchAll(str_replace('count(1)', $columns, $sql) . " limit {$start},{$size}", 'id');

        $count =$db->countBySql($sql);
        return array($list,$count);
    }
}