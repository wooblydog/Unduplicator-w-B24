<?php
require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/app/config/testData.php';
ini_set('display_errors', 1);

use App\Controllers\LeadController;
use App\Models\Lead;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$leadController = new LeadController();
$lead = new Lead();

//test
$_POST['ID'] = 1251732;
$leadController->handle($_POST);
