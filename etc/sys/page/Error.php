<?php
/**
 * File: Error.php:YCMS
 * User: xiaoyan f@yanyunfeng.com
 * Date: 15-5-4
 * Time: 下午2:12
 * @Description
 */?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo isset($title)?$title:'System Notice'  ?></title>
    <style type="text/css">
        ::selection{background-color: #333;color: #fff;}
        ::moz-selection{background-color: #333;color: #fff;}
        ::webkit-selection{background-color: #333;color: #fff;}
        body{background-color: #fff;margin: 50px;font: 13px/20px normal Helvetica, Arial, sans-serif;color: #4F5155;}
        a{color: #003399;background-color: transparent;font-weight: normal;}
        h1{color: #444;background-color: transparent;border-bottom: 1px solid #D0D0D0;font-size: 19px;font-weight: normal;margin: 0 0 14px 0;padding: 14px 15px 10px 15px;}
        code{font-family: Consolas, Monaco, Courier New, Courier, monospace;font-size: 12px;background-color: #f9f9f9;border: 1px solid #D0D0D0;color: #002166;display: block;margin: 14px 0 14px 0;padding: 12px 10px 12px 10px;}
        #container{margin-bottom: 50px;border: 1px solid #D0D0D0;-webkit-box-shadow: 0 0 8px #D0D0D0;}
        #info{padding-top: 10px;border-top: solid 1px #D0D0D0;}
        p{margin: 12px 15px 12px 15px;}
    </style>
</head>
<body>
<div id="container">
    <h1><?php echo isset($message)?$message:'System Notice'  ?></h1>
    <?php if (isset($subMessage) && $subMessage) {
        echo '<p>' . $subMessage . '</p>';
    }  ?>
</div>
<div id="info">
    <?php
    echo $_SERVER['SERVER_SOFTWARE'];
    echo ' Powered by XYRouter'.YCPF_VER;
    ?>
</div>
</body>
</html>