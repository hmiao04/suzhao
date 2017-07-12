<?php
/**
 * File: ArticleCategory.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-03-02 1:45
 */

namespace Models;


class ArticleCategory
{
    public static $CompanyNews = 1;
    public static $IndustryNews = 2;
    public static $Product = 3;
    public static $AboutUs = 4;
    public static $NewsType = [
        1 => '网站资讯',
        2 => '帮助中心'
    ];
    public static $ALLType = [
        1 => '公司新闻',
        2 => '行业资讯',
        3 => '产品服务',
        4 => '关于我们'
    ];

    public static function Text($id)
    {
        return isset(self::$ALLType[$id]) ? self::$ALLType[$id] : '未知';
    }
}