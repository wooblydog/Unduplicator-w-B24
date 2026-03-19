<?php

namespace App\Services;

use App\Factories\LoggerFactory;
use Dotenv\Dotenv;

class EnvConnector
{
    private string $bxWebhook;
    private \Monolog\Logger $logger;

    private string $host;
    private string $scheme;
    private int $port;
    private int $DBID;
    private string $DBPassword;
    private string $bxMergeLink;
    private string $bxPassword;
    private string $bxLogin;

    public function __construct()
    {
        $this->logger = LoggerFactory::create(session_id());

        try {
            $dotenv = Dotenv::createImmutable(dirname(__DIR__, 2));
            $dotenv->load();

            $this->bxWebhook = $_ENV['BX_WEBHOOK'] ?? '';
            if ($this->bxWebhook) {
                $this->logger->info('BX_WEBHOOK успешно загружен');
            } else {
                $this->logger->warning('BX_WEBHOOK не найден');
            }

            $this->host = $_ENV['REDIS_HOST'] ?? '';
            $this->port = (int)($_ENV['REDIS_PORT'] ?? 0);
            $this->scheme = $_ENV['REDIS_SCHEME'] ?? '';
            $this->DBID = (int)($_ENV['REDIS_DATABASE'] ?? 0);
            $this->DBPassword = $_ENV['REDIS_PASSWORD'] ?? '';
            $this->bxMergeLink = $_ENV['BX_MERGE_URL'] ?? '';
            $this->bxPassword = $_ENV['BX_PASSWORD'] ?? '';
            $this->bxLogin = $_ENV['BX_LOGIN'] ?? '';

            $this->logger->info('EnvConnector успешно инициализирован');
        } catch (\Throwable $e) {
            $this->logger->error('Ошибка загрузки .env', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function getBxWebhook(): string
    {
        $this->logger->info('Возвращён BX_WEBHOOK');
        return $this->bxWebhook;
    }

    public function getRedisConnectionData(): array
    {
        $this->logger->info('Возвращены параметры Redis-подключения');
        return [
            'scheme' => $this->scheme,
            'host' => $this->host,
            'port' => $this->port,
            'password' => $this->DBPassword,
            'database' => $this->DBID,
        ];
    }
}
