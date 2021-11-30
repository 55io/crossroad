<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require 'src/main.php';
if(is_array($_POST)) {
    $app->setSettings($_POST);
}

$host  = $_SERVER['HTTP_HOST'];
$uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
$extra = 'index.php';
header("Location: http://$host$uri/$extra");
exit;