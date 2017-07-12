<?php
/**
 * File: api_info.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-02-14 17:46
 */
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?php echo $methodName;?>- 在线接口文档</title>
        <link rel="stylesheet" href="//cdn.bootcss.com/semantic-ui/2.2.7/semantic.min.css">
    <style>
        body,input,td,p,h2,h1,span,label,th,h3{font-family: "microsoft yahei", Helvetica, Arial, sans-serif !important;color:#666 !important;}
    </style>
</head>

<body>
<br>
<div class="ui text container" style="max-width: none !important;">
    <div class="ui floating message">
        <h2 class='ui header'>接口：<?php echo $methodName;?>
            <a href="?ac=all" style="font-size: 14px;">查看全部接口</a>
        </h2><br/>

        <div class="ui raised segment">
            <span class="ui red ribbon label">接口地址</span>
            <div class="ui message">
                <p> <?php echo 'http'.($_SERVER['SERVER_PORT'] == 80 ?'': 's').'://'.$_SERVER['HTTP_HOST'].''.URL().'/api/v1/gateway.do?method='.$methodName;?></p>
            </div>
            <span class="ui red ribbon label">接口说明</span>
            <div class="ui message">
                <p> <?php echo $description;?></p>
            </div>
        </div>
        <h3>接口参数</h3>
        <table class="ui green celled striped table">
            <thead>
            <tr>
                <th width="250">参数</th>
                <th width="150">类型</th>
                <th>说明</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach($params as $ret): ?>
                <tr>
                    <td><?php echo $ret[1];?></td>
                    <td><?php echo $ret[0];?></td>
                    <td><?php echo $ret[2];?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <h3>返回结果<a href="##" style="font-size:14px;margin-left: 10px"
                   onclick="var ele = document.getElementById('common_api_data');ele.style.display=ele.style.display=='none'?'table':'none';return false;">公共字段</a></h3>
        <table class="ui green celled striped table" id="common_api_data" style="display: none;">
            <thead>
            <tr>
                <th width="250">返回字段</th>
                <th width="150">类型</th>
                <th>说明</th>
            </tr>
            </thead>
            <tbody>

            <tr>
                <td>code</td>
                <td>string</td>
                <td>操作码,0表示成功,1表示不成功</td>
            </tr>
            <tr>
                <td>message</td>
                <td>string</td>
                <td>提示信息</td>
            </tr>
            <tr>
                <td>data</td>
                <td>object</td>
                <td>各接口的相应的字段</td>
            </tr>
            </tbody>
        </table>
        <table class="ui green celled striped table">
            <thead>
            <tr>
                <th width="250">返回字段</th>
                <th width="150">类型</th>
                <th>说明</th>
            </tr>
            </thead>
            <tbody>

            <?php foreach($returns as $ret): ?>
            <tr>
                <td><?php echo $ret[1];?></td>
                <td><?php echo $ret[0];?></td>
                <td><?php echo $ret[2];?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div class="ui blue message">
            <strong>温馨提示：</strong> 返回字段都在公共字段的data字段中。
        </div>
        <p>&copy; Powered By <a href="http://ycf.xiaoyan.me/" target="_blank">YCFramework <?php echo YCPF_VER;?></a>

        <p>
    </div>
</div>
</body>
</html>
