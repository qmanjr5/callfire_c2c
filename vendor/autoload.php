<?php
require_once(__DIR__.'/SplClassLoader.php');

$callfireLoader = new SplClassLoader('CallFire', __DIR__.'/CallFire-PHP-SDK/src');
$callfireLoader->register();

$stdlibLoader = new SplClassLoader('Zend\Stdlib', __DIR__);
$stdlibLoader->register();

