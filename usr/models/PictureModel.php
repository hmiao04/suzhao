<?php
/**
 * File: PictureModel.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-03-30 22:18
 */

namespace Models;


class PictureModel extends \Model
{
    public $id;
    public $file_id;
    public $member_id;
    public $upload_time;
    public $category_id;
    public $like_count;
    public $seq;
    public $status;

    public function __construct()
    {
        $this->setPrimaryKey('id');
        $this->setTableName('sz_pictures');
    }

    public function getTop($count = 8, $cate_id = null)
    {
        $condition = [];
        if ($cate_id != null) $condition['category_id'] = $cate_id;
        return DB()->field('p.*,f.file_name')->table($this->getTableName() . '(p)')
            ->join([
                '[><]' . MemberModel::TableName(FileResource::class) . '(f)' => ['p.file_id' => 'f.file_id']
            ])->where($condition)->orderBy('seq DESC')->limit([0, $count])->select();
    }

    public function getListByCateId($cate_id,$member_id, $limit)
    {
        $condition = [];
        if($cate_id > 0){
            $condition['category_id'] = $cate_id;
        }else{
            $condition['category_id[>]'] = 1;
        }
        if($member_id > 0)  $condition['p.member_id'] = $member_id;
        $condition['p.status'] = DataStatus::NORMAL;
        $list = DB()->field('p.*,f.file_name,f.file_time')->table($this->getTableName() . '(p)')
            ->join([
                '[><]' . $this->getTableName(FileResource::class) . '(f)' => ['p.file_id' => 'f.file_id']
            ])->where($condition)->orderBy(['seq DESC','id DESC'])->limit($limit)->select();
        if($member_id > 0){
            unset($condition['p.member_id']);
            $condition['member_id'] = $member_id;
        }
        unset($condition['p.status']);
        $condition['status'] = DataStatus::NORMAL;
        return array(
            $list,$this->count($condition)
        );
    }
}