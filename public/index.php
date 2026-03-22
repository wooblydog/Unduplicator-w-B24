<?php
require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/config/testData.php';

ini_set('display_errors', 1);

use App\Controllers\LeadController;
use App\Models\Lead;
use App\Rules\AppointmentInFutureRule;
use App\Rules\CreatedLessThan24hRule;
use App\Services\Lead\LeadSelector;
use App\Services\Logger;


$logger = new Logger();
$leadController = new LeadController();
$lead = new Lead();
$selector = new LeadSelector();

$rules = [
    new CreatedLessThan24hRule(),
    new AppointmentInFutureRule(),
];

$selector->setRules($rules);

//dump("Правило возраста", $selector->chooseMainLead($ageRuleTestSet["dup"], $ageRuleTestSet["new"]));
//dump("Правило записи", $selector->chooseMainLead($hasApptTestSet["dup"], $hasApptTestSet["new"]));
//dump("Правило записи в прошлом", $selector->chooseMainLead($pastApptTestSet["dup"], $pastApptTestSet["new"]));
//dump("Конфликтные", $selector->chooseMainLead($conflictRulesTestSet["dup"], $conflictRulesTestSet["new"]));

//$test = $lead->findByPhone($dupPhone, $_POST['id']);
$leadController->handle($_POST);


//try {
//    $logger->notice('Старт скрипта index.php');
//
//    die(123);
//    $factory = new LeadFactory();
//    $phoneFormatter = new PhoneFormatter();
//
//    $rules = [
//        new BlacklistRule(),
//        new ConvertedRule(),
//        new HasAppointmentRule(),
//        new IsCallTouchRule(),
//        new AgeCreationRule(),
//    ];
//
//    $selector = new LeadSelector($rules);
//
//    if (!isset($_GET['id'])) {
//        $logger->warning('Не передан параметр id');
//        die();
//    }
//
//    $newLeadId = (int)$_GET['id'];
//    $logger->notice('Получен ID нового лида', ['newLeadId' => $newLeadId]);
//
//    $lead = $factory->createFromId($newLeadId);
//    if (!$lead) {
//        $logger->error('Лид не найден', ['newLeadId' => $newLeadId]);
//        die('Лид не найден');
//    }
//
//    $leadPhone = $phoneFormatter->format($lead->getData()['PHONE'][0]['VALUE']);
//
//    $leads = $factory->createFromPhone($leadPhone);
//    $logger->notice('Собран массив лидов с дубликатами', ['count' => count($leads)]);
//
//    $resultFromSelector = $selector->chooseMainLead($leads, $lead->getData());
//
//    $redisMessage = new RedisMessageBuilder($resultFromSelector);
//    $redisJson = $redisMessage->buildMessage();
//
//    $redisSender = new RedisSender();
//    $redisSender->send($redisJson);
//
//    $logger->notice('Отправлено сообщение в Redis', ['message' => $redisJson]);
//
//    echo $redisJson;
//
//} catch (\Throwable $e) {
//    //записать плохие триа и ретрайнуть по круду раз в день
//    $logger->error('Произошла ошибка в index.php', [
//        'exception' => $e->getMessage(),
//        'trace' => $e->getTraceAsString()
//    ]);
//    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
//}
