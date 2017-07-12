<?php
/**
 * File: UserGroupJoin.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-02-28 13:44
 */

namespace Models;


class UserGroupJoin extends \Model
{
    public $id;
    public $group_id;
    public $member_id;
    public $buy_price;
    public $buy_count;
    public $receiver;
    public $shipping_address;
    public $telephone;
    public $remarks;
    public $cancel_reason;
    public $pay_status;
    public $join_time;
    public $bill_sn;
    public $status;

    public function __construct()
    {
        $this->setPrimaryKey('id');
        $this->setTableName('sz_group_buy_join');
    }

    public function count($condition = null)
    {
        return $this->createQuery()
            ->field('1')->table($this->getTableName() . '(j)')
            ->join([
                '[><]' . $this->getTableName(UserGroupBuy::class) . '(t)' => ['j.group_id' => 't.id'],
                '[><]' . $this->getTableName(MemberModel::class) . '(m)' => ['t.member_id' => 'm.id'],
            ])->where($condition)->count();
    }

    public function getJoinCount($groupById){
        $j_table = $this->getTableName();
        $querySQL = "SELECT
			SUM(buy_count)
		FROM {$j_table} j
		WHERE j.group_id = {$groupById} AND j.`status` = 1";
        return DB()->countBySql($querySQL);
    }

    public function getJoinList($condition, $limit, $orderBy = null)
    {
        return $this->createQuery()
            ->field('t.title,t.main_image,t.create_time,t.group_status,m.name(member_name),j.*')->table($this->getTableName() . '(j)')
            ->join([
                '[><]' . $this->getTableName(UserGroupBuy::class) . '(t)' => ['j.group_id' => 't.id'],
                '[><]' . $this->getTableName(MemberModel::class) . '(m)' => ['t.member_id' => 'm.id'],
            ])->where($condition)->limit($limit)->orderBy($orderBy)->select();
    }

    public function getAcceptTuan($memberId,$start = 0,$size = 15){
        $querySQL = "SELECT
	count(1)
FROM
	sz_group_buy_business a,
	sz_group_buy b
WHERE
	a.tuan_id = b.id
AND a.member_id = {$memberId}
AND b.`status` = 1
AND a.status > 0
";
        $queryColumn = 'a.id,
	a.`status`,
	a.money,
	a.create_time,
	a.remark,
	a.tuan_id,
	b.main_image,
	b.home_image,
	b.goods_id,
	b.title AS tuan_title,
	b.group_status,
	b.create_time as tuan_time';
        $list =  $this->createQuery()->fetchAll(str_replace('count(1)',$queryColumn,$querySQL)."ORDER BY a.id DESC LIMIT {$start},{$size}");
        $count = DB()->countBySql($querySQL);
        return array($list,$count);
    }

    public function getTuanCount($groupId){
        $querySQL = "SELECT
	COUNT(1) AS totalCount,
	SUM(buy_price * buy_count) as totalMoney
FROM
	sz_group_buy_join
WHERE
	group_id = {$groupId}";
        return $this->getQuery()->fetch($querySQL);
    }
}