<?php
# Create and use Autoloader
use src\AuthenticationProvider;
use src\DatabaseSingleton;
use src\DeltaProvider;
use src\DocumentProvider;
use src\Response;
use src\ResponseController;

function autoload($class): void
{
    $class = str_replace('\\', '/', $class);
    require_once './' . $class . '.php';
}

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
                    $r = new Response("500", ["message" => $e->getMessage()]);
                    ResponseController::respondJson($r);
                }
                break;
            case "GET":
                $_GET = json_decode(file_get_contents("php://input"));
                $auth = new AuthenticationProvider();
                try {
                    $auth->readUserdata($_GET->token);
                } catch (Exception $e) {
                    $r = new Response("500", ["message" => $e->getMessage()]);
                    ResponseController::respondJson($r);
                }
                break;
            case "PUT":
                $_PUT = json_decode(file_get_contents("php://input"));
                $auth = new AuthenticationProvider();
                try {
                    $auth->updateUserdata($_PUT->token, $_PUT->firstname, $_PUT->lastname, $_PUT->email);
                } catch (Exception $e) {
                    $r = new Response("500", ["message" => $e->getMessage()]);
                    ResponseController::respondJson($r);
                }
                break;
            case "DELETE":
                $_DELETE = json_decode(file_get_contents("php://input"));
                $auth = new AuthenticationProvider();
                try {
                    $auth->deleteUser($_DELETE->token);
                } catch (Exception $e) {
                    $r = new Response("500", ["message" => $e->getMessage()]);
                    ResponseController::respondJson($r);
                }
                break;
        }
        break;
    case 'documents':
        switch ($_SERVER["REQUEST_METHOD"]) {
            case "GET":
                $_GET = json_decode(file_get_contents("php://input"));
                $doc = new DocumentProvider();
                try {
                    $doc->readDocumentMetaCollection($_GET->token);
                } catch (Exception $e) {
                    $r = new Response("500", ["message" => $e->getMessage()]);
                    ResponseController::respondJson($r);
                }
                break;
        }
        break;
    case 'document':
        switch ($_SERVER["REQUEST_METHOD"]) {
            case "GET":
                if (sizeof($path) == 2) {
                    $_GET = json_decode(file_get_contents("php://input"));
                    $doc = new DocumentProvider();
                    try {
                        $doc->readDocumentMetaById($_GET->token, $path[1]);
                    } catch (Exception $e) {
                        $r = new Response("500", ["message" => $e->getMessage()]);
                        ResponseController::respondJson($r);
                    }
                } else {
                    $r = new Response("400", ["message" => "Bad Request, requesting single document without id or with too many parameters."]);
                    ResponseController::respondJson($r);
                }
                break;
            case "POST":
                $_POST = json_decode(file_get_contents("php://input"));
                $doc = new DocumentProvider();
                try {
                    $doc->createDocument($_POST->token, $_POST->documentname);
                } catch (Exception $e) {
                    $r = new Response("500", ["message" => $e->getMessage()]);
                    ResponseController::respondJson($r);
                }
                break;
            case "PUT":
                if (sizeof($path) == 2) {
                    $_PUT = json_decode(file_get_contents("php://input"));
                    $doc = new DocumentProvider();
                    try {
                        $doc->updateDocument($_PUT->token, $path[1], $_PUT->documentname);
                    } catch (Exception $e) {
                        $r = new Response("500", ["message" => $e->getMessage()]);
                        ResponseController::respondJson($r);
                    }
                }
                else {
                    $r = new Response("400", ["message" => "Bad Request, requesting single document update without id or with too many parameters."]);
                    ResponseController::respondJson($r);
                }
                break;
            case "DELETE":
                if (sizeof($path) == 2) {
                    $_DELETE = json_decode(file_get_contents("php://input"));
                    $doc = new DocumentProvider();
                    try {
                        $doc->deleteDocument($_DELETE->token, $path[1]);
                    } catch (Exception $e) {
                        $r = new Response("500", ["message" => $e->getMessage()]);
                        ResponseController::respondJson($r);
                    }
                }
                else {
                    $r = new Response("400", ["message" => "Bad Request, requesting single document delete without id or with too many parameters."]);
                    ResponseController::respondJson($r);
                }
                break;
        }
        break;
    case 'deltas':
        switch ($_SERVER["REQUEST_METHOD"]) {
            case "GET":
                if (sizeof($path) == 2) {
                    $_GET = json_decode(file_get_contents("php://input"));
                    $deltaprovider = new DeltaProvider();
                    try {
                        $deltaprovider->readDocumentDeltas($_GET->token, $path[1]);
                    } catch (Exception $e) {
                        $r = new Response("500", ["message" => $e->getMessage()]);
                        ResponseController::respondJson($r);
                    }
                    break;
                }
        }
        break;
    case 'delta':
        switch ($_SERVER["REQUEST_METHOD"]) {
            case "GET":
                if (sizeof($path) == 2) {
                    $_GET = json_decode(file_get_contents("php://input"));
                    $deltaprovider = new DeltaProvider();
                    try {
                        $deltaprovider->readDelta($_GET->token, $path[1]);
                    } catch (Exception $e) {
                        $r = new Response("500", ["message" => $e->getMessage()]);
                        ResponseController::respondJson($r);
                    }
                    break;
                }
                break;
            case "POST":
                $_POST = json_decode(file_get_contents("php://input"));
                $deltaprovider = new DeltaProvider();
                try {
                    $deltaprovider->createDelta($_POST->token, $_POST->documentid, $_POST->deltacontent);
                } catch (Exception $e) {
                    $r = new Response("500", ["message" => $e->getMessage()]);
                    ResponseController::respondJson($r);
                }
                break;
            case "PUT":
                if (sizeof($path) == 2) {
                    $_PUT = json_decode(file_get_contents("php://input"));
                    $deltaprovider = new DeltaProvider();
                    try {
                        $deltaprovider->updateDelta($_PUT->token, $path[1], $_PUT->deltacontent);
                    } catch (Exception $e) {
                        $r = new Response("500", ["message" => $e->getMessage()]);
                        ResponseController::respondJson($r);
                    }
                    break;
                }
                break;
            case "DELETE":
                if (sizeof($path) == 2) {
                    $_DELETE = json_decode(file_get_contents("php://input"));
                    $deltaprovider = new DeltaProvider();
                    try {
                        $deltaprovider->deleteDelta($_DELETE->token, $path[1]);
                    } catch (Exception $e) {
                        $r = new Response("500", ["message" => $e->getMessage()]);
                        ResponseController::respondJson($r);
                    }
                    break;
                }
                break;
        }
        break;
    case 'auth':
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $_POST = json_decode(file_get_contents("php://input"));
            $auth = new AuthenticationProvider();
            try {
                $auth->login($_POST->email, $_POST->password);
            } catch (Exception $e) {
                $r = new Response("500", ["message" => $e->getMessage()]);
                ResponseController::respondJson($r);
            }
        } else if ($_SERVER["REQUEST_METHOD"] == "PUT") {
            $_PUT = json_decode(file_get_contents("php://input"));
            $auth = new AuthenticationProvider();
            try {
                $auth->updatePassword($_PUT->token, $_PUT->oldpassword, $_PUT->newpassword);
            } catch (Exception $e) {
                $r = new Response("500", ["message" => $e->getMessage()]);
                ResponseController::respondJson($r);
            }
        } else {
            $r = new Response("405", ["message" => "method not allowed"]);
            ResponseController::respondJson($r);
        }
        break;
    default:
        $r = new Response("404", ["message" => "not found"]);
        ResponseController::respondJson($r);
        break;
}