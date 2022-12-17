<?php
declare(strict_types=1);
$configs = include('config.php');

spl_autoload_register(function($class) {
    require __DIR__ . "/src/$class.php";
});

set_error_handler("ErrorHandler::handleError");
set_exception_handler("ErrorHandler::handleException");

header("Content-Type: application/json; charset=UTF-8");

$parts = explode("/", $_SERVER["REQUEST_URI"]);

if ($parts[2] != "api") {
    http_response_code(404);
    exit;
}

$endpoint = $parts[3];

$id = $parts[4] ?? null;

$database = new Database($configs["host"], $configs["database"], $configs["username"], $configs["password"]);

$gateway = new ProductGateway($database);

$controller = new ProductController($gateway);

$controller->processRequest($_SERVER["REQUEST_METHOD"], $endpoint, $id);
?>