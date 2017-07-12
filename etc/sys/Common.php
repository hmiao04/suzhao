<?php
function request($k, $t = 'R', $default = null)
{
    switch ($t) {
        case 'P':
            $var = &$_POST;
            break;
        case 'G':
            $var = &$_GET;
            break;
        case 'C':
            $var = &$_COOKIE;
            break;
        case 'R':
            $var = &$_REQUEST;
            break;
    }
    return isset($var[$k]) ? (is_array($var[$k]) ? $var[$k] : trim($var[$k])) : $default;
}

function decode_json($data)
{
    return @@json_decode($data, true);
}

function encode_json($data)
{
    if (defined('JSON_UNESCAPED_UNICODE')) {
        return json_encode($data, JSON_UNESCAPED_UNICODE);
    } else {
        return json_encode($data);
    }
}

function stringFormat($string)
{
    $args = func_get_args();
    $matched = array();
    array_shift($args);
    if (preg_match_all('/\{(\d+)\}/i', $string, $matched)) {
        $searchArray = $replaceArray = array();
        foreach ($matched[1] as $index) {
            $findIndex = sprintf('{%d}', $index);
            if ($index >= count($args)) {
                throw new OutOfBoundsException(sprintf('%s out of arguments', $findIndex));
            }
            if (in_array($findIndex, $searchArray)) continue;
            $searchArray[] = $findIndex;
            $replaceArray[] = $args[$index];
        }
        $string = str_replace($searchArray, $replaceArray, $string);
    }
    return $string;
}

function config_item($key)
{
    return YCFCore::getInstance()->getConf($key);
}

if (!function_exists('remove_invisible_characters')) {
    function remove_invisible_characters($str, $url_encoded = TRUE)
    {
        $non_displayables = array();

        // every control character except newline (dec 10)
        // carriage return (dec 13), and horizontal tab (dec 09)

        if ($url_encoded) {
            $non_displayables[] = '/%0[0-8bcef]/';    // url encoded 00-08, 11, 12, 14, 15
            $non_displayables[] = '/%1[0-9a-f]/';    // url encoded 16-31
        }

        $non_displayables[] = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S';    // 00-08, 11, 12, 14-31, 127

        do {
            $str = preg_replace($non_displayables, '', $str, -1, $count);
        } while ($count);

        return $str;
    }
}
function fileSizeFormat($size)
{
    $unit = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');
    return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
}

function gbk2utf8(&$arrayData)
{
    if ($arrayData) {
        if (is_array($arrayData)) {
            foreach ($arrayData as $key => $value) {
                if (is_array($value)) {
                    $arrayData[$key] = gbk2utf8($value);
                } elseif (is_string($value)) {
                    $arrayData[$key] = iconv('gbk', 'utf-8', $value);
                }
            }
        } elseif (is_string($arrayData)) {
            $arrayData = iconv('gbk', 'utf-8', $arrayData);
        }
    }
    return $arrayData;
}

function getNumber($k, $min = 0)
{
    $number = request($k, 'R', $min);
    $number = $number < $min ? $min : $number;
    return $number;
}

function getPage($k = 'page')
{
    $page = request($k, 'G', 1);
    $page = $page < 1 ? 1 : $page;
    return $page;
}

function timeIsNull($timeStr)
{
    return $timeStr == null || $timeStr == "" || $timeStr == "0000-00-00" || $timeStr == "0000-00-00 00:00:00";
}

function formatDateTime($timeStr)
{
    return strlen($timeStr) > 10 ? substr($timeStr, 0, 10) : $timeStr;
}

function getPageString($total, $size, $page, $url = '')
{
    $sp = $url && strpos($url, "?") > -1 ? "&" : "?";
    $total = ceil($total / $size);
    if ($total < 1) {
        return "";
    }
    $pagestr = '<a href="' . $url . $sp . 'page=1" class="padding bold">First</a>';
    for ($i = 1; $i <= $total; $i++) {
        $pagestr .= '<a href="' . ($i == $page ? 'javascript:void(0);' : $url . $sp . 'page=' . $i) . '"' . ($i == $page ? ' class="active"' : '') . '>' . $i . '</a>';
    }
    $pagestr .= '<a href="' . $url . $sp . 'page=' . $total . '" class="padding bold">Last</a>';
    return $pagestr;
}

