<?php
/**
 * File: category.import.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-05-23 17:09
 */
$category = [];
$handle = fopen('category.data.txt', 'r');
if ($handle) {
    $new_category = false;
    $cate_item = [];
    while (($line = fgets($handle, 4096)) !== false) {
        $line = trim($line);
        if (strlen($line) == 0) {
            $category[] = $cate_item;
            continue;
        }
        if (substr($line, 0, 1) == 'c') {
            $cate_item = [
                'cate_name' => substr($line, 1),
                'child' => []
            ];
        } else {
            $cate_item['child'] = explode(' ', $line);
        }
    }
    fclose($handle);
}

include dirname(dirname(dirname(__FILE__))) . '/etc/sys/libs/DBCore.php';
$db_config = array(
    'server' => 'localhost',
    'database_type' => 'mysql',
    'username' => 'root',
    'password' => '123456',
    'port' => 3306,
    'charset' => 'utf8',
    'database_name' => 'suzhao',
    'db_prefix' => '',
    'option' => array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    )
);
$db = new DBCore($db_config);
//INSERT INTO `sz_common_category` (`cate_name`, `type`) VALUES ('123', 'goods')
foreach ($category as $category_f) {
    $cid = $db->insert('sz_common_category', array('cate_name' => $category_f['cate_name'], 'type' => 'goods'));
    if ($cid > 0) {
        foreach ($category_f['child'] as $cate_name) {
            $db->insert('sz_common_category', array(
                'cate_name' => $cate_name, 'parent_id' => $cid, 'type' => 'goods'));
        }
    }
}