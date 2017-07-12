<?php
/**
 * File: GroupBuyStatus.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-03-28 16:00
 */

namespace Models;


//团购状态  1.发起中... 2.已接   3.已完结 4.保存中
class GroupBuyStatus
{
    /**
     * @var int 保存中
     */
    static $Saved = 4;
    /**
     * @var int 发起中
     */
    static $Published = 1;
    /**
     * @var int 已接
     */
    static $Accepted = 2;
    /**
     * @var int 已完结
     */
    static $Done = 3;
}