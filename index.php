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

# Specify JSON Header and encoding, as well as CORS headers
header('Content-Type: application/json; charset=utf-8;');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PATCH, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-Auth-Token');
header('Access-Control-Allow-Credentials: true');
# Ignore OPTIONS Preflight call
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    $res = new Response("200", ["message" => "ok"]);
    ResponseController::respondJson($res);
}
# get credentials from post arguments from rest api call

# Parse Path
$route = explode("/", strtok($_SERVER['REQUEST_URI'], '?'));
# Remove first blank element
array_shift($route);
$option_count = sizeof($route);

# Ignore OPTIONS Preflight call
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    $res = new Response("200", ["message" => "ok"]);
    ResponseController::respondJson($res);
}

switch ($route[0]) {
    case "user":
        if (isset($route[1]) && is_numeric($route[1])) {
            switch ($_SERVER["REQUEST_METHOD"]) {
                case "GET":
                    if (!isset($_SERVER["HTTP_X_AUTH_TOKEN"])) {
                        $res = new Response("400", ["message" => "You performed a malformed request"]);
                        ResponseController::respondJson($res);
                    }
                    $auth_token = $_SERVER["HTTP_X_AUTH_TOKEN"];
                    $user_id = $route[1];
                    $auth = new AuthenticationProvider();
                    try {
                        $auth->readUserdata($auth_token, $user_id);
                    } catch (Exception $e) {
                        $res = new Response("500", ["message" => "Internal Server Error"]);
                        ResponseController::respondJson($res);
                    }
                    break;
                case "DELETE":
                    if (!isset($_SERVER["HTTP_X_AUTH_TOKEN"])) {
                        $res = new Response("400", ["message" => "You performed a malformed request"]);
                        ResponseController::respondJson($res);
                    }
                    $auth_token = $_SERVER["HTTP_X_AUTH_TOKEN"];
                    $user_id = $route[1];
                    $auth = new AuthenticationProvider();
                    try {
                        $auth->deleteUser($auth_token, $user_id);
                    } catch (Exception $e) {
                        $res = new Response("500", ["message" => "Internal Server Error"]);
                        ResponseController::respondJson($res);
                    }
                    break;
                case "PATCH":
                    if (!isset($_SERVER["HTTP_X_AUTH_TOKEN"])) {
                        $res = new Response("400", ["message" => "You performed a malformed request"]);
                        ResponseController::respondJson($res);
                    }
                    $auth_token = $_SERVER["HTTP_X_AUTH_TOKEN"];
                    $user_id = $route[1];
                    $_PATCH = json_decode(file_get_contents('php://input'), true);
                    $auth = new AuthenticationProvider();
                    try {
                        $auth->updateUserdata($auth_token, $user_id, $_PATCH["firstname"], $_PATCH["lastname"], $_PATCH["email"]);
                    } catch (Exception $e) {
                        $res = new Response("500", ["message" => "Internal Server Error"]);
                        ResponseController::respondJson($res);
                    }
                    break;
            }
        }
        else if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $auth = new AuthenticationProvider();
            try {
                $auth->createUser($_POST["email"], $_POST["password"], $_POST["firstname"], $_POST["lastname"]);
            } catch (Exception $e) {
                $res = new Response("500", ["message" => "Internal Server Error"]);
                ResponseController::respondJson($res);
            }
        }
        else {
            $res = new Response("400", ["message" => "You performed a malformed request"]);
            ResponseController::respondJson($res);
        }
        break;
    case "auth":
        if (!is_numeric($route[1]) && $option_count > 1) {
            switch ($route[1]) {
                case "login":
                    if ($_SERVER["REQUEST_METHOD"] == "POST") {
                        $auth = new AuthenticationProvider();
                        try {
                            $auth->login($_POST["email"], $_POST["password"]);
                        } catch (Exception $e) {
                            $res = new Response("500", ["message" => "Internal Server Error"]);
                            ResponseController::respondJson($res);
                        }
                    }
                    else {
                        $res = new Response("400", ["message" => "You performed a malformed request"]);
                        ResponseController::respondJson($res);
                    }
                    break;
                case "validate":
                    if ($_SERVER["REQUEST_METHOD"] == "GET") {
                        if (!isset($_SERVER["HTTP_X_AUTH_TOKEN"])) {
                            $res = new Response("400", ["message" => "You performed a malformed request"]);
                            ResponseController::respondJson($res);
                        }
                        $auth_token = $_SERVER["HTTP_X_AUTH_TOKEN"];
                        $auth = new AuthenticationProvider();
                        try {
                            $auth->validateToken($auth_token, true);
                        } catch (Exception $e) {
                            $res = new Response("500", ["message" => "Internal Server Error"]);
                            ResponseController::respondJson($res);
                        }
                    }
                    else {
                        $res = new Response("400", ["message" => "You performed a malformed request"]);
                        ResponseController::respondJson($res);
                    }
                    break;
                case "password":
                    if (is_numeric($route[2]) && $_SERVER["REQUEST_METHOD"] == "PATCH") {
                        $_PATCH = json_decode(file_get_contents('php://input'), true);
                        // POST /auth/password/{id}
                        if (!isset($_SERVER["HTTP_X_AUTH_TOKEN"])) {
                            $res = new Response("400", ["message" => "You performed a malformed request"]);
                            ResponseController::respondJson($res);
                        }
                        $auth_token = $_SERVER["HTTP_X_AUTH_TOKEN"];
                        $auth = new AuthenticationProvider();
                        try {
                            $auth->updatePassword($auth_token, $route[2], $_PATCH["old_password"], $_PATCH["new_password"]);
                        } catch (Exception $e) {
                            $res = new Response("500", ["message" => "Internal Server Error"]);
                            ResponseController::respondJson($res);
                        }
                    }
                    else {
                        $res = new Response("400", ["message" => "You performed a malformed request"]);
                        ResponseController::respondJson($res);
                    }
                    break;
            }
        }
    case "document":
        if (isset($route[1]) && is_numeric($route[1])) {
            switch ($_SERVER["REQUEST_METHOD"]) {
                case "GET":
                    // GET /document/{id}
                    if (!isset($_SERVER["HTTP_X_AUTH_TOKEN"])) {
                        $res = new Response("400", ["message" => "You performed a malformed request"]);
                        ResponseController::respondJson($res);
                    }
                    $auth_token = $_SERVER["HTTP_X_AUTH_TOKEN"];
                    $docs = new DocumentProvider();
                    try {
                        $docs->readDocumentMetaById($auth_token, $route[1]);
                    } catch (Exception $e) {
                        $res = new Response("500", ["message" => "Internal Server Error"]);
                        ResponseController::respondJson($res);
                    }
                    break;
                case "DELETE":
                    if (!isset($_SERVER["HTTP_X_AUTH_TOKEN"])) {
                        $res = new Response("400", ["message" => "You performed a malformed request"]);
                        ResponseController::respondJson($res);
                    }
                    $auth_token = $_SERVER["HTTP_X_AUTH_TOKEN"];
                    $docs = new DocumentProvider();
                    try {
                        $docs->deleteDocument($auth_token, $route[1]);
                    } catch (Exception $e) {
                        $res = new Response("500", ["message" => "Internal Server Error, in index.php"]);
                        ResponseController::respondJson($res);
                    }
                    break;
                case "PATCH":
                    // PATCH /document/{id}
                    parse_str(file_get_contents('php://input'), $_PATCH);
                    if (!isset($_SERVER["HTTP_X_AUTH_TOKEN"])) {
                        $res = new Response("400", ["message" => "You performed a malformed request"]);
                        ResponseController::respondJson($res);
                    }
                    $auth_token = $_SERVER["HTTP_X_AUTH_TOKEN"];
                    $docs = new DocumentProvider();
                    try {
                        $docs->updateDocument($auth_token, $route[1], $_PATCH["title"]);
                    } catch (Exception $e) {
                        $res = new Response("500", ["message" => "Internal Server Error"]);
                        ResponseController::respondJson($res);
                    }
                    break;
            }
        }
        else if ($_SERVER["REQUEST_METHOD"] == "POST") {
            // POST /document/
            if (!isset($_SERVER["HTTP_X_AUTH_TOKEN"])) {
                $res = new Response("400", ["message" => "You performed a malformed request"]);
                ResponseController::respondJson($res);
            }
            $auth_token = $_SERVER["HTTP_X_AUTH_TOKEN"];
            $docs = new DocumentProvider();
            try {
                $docs->createDocument($auth_token, $_POST["title"]);
            } catch (Exception $e) {
                $res = new Response("500", ["message" => "Internal Server Error"]);
                ResponseController::respondJson($res);
            }
        }
        else {
            $res = new Response("400", ["message", "You performed a malformed request"]);
            ResponseController::respondJson($res);
        }
        break;
    case "documents":
        if ($_SERVER["REQUEST_METHOD"] == "GET") {
            if (is_numeric($route[1])) {
                // GET /documents/{id}
                if (!isset($_SERVER["HTTP_X_AUTH_TOKEN"])) {
                    $res = new Response("400", ["message" => "You performed a malformed request"]);
                    ResponseController::respondJson($res);
                }
                $auth_token = $_SERVER["HTTP_X_AUTH_TOKEN"];
                $docs = new DocumentProvider();
                try {
                    $docs->readDocumentMetaCollection($auth_token, $route[1]);
                } catch (Exception $e) {
                    $res = new Response("500", ["message" => "Internal Server Error"]);
                    ResponseController::respondJson($res);
                }
            }
            else {
                $res = new Response("400", ["message", "You performed a malformed request"]);
                ResponseController::respondJson($res);
            }
        }
        else {
            $res = new Response("400", ["message", "You performed a malformed request"]);
            ResponseController::respondJson($res);
        }
        break;
    case "delta":
        if (isset($route[1]) && is_numeric($route[1])) {
            switch ($_SERVER["REQUEST_METHOD"]) {
                case "GET":
                    // GET /delta/{id}
                    if (!isset($_SERVER["HTTP_X_AUTH_TOKEN"])) {
                        $res = new Response("400", ["message" => "You performed a malformed request"]);
                        ResponseController::respondJson($res);
                    }
                    $auth_token = $_SERVER["HTTP_X_AUTH_TOKEN"];
                    $delta = new DeltaProvider();
                    try {
                        $delta->readDelta($auth_token, $route[1]);
                    } catch (Exception $e) {
                        $res = new Response("500", ["message" => "Internal Server Error"]);
                        ResponseController::respondJson($res);
                    }
                    break;
                case "POST":
                    // POST /delta/{id}
                    if (!isset($_SERVER["HTTP_X_AUTH_TOKEN"])) {
                        $res = new Response("400", ["message" => "You performed a malformed request"]);
                        ResponseController::respondJson($res);
                    }
                    $auth_token = $_SERVER["HTTP_X_AUTH_TOKEN"];
                    $delta = new DeltaProvider();
                    try {
                        $delta->createDelta($auth_token, $route[1], $_POST["delta"]);
                    } catch (Exception $e) {
                        $res = new Response("500", ["message" => "Internal Server Error"]);
                        ResponseController::respondJson($res);
                    }
                    break;
                case "DELETE":
                    // DELETE /delta/{id}
                    if (!isset($_SERVER["HTTP_X_AUTH_TOKEN"])) {
                        $res = new Response("400", ["message" => "You performed a malformed request"]);
                        ResponseController::respondJson($res);
                    }
                    $auth_token = $_SERVER["HTTP_X_AUTH_TOKEN"];
                    $delta = new DeltaProvider();
                    try {
                        $delta->deleteDelta($auth_token, $route[1]);
                    } catch (Exception $e) {
                        $res = new Response("500", ["message" => "Internal Server Error"]);
                        ResponseController::respondJson($res);
                    }
                    break;
                case "PATCH":
                    // PATCH /delta/{id}
                    if (!isset($_SERVER["HTTP_X_AUTH_TOKEN"])) {
                        $res = new Response("400", ["message" => "You performed a malformed request"]);
                        ResponseController::respondJson($res);
                    }
                    parse_str(file_get_contents('php://input'), $_PATCH);
                    $auth_token = $_SERVER["HTTP_X_AUTH_TOKEN"];
                    $delta = new DeltaProvider();
                    try {
                        $delta->updateDelta($auth_token, $route[1], $_PATCH["delta"]);
                    } catch (Exception $e) {
                        $res = new Response("500", ["message" => "Internal Server Error"]);
                        ResponseController::respondJson($res);
                    }
                    break;
            }
        } else {
            $res = new Response("400", ["message", "You performed a malformed request"]);
            ResponseController::respondJson($res);
        }
        break;
    case "deltas":
        if (is_numeric($route[1])) {
            switch ($_SERVER["REQUEST_METHOD"]) {
                case "GET":
                    // GET /deltas/{id}
                    if (!isset($_SERVER["HTTP_X_AUTH_TOKEN"])) {
                        $res = new Response("400", ["message" => "You performed a malformed request"]);
                        ResponseController::respondJson($res);
                    }
                    $auth_token = $_SERVER["HTTP_X_AUTH_TOKEN"];
                    $delta = new DeltaProvider();
                    try {
                        $delta->readDocumentDeltas($auth_token, $route[1]);
                    } catch (Exception $e) {
                        $res = new Response("500", ["message" => "Internal Server Error"]);
                        ResponseController::respondJson($res);
                    }
                    break;
            }
        }
        break;
    case "share":
        if (is_numeric($route[1]) && is_numeric($route[2])) {
            switch ($_SERVER["REQUEST_METHOD"]) {
                case "POST":
                    // POST /share/{document_id}/{user_id}
                    if (!isset($_SERVER["HTTP_X_AUTH_TOKEN"])) {
                        $res = new Response("400", ["message" => "You performed a malformed request"]);
                        ResponseController::respondJson($res);
                    }
                    $auth_token = $_SERVER["HTTP_X_AUTH_TOKEN"];
                    $docs = new DocumentProvider();
                    try {
                        $docs->shareDocument($auth_token, $route[1], $route[2]);
                    } catch (Exception $e) {
                        $res = new Response("500", ["message" => "Internal Server Error"]);
                        ResponseController::respondJson($res);
                    }
                    break;
                case "DELETE":
                    // DELETE /share/{document_id}/{user_id}
                    if (!isset($_SERVER["HTTP_X_AUTH_TOKEN"])) {
                        $res = new Response("400", ["message" => "You performed a malformed request"]);
                        ResponseController::respondJson($res);
                    }
                    $auth_token = $_SERVER["HTTP_X_AUTH_TOKEN"];
                    $docs = new DocumentProvider();
                    try {
                        $docs->unshareDocument($auth_token, $route[1], $route[2]);
                    } catch (Exception $e) {
                        $res = new Response("500", ["message" => "Internal Server Error"]);
                        ResponseController::respondJson($res);
                    }
                    break;
                default:
                    $res = new Response("400", ["message", "You performed a malformed request"]);
                    ResponseController::respondJson($res);
                    break;
            }
        }
        break;
    default:
        $res = new Response("404", ["message" => "not found"]);
        ResponseController::respondJson($res);
        break;
}