<?php
require __DIR__ . '/vendor/autoload.php';

use App\Controllers\LeadController;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

try {
    $router = require __DIR__ . '/config/routes.php';
    $response = $router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'], $_GET);
    http_response_code($response->getStatusCode());
    echo $response->getContent();
} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode(['error' => $e->getMessage()]);
}

//$leadController = new LeadController();
//
////test
////$_GET['ID'] = 1267265;
//$leadController->handle($_GET);

// $id = `{\"MainLead\":{\"Id\":\"1254523\",\"Uid\":\"\"},\"Duplicates\":[{\"Id\":\"874895\",\"Uid\":\"d559f20d-f1d7-41d6-a169-257b80268847\"}]}`;

// dd((array) $lead->get(1254523));

