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

$endpoint = $parts[1];

$id = $parts[2] ?? null;

$database = new Database($configs["host"], $configs["database"], $configs["username"], $configs["password"]);

$gateway = new ProductGateway($database);

$controller = new ProductController($gateway);

$controller->processRequest($_SERVER["REQUEST_METHOD"], $endpoint, $id);
?>