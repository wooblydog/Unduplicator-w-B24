<?php
require __DIR__ . '/../vendor/autoload.php';

use App\Factories\LoggerFactory;
use App\Factories\LeadFactory;
use App\Models\CallModel;
use App\Rules\ActiveAppointmentRule;
use App\Rules\BlacklistRule;
use App\Rules\ConvertedRule;
use App\Rules\CreatedCloseRule;
use App\Rules\IsCallTouchRule;
use App\Services\Lead\LeadSelector;
use App\Services\PhoneFormatter;
use App\Services\Redis\RedisMessageBuilder;
use App\Services\Redis\RedisSender;

$logger = LoggerFactory::create(session_id());

try {
    $logger->info('Старт скрипта index.php');

    $callModel = new CallModel();
    $factory = new LeadFactory($callModel);
    $phoneFormatter = new PhoneFormatter();

    $rules = [
        new BlacklistRule(),
        new ConvertedRule(),
        new ActiveAppointmentRule(),
        new IsCallTouchRule(),
        new CreatedCloseRule(),
    ];
    $selector = new LeadSelector($rules);

    if (!isset($_GET['id'])) {
        $logger->warning('Не передан параметр id');
        die();
    }

    $newLeadId = (int)$_GET['id'];
    $logger->notice('Получен ID нового лида', ['newLeadId' => $newLeadId]);

    $lead = $factory->createFromId($newLeadId);
    if (!$lead) {
        $logger->error('Лид не найден', ['newLeadId' => $newLeadId]);
        die('Лид не найден');
    }

    $leadPhone = $phoneFormatter->format($lead->getData()['PHONE'][0]['VALUE']);

    $leads = $factory->createFromPhone($leadPhone);
    $logger->info('Собран массив лидов с дубликатами', ['count' => count($leads)]);

    $resultFromSelector = $selector->chooseMainLead($leads, $lead->getData());

    $redisMessage = new RedisMessageBuilder($resultFromSelector);
    $redisJson = $redisMessage->buildMessage();

    $redisSender = new RedisSender();
    $redisSender->send($redisJson);

    $logger->info('Отправлено сообщение в Redis', ['message' => $redisJson]);

    echo $redisJson;

} catch (\Throwable $e) {
    $logger->error('Произошла ошибка в index.php', [
        'exception' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
