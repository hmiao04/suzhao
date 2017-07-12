<?php
/**
 * File: GoodsColor.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-05-06 13:10
 */

namespace Models;
class Color
{
    public $colorName;
    public $colorValue;

    public function __construct($value, $name = '')
    {
        $this->colorValue = $value;
        $this->colorName = $name;
    }

    public function equal(Color $c)
    {
        return strtolower($this->colorValue) == strtolower($c->colorValue);
    }
}

class GoodsColor
{
    const RED = 'red';
    const BLUE = 'blue';
    const GREEN = 'green';
    const WHITE = 'white';
    const BLACK = 'black';
    const PINK = 'pink';
    const GRAY = 'gray';

    public static $AllColor = [
        self::RED => ['value' => 'red', 'name' => '红色'],
        self::BLUE => ['value' => 'blue', 'name' => '蓝色'],
        self::GREEN => ['value' => 'green', 'name' => '绿色'],
        self::WHITE => ['value' => 'white', 'name' => '白色'],
        self::BLACK => ['value' => 'black', 'name' => '黑色'],
        self::PINK => ['value' => 'pink', 'name' => '粉红色'],
        self::GRAY => ['value' => 'gray', 'name' => '灰色'],
    ];

    public static function ColorName($color)
    {
        if (!$color) {
            return '';
        }
        if (is_array($color)) {
            $colors = [];
            $name = '';
            foreach ($color as $c) {
                $name = isset(self::$AllColor[$c]) ? self::$AllColor[$c]['name'] : '';
                if ($name) {
                    $colors[] = $name;
                }
            }
            return $colors;
        }
        return isset(self::$AllColor[$color]) ? self::$AllColor[$color]['name'] : '';
    }
}