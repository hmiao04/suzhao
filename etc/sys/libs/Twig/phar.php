<?php
function logIt($str)
{
    echo '['.date("i:s") . '] ' . $str . "\n";
}

logIt("start ... ");

logIt("build env success...");
$fileName = 'Twig.phar';
if (file_exists($fileName)) {
    logIt("found phar file!!!");
    logIt("start delete it!");

    @unlink($fileName);
    if (file_exists($fileName)) {
        logIt('delete it failed~~~');
        logIt('exit');
        exit;
    }
}
logIt('start building ...');
$phar = new Phar($fileName, 0, $fileName);
$phar->buildFromDirectory(dirname(__FILE__) . '/Twig');
$phar->setStub($phar->createDefaultStub('Autoloader.php', 'Autoloader.php'));
$phar->compressFiles(Phar::GZ);

logIt("package phar success.");