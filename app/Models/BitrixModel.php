<?php

namespace App\Models;

use App\Factories\LoggerFactory;
use App\Services\EnvConnector;
use Bitrix24\SDK\Core\Exceptions\InvalidArgumentException;
use Bitrix24\SDK\Services\ServiceBuilderFactory;

class BitrixModel
{
    private EnvConnector $connector;
    private string $webhook;
    private \Monolog\Logger $logger;

    public function __construct()
    {
        $this->logger = LoggerFactory::create(session_id());

        try {
            $this->connector = new EnvConnector();
            $this->webhook = $this->connector->getBxWebhook();
            $this->logger->info('BitrixModel создан', ['webhook' => $this->webhook]);
        } catch (\Throwable $e) {
            $this->logger->error('Ошибка инициализации BitrixModel', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function connect()
    {
        try {
            $this->logger->info('Попытка подключения к Битрикс24');

            $serviceBuilder = ServiceBuilderFactory::createServiceBuilderFromWebhook($this->webhook);

            $this->logger->info('Подключение к Битрикс24 успешно');

            return $serviceBuilder;

        } catch (InvalidArgumentException $e) {
            $this->logger->error('Ошибка подключения к Битрикс24', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            exit('Ошибка подключения к Б24: ' . $e->getMessage());
        }
    }
}
