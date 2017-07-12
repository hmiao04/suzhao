<?php
/**
 * File: GroupBuy.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-02-27 21:24
 */

namespace Models;


use Controller\Admin\GroupBuy;

class UserGroupBuy extends \Model
{

    public $id;
    public $title;
    public $member_id;
    public $goods_id;
    public $home_image;
    public $main_image;
    public $require;
    public $create_time;
    public $end_time;
    public $group_status;
    public $seq;
    public $status;

    public function __construct()
    {
        $this->setPrimaryKey('id');
        $this->setTableName('sz_group_buy');
    }

    public function findByCondition($condition = null,$limit = null,$orderBy = null){
        return $this->getQuery()->where($condition)->orderBy($orderBy)->limit($limit)->select();
    }
    public function find($condition = null){
        $condition = null == $condition ? $this->getNotNullArray():$condition;
        $data = $this->getQuery()->where($condition)->get();
        if(!$data) return null;
        if($data && is_array($data)) $this->setProperty($data);
        $this->extraData = $data;
        return $this;
    }

    public function getRecommend($count  = 15){
        $data = $this->getQuery()->where([
            't.status' => DataStatus::NORMAL
        ])->limit($count)->orderBy('t.seq DESC')->select();
        return $data;
    }

    public function getNewList($count  = 10){
        $data = $this->getQuery()->where([
            't.status' => DataStatus::NORMAL
        ])->limit($count)->orderBy('t.id DESC')->select();
        return $data;
    }

    /**
     * @return \DBCore
     * @throws \AppException
     */
    public function getQuery(){
        return DB()->field('t.*,m.name(member_name),g.title(goods_name),g.price_sale(goods_price)')->table($this->getTableName() . '(t)')
            ->join([
                '[><]' . $this->getTableName(MemberModel::class) . '(m)' => ['t.member_id' => 'm.id'],
                '[><]' . $this->getTableName(GoodsModel::class) . '(g)' => ['t.goods_id' => 'g.id']
            ]);
    }

    public function getTopList($count = 2){
        $table = $this->getTableName();
        $j_table = $this->getTableName(UserGroupJoin::class);
        $querySQL = "SELECT
	*,(
		SELECT
			count(1)
		FROM {$j_table} j
		WHERE j.group_id = g.id AND j.`status` = 1
	) AS join_member_count,(
		SELECT
			SUM(buy_count)
		FROM {$j_table} j
		WHERE j.group_id = g.id AND j.`status` = 1
	) AS member_buy_count
FROM {$table} g
WHERE
	status = 1 and group_status = 1 and home_image <> '' and home_image is not NULL
ORDER BY seq DESC,id DESC
LIMIT 0,{$count}";
        return $this->createQuery()->fetchAll($querySQL);
    }
}