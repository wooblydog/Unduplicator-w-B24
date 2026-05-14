<?php
require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/config/testData.php';

use App\Controllers\LeadController;
use App\Models\Lead;
use App\Rules\AppointmentInFutureRule;
use App\Rules\CreatedLessThan24hRule;
use App\Services\Lead\LeadSelector;
use App\Services\Logger;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$logger = new Logger();
$leadController = new LeadController();
$lead = new Lead();
$selector = new LeadSelector();

$rules = [
    new CreatedLessThan24hRule(),
    new AppointmentInFutureRule(),
];

$selector->setRules($rules);
$leadController->handle($_POST);