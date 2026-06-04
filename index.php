<?php
require __DIR__ . '/vendor/autoload.php';

use App\Controllers\LeadController;
use App\Services\RetryFailsService;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$leadController = new LeadController();
$retryFailsService = new RetryFailsService();

if (isset($_GET['retry'])){
    $retryFailsService->init();
} else {
    $leadController->handle($_GET);
}

