<?php
/**
 * File: api_list.php.
 * User: Yan<me@xiaoyan.me>
 * DateTime: 2017-02-14 17:37
 */
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>在线接口列表</title>
    <link rel="stylesheet" href="//cdn.bootcss.com/semantic-ui/2.2.7/semantic.min.css">
    <style>
        body,input,td,p,h2,h1,span,label,th,h3{font-family: "microsoft yahei", Helvetica, Arial, sans-serif !important;color:#666 !important;}
    </style>
</head>
<body>
<br/>

<div class="ui text container" style="max-width: none !important;">
    <div class="ui floating message">
        <h1 class="ui header">接口列表</h1>
        <table class="ui green celled striped table">
            <thead>
            <tr>
                <th>#</th>
                <th>接口服务</th>
                <th>接口描述</th>
            </tr>
            </thead>
            <tbody>
            <?php $index=1; foreach(ApiProcess::GetAll() as $key=>$method): ?>
            <tr>
                <td><?php echo $index++;?></td>
                <td><a href="?method=<?php echo $method->name;?>"><?php echo $method->name;?></a></td>
                <td><?php echo $method->document;?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <p>&copy; Powered By <a href="http://ycf.xiaoyan.me/" target="_blank">YCFramework <?php echo YCPF_VER;?></a>

        <p>
    </div>
</div>
</body>
</html>

