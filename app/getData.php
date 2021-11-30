<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require 'src/main.php';
$app->run();
echo json_encode($app->serializeData());