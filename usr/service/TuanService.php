<?php
/**
 * File: TuanService.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-04-11 16:14
 */

namespace Service;


use Models\DataStatus;
use models\MemberType;
use Models\TuanBusiness;

class TuanService
{

    /**
     * @param \Models\MemberModel $memberInfo
     * @param int $tuanId
     * @return \Models\TuanBusiness
     */
    public function getMemberBidding($memberInfo, $tuanId)
    {
        if ($memberInfo == null || $memberInfo->id < 1) return false;
        if ($memberInfo->type_id != MemberType::$Company) return false;
        $tb = new TuanBusiness();
        $tb->tuan_id = $tuanId;
        $tb->member_id = $memberInfo->id;
        $tb->status = DataStatus::NORMAL;
        return $tb->find();
    }

    public static function AvailableCount()
    {
        $condition = ['status' => DataStatus::NORMAL];
        $tb = new TuanBusiness();
        return $tb->count($condition);
    }
}