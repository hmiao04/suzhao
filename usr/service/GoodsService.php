<?php
/**
 * File: GoodsService.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-04-18 10:29
 */

namespace Service;


use Models\DataStatus;
use models\GoodsModel;

class GoodsService
{
    /**
     * @var \models\MemberCompany
     */
    private $goodsModel;

    public function __construct()
    {
        $this->goodsModel = new GoodsModel();
    }

    public function getAvailableCount()
    {
        $condition = ['g.status' => DataStatus::NORMAL];
        return $this->goodsModel->count($condition);
    }

    public static function AvailableCount()
    {
        $t = new GoodsService();
        return $t->getAvailableCount();
    }

    public function parseGoodsData(&$goodsList)
    {

        foreach ($goodsList as &$item) {
            $item['goods_image'] = $item['goods_image'] ? json_decode($item['goods_image'], 1) : [];
            $item['main_image'] = count($item['goods_image']) > 0 ? $item['goods_image'][0] : 'default-image.jpg';
        }
    }

    public function getTopBySeq($count)
    {
        $dataList = $this->goodsModel->findByCondition(['g.status' => DataStatus::NORMAL], [0, $count], ['seq DESC']);
        $this->parseGoodsData($dataList);
        return $dataList;
    }

    public function getList($condition = [], $start = 0, $orderBy = 'id DESC', $size = 30)
    {
        $condition = array_merge(
            ['g.status' => DataStatus::NORMAL], $condition
        );
        $dataList = $this->goodsModel->findByCondition($condition, [$start, $size], $orderBy);
        $this->parseGoodsData($dataList);
        return array($dataList, $this->goodsModel->count($condition));
    }

    public function searchByCateAndColor($cate_id = 0, $color = '', $start = 0, $orderBy = 'id DESC', $size = 30)
    {
        $where = ' where  g.STATUS = 1';

            if ($cate_id > 0) {
                $where .= ' and cate_id = ' . $cate_id;
            }
            if ($color && strtolower($color) != 'all') {
                $where .= " and FIND_IN_SET('{$color}',goods_color)";
            }
            $sql = "SELECT
	count(1)
FROM sz_goods AS g
INNER JOIN yc_member AS m ON g.member_id = m.id
{$where} ";
            $querySQL = str_replace('count(1)', 'g.*, m. NAME AS member_name', $sql) . " ORDER BY {$orderBy} LIMIT {$start},{$size}";
            $dataList = DB()->fetchAll($querySQL);
            $this->parseGoodsData($dataList);
            return array($dataList, DB()->countBySql($sql));


            }
    }