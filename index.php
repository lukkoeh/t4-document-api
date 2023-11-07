<?php
# Include Autoloader
include 'Autoloader.php';


# Specify JSON Header and encoding
header('Content-Type: application/json; charset=utf-8');

$err = new \Classes\handlers\ErrorHandler();
$err->test();