function getClientIP()
{
    global $ip;
    if (getenv("HTTP_CLIENT_IP")) {
        $ip = getenv("HTTP_CLIENT_IP");
    } else if (getenv("HTTP_X_FORWARDED_FOR")) {
        $ip = getenv("HTTP_X_FORWARDED_FOR");
    } else if (getenv("REMOTE_ADDR")) {
        $ip = getenv("REMOTE_ADDR");
    } else {
        $ip = "unknow";
    }
    return $ip;
}

function getString2Time($str)
{
    $year = ((int)substr($str, 0, 4));//取得年份
    $month = ((int)substr($str, 5, 2));//取得月份
    $day = ((int)substr($str, 8, 2));//取得几号
    $hours = 23;
    $minute = 59;
    $second = 59;
    if (strlen($str) != 19) {
        $hours = ((int)substr($str, 11, 2));//取得小时
        $minute = ((int)substr($str, 14, 2));//取得分钟
        $second = ((int)substr($str, 17, 2));//取得秒
    }
    return mktime($hours, $minute, $second, $month, $day, $year);
}

function dateformat($datetime, $format = "Y-m-d H:i:s")
{
    if (strlen($datetime) != 19) {
        return $datetime;
    }
    $year = ((int)substr($datetime, 0, 4));//取得年份
    $month = ((int)substr($datetime, 5, 2));//取得月份
    $day = ((int)substr($datetime, 8, 2));//取得几号
    $hours = ((int)substr($datetime, 11, 2));//取得小时
    $minute = ((int)substr($datetime, 14, 2));//取得分钟
    $second = ((int)substr($datetime, 17, 2));//取得秒
    return date($format, mktime($hours, $minute, $second, $month, $day, $year));
}

function ajaxSuccess($data = array(), $dataType = 'json', $message = 'success')
{
    ajaxResponse(0, $message, $data, $dataType);
}

/**
 * @param \Exception $e
 * @param int $code
 * @param array $data
 * @param string $dataType
 */
function ajaxException($e, $code = -1, $data = array(), $dataType = 'json')
{
    ajaxError($e->getMessage(), $code, $data, $dataType);
}

function ajaxError($msg = 'the message', $code = -1, $data = array(), $dataType = 'json')
{
    $msg = is_array($msg) ? $msg : strtoupper($msg);
    ajaxResponse($code, $msg, $data, $dataType);
}

function ajaxResponse($code = 0, $msg = 'the message', $data = '', $dataType = 'json')
{
    if (is_array($code)) {
        $data = $msg;
        $msg = $code[1];
        $code = $code[0];
    }
    $retData = array('code' => $code, 'message' => $msg, 'data' => $data);
    if ($dataType == 'xml') exit(arrayToXml($retData));
    header('Content-Type: application/json');
    if (defined('JSON_UNESCAPED_UNICODE')) {
        exit(json_encode($retData, JSON_UNESCAPED_UNICODE));
    }
    exit(json_encode($retData));
}

function isAjax()
{
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
    ) {
        return true;
    }
    return false;
}

function getPassword($loginId, $loginPass)
{
    $pass = '$P$';
    $pass1 = base64_encode(md5($loginId . $loginPass));
    $pass2 = '$' . base64_encode(time());
    return str_replace('=', '', strtoupper($pass . $pass1 . $pass2));
}

function getPassword1($loginId, $loginPass)
{
    $pass = '$P$';
    $pass1 = base64_encode(md5($loginId . $loginPass));
    $pass2 = '$' . substr(md5($loginId . $loginPass), 0, 4);
    return str_replace('=', '', strtoupper($pass . $pass1 . $pass2));
}

function str_endwith($str, $search)
{
    return preg_match('[' . $str . '$]', $search);
    if (substr($str, 0, 0 - strlen($search)) == $search) {
        return true;
    }
    return false;
}

function file_get_mime($filename)
{
    if ($filename && file_exists($filename)) {
        $type = 'application/octet-stream';
        if (function_exists('mime_content_type')) {
            return mime_content_type($filename);
        } elseif (function_exists('finfo_open')) {
            $file_info = finfo_open(FILEINFO_MIME_TYPE); // 返回 mime 类型
            $type = finfo_file($file_info, $filename);
            finfo_close($file_info);
            return $type;
        } else {
            $fh = fopen($filename, 'rb');
            if ($fh) {
                $bytes6 = fread($fh, 6);
                fclose($fh);
                if ($bytes6 === false) return false;
                if (substr($bytes6, 0, 3) == "\xff\xd8\xff") return 'image/jpeg';
                if ($bytes6 == "\x89PNG\x0d\x0a") return 'image/png';
                if ($bytes6 == "GIF87a" || $bytes6 == "GIF89a") return 'image/gif';

                $code = unpack('c2chars/n2int', $bytes6);
                $type_code = intval($code['chars1'] . $code['chars2']);
                if ($type_code == 6677) return 'image/bmp';

                return 'application/octet-stream';
            }
            return false;
        }
    }
    return false;
}

