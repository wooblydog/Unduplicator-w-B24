<?php

use App\Router;
use App\Controllers\LeadController;
use App\Services\FailedHandler;

$router = new Router();

$router->addRoute('GET', '^/api/run.undup$', function ($request) {
    $controller = new LeadController();
    $controller->handle($request);
});

//$router->addRoute('GET', '^/api/run.failed$', function ($request) {
//    $service = new FailedHandler();
//    $service->process($request);
//});

$router->addRoute('GET', '^/api/status$', function () {
    header('HTTP/1.1 200 OK');
});

$router->addRoute('GET', '^/', function () {
    throw new \Exception ("Bad request", 400);
});

$router->addRoute('POST', '.*', function () {
    throw new \Exception ("Method not allowed", 405);
});

$router->addRoute('GET', '/api/run.undup$', function ($request) {
    echo "It works!";
});

$router->addRoute('GET', '/api/status$', function () {
    echo "Status OK";
});

// Корень проекта (с учетом подпапок)
$router->addRoute('GET', 'Undup-v2/$', function () {
    echo "Main Page";
});

return $router;