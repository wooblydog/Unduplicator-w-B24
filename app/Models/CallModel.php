<?php

namespace App\Models;

use App\Factories\LoggerFactory;
use Bitrix24\SDK\Core\Exceptions\BaseException;
use Bitrix24\SDK\Core\Exceptions\TransportException;
use Bitrix24\SDK\Services\ServiceBuilder;

class CallModel
{
    private BitrixModel $bitrixModel;
    private ServiceBuilder $connect;
    private \Monolog\Logger $logger;

    public function __construct()
    {
        $this->logger = LoggerFactory::create(session_id());

        $this->bitrixModel = new BitrixModel();
        $this->connect = $this->bitrixModel->connect();
        $this->logger->info('CallModel инициализирован');
    }

    /**
     * @throws TransportException
     * @throws BaseException
     */
    public function getLead(int $id): ?array
    {
        $this->logger->info('Получение лида по ID', ['id' => $id]);
        $resp = $this->connect->core->call('crm.lead.get', ['ID' => $id]);
        $result = $resp->getResponseData()->getResult();
        $this->logger->notice('Лид получен', ['id' => $id, 'lead' => $result]);
        return $result;
    }

    /**
     * @throws TransportException
     * @throws BaseException
     */
    public function getDuplicatesList(string $phone): ?array
    {
        $this->logger->info('Получение списка дубликатов по телефону', ['phone' => $phone]);
        $resp = $this->connect->core->call('crm.duplicate.findbycomm', [
            'entity_type' => 'LEAD',
            'type' => 'PHONE',
            'values' => [$phone]
        ]);
        $result = $resp->getResponseData()->getResult()['LEAD'];
        $this->logger->info('Дубликаты найдены', ['phone' => $phone, 'duplicates' => $result]);
        return $result;
    }

}