/**
 * @param $str
 * @param $full_string
 * @return int
 */
function str_startwith($str, $full_string)
{
    $str = str_replace('\\', '\\\\', $str);
    return preg_match('[^' . $str . ']', $full_string);
}

/**
 * 获取文件名后缀
 */
function getFileSuffix($fileName)
{
    return strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
}

function getFileName($filePath)
{
    return pathinfo($filePath, PATHINFO_BASENAME);
}

function getFileNameWithOutSuffix($filePath)
{
    $fileName = getFileName($filePath);
    $suffixLen = strlen(getFileSuffix($fileName));
    if ($suffixLen > 0) {
        $suffixLen++;
    }
    return substr($fileName, 0, strlen($fileName) - $suffixLen);
}

/**
 * @param string $all
 * @param string $find
 * @return bool|int
 */
function str_index_of($all, $find)
{
    return strpos($all, $find);
}

function lastindexof($all, $part)
{
    if (trim($all) == "" || trim($part) == "") return 0;
    $lastIndexOf = $offset = 0;
    while (strpos($all, $part) !== false) {
        $indexOf = strpos($all, $part);
        $lastIndexOf = $lastIndexOf + $indexOf + $offset;
        $all = substr($all, $indexOf + strlen($part));
        $offset = strlen($part);
    }

    return $lastIndexOf;
}

function getDirFiles($dirPath, $filter = '')
{
    $dir = opendir($dirPath);
    $files = array();
    while (($file = readdir($dir)) !== false) {
        if (!str_startwith($file, '.') && !str_startwith($file, '..')) {
            if ($filter) {
                if (preg_match('/' . $filter . '/', $file)) {
                    $files[] = $file;
                }
            } else {
                $files[] = $file;
            }
        }
    }
    closedir($dir);
    return $files;
}

/**
 * Makes directory
 * @link http://www.php.net/manual/en/function.mkdir.php
 * @param dir string <p>
 * The directory path.
 * </p>
 */
function mkdirs($dir)
{
    if (!is_dir($dir)) {
        if (!mkdirs(dirname($dir))) {
            return false;
        }
        if (!mkdir($dir, 0777)) {
            return false;
        }
    }
    return true;
}

/**
 * 删除目录
 * @param string $dirName
 */
function delDirAndFile($dirName)
{
    if ($handle = opendir("$dirName")) {
        while (false !== ($item = readdir($handle))) {
            if ($item != "." && $item != "..") {
                if (is_dir("$dirName/$item")) {
                    delDirAndFile("$dirName/$item");
                } else {
                    if (unlink("$dirName/$item")) {
                        return true;
                    }
                }
            }
        }
        closedir($handle);
        if (rmdir($dirName)) {
            return true;
        }
    }
}

function base62($x)
{
    $show = "";
    while ($x > 0) {
        $s = $x % 62;
        if ($s > 35) {
            $s = chr($s + 61);
        } elseif ($s > 9 && $s <= 35) {
            $s = chr($s + 55);
        }
        $show .= $s;
        $x = floor($x / 62);
    }
    return $show;
}

/**
 * 短网址生成
 * Enter description here ...
 * @param unknown_type $url
 */
function urlShort($url)
{
    $url = crc32($url);
    $result = sprintf("%u", $url);
    $md5 = substr(md5($url), 0, 6);
    return base62($result) . $md5;
}

function shortUrl($long_url)
{
    $key = 'xiaoyan.me';
    $base32 = "abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";

    // 利用md5算法方式生成hash值
    $hex = hash('md5', $long_url . $key);
    $hexLen = strlen($hex);
    $subHexLen = $hexLen / 8;

    $output = array();
    for ($i = 0; $i < $subHexLen; $i++) {
        // 将这32位分成四份，每一份8个字符，将其视作16进制串与0x3fffffff(30位1)与操作
        $subHex = substr($hex, $i * 8, 8);
        $idx = 0x3FFFFFFF & (1 * ('0x' . $subHex));

        // 这30位分成6段, 每5个一组，算出其整数值，然后映射到我们准备的62个字符
        $out = '';
        for ($j = 0; $j < 6; $j++) {
            $val = 0x0000003D & $idx;
            $out .= $base32[$val];
            $idx = $idx >> 5;
        }
        $output[$i] = $out;
    }
    return $output[2] . $output[3];
}

