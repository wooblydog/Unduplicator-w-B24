<?php

namespace App\Services\Redis;

use App\Factories\LoggerFactory;
use App\Services\EnvConnector;
use Predis\Client as PredisClient;

class RedisConn
{
    private EnvConnector $connector;
    public PredisClient $redis;
    private \Monolog\Logger $logger;

    public function __construct()
    {
        $this->logger = LoggerFactory::create(session_id());

        try {
            $this->connector = new EnvConnector();
            $this->redis = new PredisClient($this->connector->getRedisConnectionData());
            $this->logger->info('Redis-подключение установлено успешно', [
                'connectionData' => $this->connector->getRedisConnectionData()
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Ошибка при подключении к Redis', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}
