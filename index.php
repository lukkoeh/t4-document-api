<?php
# Create and use Autoloader
use src\AuthenticationProvider;
use src\DatabaseSingleton;
use src\Response;
use src\ResponseController;

function autoload($class): void
{$class = str_replace('\\', '/', $class);require_once './' . $class . '.php';}
spl_autoload_register('autoload');

# Specify JSON Header and encoding
header('Content-Type: application/json; charset=utf-8');

# get credentials from post arguments from rest api call

# Parse Path
$path = explode("/", $_SERVER['REQUEST_URI']);
# Remove first blank element
array_shift($path);

# Case switch determines Endpoint, and passes the path to the respective Controller
switch ($path[0]) {
    case 'users':
        switch ($_SERVER["REQUEST_METHOD"]) {
            case "POST":
                $_POST = json_decode(file_get_contents("php://input"));
                $auth = new AuthenticationProvider();
                try {
                    $auth->createUser($_POST->email, $_POST->password, $_POST->firstname, $_POST->lastname);
                } catch (Exception $e) {
                    $r = new Response("500", ["message"=>$e->getMessage()]);
                    ResponseController::respondJson($r);
                }
                break;
            case "GET":
                $_GET = json_decode(file_get_contents("php://input"));
                $auth = new AuthenticationProvider();
                try {
                    $auth->readUserdata($_GET->token);
                } catch (Exception $e) {
                    $r = new Response("500", ["message"=>$e->getMessage()]);
                    ResponseController::respondJson($r);
                }
                break;
            case "PUT":
                $_PUT = json_decode(file_get_contents("php://input"));
                $auth = new AuthenticationProvider();
                try {
                    $auth->updateUserdata($_PUT->token, $_PUT->firstname, $_PUT->lastname, $_PUT->email);
                } catch (Exception $e) {
                    $r = new Response("500", ["message"=>$e->getMessage()]);
                    ResponseController::respondJson($r);
                }
                break;
            case "DELETE":
                $_DELETE = json_decode(file_get_contents("php://input"));
                $auth = new AuthenticationProvider();
                try {
                    $auth->deleteUser($_DELETE->token);
                } catch (Exception $e) {
                    $r = new Response("500", ["message"=>$e->getMessage()]);
                    ResponseController::respondJson($r);
                }
                break;
        }
        break;
    case 'documents':
        $r = new Response("200", ["message"=>"document endpoint"]);
        ResponseController::respondJson($r);
        break;
    case 'auth':
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $_POST = json_decode(file_get_contents("php://input"));
            $auth = new AuthenticationProvider();
            try {
                $auth->login($_POST->email, $_POST->password);
            } catch (Exception $e) {
                $r = new Response("500", ["message"=>$e->getMessage()]);
                ResponseController::respondJson($r);
            }
        }
        else if ($_SERVER["REQUEST_METHOD"] == "PUT") {
            $_PUT = json_decode(file_get_contents("php://input"));
            $auth = new AuthenticationProvider();
            try {
                $auth->updatePassword($_PUT->token, $_PUT->oldpassword, $_PUT->newpassword);
            } catch (Exception $e) {
                $r = new Response("500", ["message"=>$e->getMessage()]);
                ResponseController::respondJson($r);
            }
        }
        else {
            $r = new Response("405", ["message" => "method not allowed"]);
            ResponseController::respondJson($r);
        }
        break;
    default:
        $r = new Response("404", ["message"=>"not found"]);
        ResponseController::respondJson($r);
        break;
}