function count_line($filename)
{
    if (!@file_exists($filename)) return 0;
    $fp = fopen($filename, "r");
    $i = 0;
    while (!feof($fp)) {
        //每次读取1M
        if ($data = fread($fp, 1024 * 1024 * 1)) {
            //计算读取到的行数
            $num = substr_count($data, "\n");
            $i += $num;
        }
    }
    fclose($fp);
    return $i;
}

/**
 * php auth
 * @param string $username check username
 * @param string $password check password
 * @param string $authDescription
 * @param string $cancel
 * @return bool
 */
function phpAuthCheck($username, $password, $authDescription = 'Please Login', $cancel = 'You Click Cancel Button')
{
    if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
        if (
            $_SERVER['PHP_AUTH_USER'] == $username &&
            $_SERVER['PHP_AUTH_PW'] == $password
        ) {
            return true;
        }
    }
    header('WWW-Authenticate: Basic realm="' . $authDescription . '"');
    header('HTTP/1.0 401 Unauthorized');
    exit($cancel);
}

function showGravatar($email, $size = 50)
{
    echo '<img src="http://www.gravatar.com/avatar.php?gravatar_id=' . md5($email) . '&size=' . $size . '" width="' . $size . '" height="' . $size . '" style="vertical-align: middle;" />';
}

function get_long_time($date)
{
    $curr = time();
    $tmp = $curr - $date;
    if ($tmp < 60) {
        $re = $tmp . '秒前';
    } else if ($tmp < 3600) {
        $re = floor($tmp / 60) . '分钟前';
    } else if ($tmp < 86400) {
        $re = floor($tmp / 3600) . '小时前';
    } else if ($tmp < 259200) {//3天内
        $re = floor($tmp / 86400) . '天前';
    } else {
        $re = date('Y年m月d日 H:i:s', $date);
    }
    echo $re;
}

/**
 * @param array $config
 * @return DBCore|null
 */
function DB($config = array())
{
    if (is_string($config)) {
        $ck = $config;
        $config = YCFCore::getInstance()->getConf('db_config');
        if (!isset($config[$ck])) throw new AppException('not found ' . $ck);
        $config = $config[$ck];
    }
    $DB_KEY = 'DB_OBJ';
    if (!empty($config) && is_array($config)) {
        $DB_KEY .= '_' . md5(serialize($config));
    }
    if (Cache::getInstance()->exists($DB_KEY)) {
        $db = Cache::getInstance()->get($DB_KEY);
        $db->resetVars();
        return $db;
    }
    $dbc = YCFCore::getInstance()->getConf('db_config');
    if (isset($dbc['autoload']) && $dbc['autoload'] == false) {
        $db_obj = new DBCore();
        Cache::getInstance()->set($DB_KEY, $db_obj);
        $db_obj->resetVars();
        return $db_obj;
    }
    $dbc = $dbc[$dbc['runtime']];
    if (!empty($config) && is_array($config)) $dbc = array_merge($dbc, $config);
    $db_obj = new DBCore($dbc);
    Cache::getInstance()->set($DB_KEY, $db_obj);
    $db_obj->resetVars();
    return $db_obj;
}

function disposeDB($config = array())
{
    $DB_KEY = 'DB_OBJ';
    if (!empty($config) && is_array($config)) {
        $DB_KEY .= '_' . md5(serialize($config));
    }
    Cache::getInstance()->delete($DB_KEY);
}

function THEME()
{
    $r = YCFCore::getInstance();
    return URL() . '/static/template/' . $r->getConf('theme');
}

function http_hyper()
{
    return ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';

}

function URL($host = false)
{
    $context = substr($_SERVER['SCRIPT_NAME'], 0, lastindexof($_SERVER['SCRIPT_NAME'], '/'));
    if (substr($context, 0, 2) == '//') $context = substr($context, 1);
    return $host ? http_hyper() . $_SERVER['HTTP_HOST'] . $context : $context;
}

function initMore()
{
    Logger::getLogger()->sys('start init template vars');
    $r = YCFCore::getInstance();
    $twig = $r->getTwig();
    $path = $twig->getLoader()->getPaths();
    $twig->getLoader()->setPaths($path[0] . '/' . $r->getConf('theme'));
    $twig->addFunction("__THEME__", new Twig_Function_Function("THEME"));
    $twig->addFunction("__URL__", new Twig_Function_Function("URL"));
    $r->getTmpl()->assign('__URL__', URL());
    $r->getTmpl()->assign('HOME', URL());
    $r->getTmpl()->assign('__THEME__', THEME());
}

