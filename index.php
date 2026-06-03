<?php
require __DIR__ . '/vendor/autoload.php';

use App\Controllers\LeadController;
use App\Services\ConflictWriter;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$leadController = new LeadController();
$conflictWriter = new ConflictWriter();

$leadController->handle($_GET);

