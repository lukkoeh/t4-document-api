<?php
# Create and use Autoloader
function autoload($class){$class = str_replace('\\', '/', $class);require_once './' . $class . '.php';}
spl_autoload_register('autoload');

# Specify JSON Header and encoding
header('Content-Type: application/json; charset=utf-8');

# Parse Path
$path = explode("/", $_SERVER['REQUEST_URI']);
array_shift($path);

# Case switch determines Endpoint, and passes the path to the respective Controller
switch ($path[0]) {
    case 'users':
        print("user endpoint");
        break;
    case 'documents':
        print("document endpoint");
        break;
}