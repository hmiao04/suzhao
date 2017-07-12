<?php
/**
 * File: GoodsModel.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-03-06 23:02
 */

namespace models;

class GoodsStatus{
    static $Normal = 1;
    static $Disable = 2;
    static $Invisible = -2;
    static $Delete = 0;
}
class GoodsModel extends \Model
{
    public $id;
    public $title;
    public $cate_id;
    public $goods_color;
    public $goods_sn;
    public $member_id;
    public $home_image;
    public $goods_image;
    public $price_sale;
    public $price_original;
    public $goods_brief;
    public $goods_content;
    public $created_date;
    public $seq;
    public $status;

    public function __construct()
    {
        $this->setPrimaryKey('id');
        $this->setTableName('sz_goods');
    }

    public function findByCondition($condition = null,$limit = null,$orderBy = null){
        return $this->getQuery()->where($condition)->orderBy($orderBy)->limit($limit)->select();
    }
    public function find($condition = null){
        $data = $this->getQuery()->where($condition)->get();
        if(!$data) return null;
        if($data && is_array($data)) $this->setProperty($data);
        $this->extraData = $data;
        return $this;
    }
    /**
     * @return $this
     * @throws \AppException
     */
    public function getQuery(){
        return DB()->field('g.*,m.name(member_name)')->table($this->getTableName() . '(g)')
            ->join([
                '[><]' . $this->getTableName(MemberModel::class) . '(m)' => ['g.member_id' => 'm.id']
            ]);
    }
}