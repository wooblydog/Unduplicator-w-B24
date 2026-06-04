<?php

namespace App\Models;

use App\Services\Bitrix24;
use App\Services\Logger;

class Lead
{
    private Bitrix24 $bitrix24;
    private mixed $protectedFields;
    private Logger $logger;

    public function __construct()
    {
        $this->bitrix24 = new Bitrix24($_ENV["B24_DOMAIN"], $_ENV["B24_ID"], $_ENV["B24_HASH"]);
        $this->logger = new Logger();
    }

    public function get(int $id): ?object
    {
        return $this->bitrix24->getLead($id);
    }

    public function getAll($ids): array
    {
        if (empty($ids)) return [];

        $all = [];
        $start = 0;

        while ($start !== null) {
            $page = $this->bitrix24->getLeads(
                ["@ID" => $ids],
                ["*", 'UF_CRM_1668339568358', 'UF_CRM_1727328936', 'UF_CRM_1668352823231', 'UF_CRM_1635751283979', 'UF_CRM_1726815456024',],
                $start
            );

            $all = array_merge($all, $page->result ?? []);
            $start = $page->next ?? null;
        }

        return $all;
    }

    public function getDuplicatesByPhone(array $phones, int $excludeId): array
    {
        $duplicates = $this->bitrix24->searchDuplicate("PHONE", "LEAD", $phones);
        $leads = $duplicates->LEAD ?? [];

        return array_filter($leads, fn($id) => $id !== $excludeId);
    }

    public function update($leadId, $fields)
    {
        return $this->bitrix24->updateLead($leadId, $fields);
    }

    public function merge($ids)
    {
        return $this->bitrix24->mergeLeads($ids);
    }

    public function sendDataToTable(array $preparedData): void
    {
        try {
            $mainLeadGuid = $preparedData['MainLead']['Uid'];
            $mainLeadId = $preparedData['MainLead']['Id'];

            $this->logger->info("Отправка в таблицу", $preparedData);

            if ($mainLeadGuid == "00000000-0000-0000-0000-000000000000" || empty($mainLeadGuid)) {
                $this->logger->info("Пропускаю отправку данных в таблицу. MainLead $mainLeadId с пустым табличным идентификатором.");
                return;
            }

            $jsonData = json_encode($preparedData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json',],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_URL => $_ENV["UD_TABLE_URL"],
                CURLOPT_POSTFIELDS => $jsonData,
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            curl_close($ch);

            if ($httpCode < 200 || $httpCode >= 300) {
                $this->logger->error("Ошибка отправки данных в таблицу: $httpCode — $response");
                return;
            }
        } catch (\Exception $ex) {
            $this->logger->error("Вызвано исключение при отправки данных в таблицу: " . $ex->getMessage());
        }
    }
}
