<?php
/**
 * File: TaskStatus.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-04-13 21:41
 */

namespace models;


//状态(1:未接 2:已接 3:取消 0:删除 4:完成)
class TaskStatus
{
    public static $NotStart = 1;
    public static $Started = 2;
    public static $Cancel = 3;
    public static $Done = 4;

}