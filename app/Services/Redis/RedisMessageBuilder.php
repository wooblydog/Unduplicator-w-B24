<?php

namespace App\Services\Redis;

use App\Factories\LoggerFactory;

class RedisMessageBuilder
{
    private array $leads;
    private array $message;
    private string $emptyUID = '00000000-0000-0000-0000-000000000000';
    private \Monolog\Logger $logger;

    public function __construct(array $leads)
    {
        $this->logger = LoggerFactory::create(session_id());
        $this->leads = $leads;
    }

    public function buildMessage(): string
    {
        if(empty($this->leads['duplicates'])){
            $this->logger->notice('В сборщик сообщений пришёл только основной лид без дубликатов. Завершение работы скрипта');
            http_response_code(200);
            die();
        }
        $this->logger->info('Начало сборки сообщения для Redis', [
            'mainLeadId' => $this->leads['main']['ID'] ?? null,
            'duplicateCount' => count($this->leads['duplicates'] ?? [])
        ]);

        $duplicates = [];

        foreach ($this->leads['duplicates'] ?? [] as $duplicate) {
            $duplicates[] = [
                'ID' => $duplicate['ID'],
                'Uid' => !empty($duplicate['UF_CRM_1726815456024'])
                    ? $duplicate['UF_CRM_1726815456024']
                    : $this->emptyUID,
            ];
        }

        $this->message = [
            'url' => "https://ud-rus.ru/crm/lead/merge/?id=",
            'duplicateData' => [
                'MainLead' => [
                    'Id' => $this->leads['main']['ID'],
                    'Uid' => !empty($this->leads['main']['UF_CRM_1726815456024'])
                        ? $this->leads['main']['UF_CRM_1726815456024']
                        : $this->emptyUID,
                ],
                'Duplicates' => $duplicates
            ],
            'login' => 'service-api',
            'password' => '9Kp-uBp-8YF-9yi'
        ];

        $this->logger->info('Сообщение для Redis сформировано', ['message' => $this->message]);

        return json_encode($this->message, JSON_UNESCAPED_UNICODE);
    }
}
