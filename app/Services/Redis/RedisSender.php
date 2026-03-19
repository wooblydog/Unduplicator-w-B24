<?php

namespace App\Services\Redis;

use App\Factories\LoggerFactory;

class RedisSender
{
    private RedisConn $redisCon;
    private \Predis\Client $redis;
    private \Monolog\Logger $logger;

    public function __construct()
    {
        $this->logger = LoggerFactory::create(session_id());

        try {
            $this->redisCon = new RedisConn();
            $this->redis = $this->redisCon->redis;
            $this->logger->info('Подключение к Redis установлено для RedisSender');
        } catch (\Throwable $e) {
            $this->logger->error('Ошибка при инициализации RedisSender', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function send(string $message): void
    {
        try {
            $this->redis->xadd('MergeCommand', ['data' => $message]);
            $this->logger->notice('Сообщение отправлено в Redis', ['channel' => 'MergeCommand', 'message' => $message]);
        } catch (\Throwable $e) {
            $this->logger->error('Ошибка отправки сообщения в Redis', [
                'channel' => 'MergeCommand',
                'message' => $message,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}