function buildTree($items, $key = 'id', $parentKey = 'parent')
{
    $newData = array();
    foreach ($items as $item)
        $newData[$item[$key]] = $item;
    $items = $newData;
    foreach ($items as $item)
        $items[$item[$parentKey]]['child'][$item[$key]] = &$items[$item[$key]];
    return isset($items[0]['child']) ? $items[0]['child'] : array();
}

function createUnique($prefix = 'TSK')
{
    $data = substr(uniqid(), 2) . rand(10, 99);
    return strtoupper($prefix . $data);
}

/**
 * @param $fileName
 * @return if file not exists return false else if can read return array or return null
 */
function getExcelData($fileName)
{
    if (!file_exists($fileName)) {
        return false;
    }
    YCLoader::init()->import('Excel/PHPExcel');
    $fileInfo = pathinfo($fileName);
    $excel = new PHPExcel();

    /**默认用excel2007读取excel，若格式不对，则用之前的版本进行读取*/
    $reader = new PHPExcel_Reader_Excel2007();
    if (strtolower($fileInfo['extension']) == 'xls') {
        $reader = new PHPExcel_Reader_Excel5();
    }
    if (!$reader->canRead($fileName)) { //判断能否读取数据
        return null;
    }
    $excel = $reader->load($fileName); //加载文件
    $sheet = $excel->getActiveSheet(); //获取活动的sheet
    $highestRow = $sheet->getHighestRow(); //获得最大行数
    $highestColumn = $sheet->getHighestColumn(); //获得最大列数
    $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);
    $excelData = array();
    for ($row = 1; $row <= $highestRow; $row++) {
        for ($col = 0; $col < $highestColumnIndex; $col++) {
            $excelData[$row][] = (string)$sheet->getCellByColumnAndRow($col, $row)->getValue();
        }
    }
    return $excelData;
}

function array_key_values($array, $key)
{
    $arr = array();
    foreach ($array as $v) {
        $arr[] = $v[$key];
    }
    return $arr;
}

function array_value_toKey($array, $key)
{
    $arr = array();
    foreach ($array as $v) {
        if (!is_array($v[$key])) $arr[$v[$key]] = $v;
    }
    return $arr;
}

function arrayToXml($arr, $dom = 0, $item = 0)
{
    if (!$dom) {
        $dom = new DOMDocument("1.0", "utf-8");
    }
    if (!$item) {
        $item = $dom->createElement("root");
        $dom->appendChild($item);
    }
    foreach ($arr as $key => $val) {
        $itemx = $dom->createElement(is_string($key) ? $key : "item");
        $item->appendChild($itemx);
        if (!is_array($val)) {
            $text = $dom->createTextNode($val);
            $itemx->appendChild($text);

        } else {
            arrayToXml($val, $dom, $itemx);
        }
    }
    return $dom->saveXML();
}

/**
 * 求两个日期之间相差的天数
 * @param string $day1
 * @param string $day2
 * @return number
 */
function getDaysFromTowDate($day1, $day2)
{
    $second1 = is_numeric($day1) ? $day1 : strtotime($day1);
    $second2 = is_numeric($day2) ? $day2 : strtotime($day2);

    if ($second1 < $second2) {
        $tmp = $second2;
        $second2 = $second1;
        $second1 = $tmp;
    }
    return ($second1 + 1 - $second2) / 86400;
}

function jump_url($url, $time = 0)
{
    //
    var_dump(headers_sent());
    if ($time <= 0 && !headers_sent()) {
        header("Location: " . $url, true, 302);
        exit;
    }
    echo '<meta http-equiv="refresh" content="' . $time . ';url=' . $url . '">';
    exit;
}

/**
 * @return bool
 */
function is_session_started()
{
    if (php_sapi_name() !== 'cli') {
        if (version_compare(phpversion(), '5.4.0', '>=')) {
            return session_status() === PHP_SESSION_ACTIVE ? TRUE : FALSE;
        } else {
            return session_id() === '' ? FALSE : TRUE;
        }
    }
    return FALSE;
}

/**
 * 启动新会话或者重用现有会话
 */
function start_the_session()
{
    if (is_session_started() === FALSE) session_start();
}
