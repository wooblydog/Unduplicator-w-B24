<?php

namespace App\Models;

use App\Services\Bitrix24;

class Lead
{
    private Bitrix24 $bitrix24;

    public function __construct()
    {
        $this->bitrix24 = new Bitrix24($_ENV["B24_DOMAIN"], $_ENV["B24_ID"], $_ENV["B24_HASH"]);
    }

    public function create($data): int|bool
    {
        $result = $this->bitrix24->addLead($_ENV["B24_RESPONIBLE_ID"], $data);
        return is_int($result) ? $result : false;
    }

    public function get(int $id): object
    {
        return $this->bitrix24->getLead($id);
    }

    //TODO в боевом режиме починить
    public function update($id, $data): bool
    {
        return false;
        return $this->bitrix24->updateLead($id, $data);
    }

    //TODO в боевом режиме починить
    // Исправить на объединение, т.к. нужно хранить историю
    public function delete($id)
    {
        return false;
        return $this->bitrix24->deleteLead($id);
    }
    public function getAll($ids): array
    {
        if (empty($ids)) return [];

        $all = [];
        $start = 0;

        while ($start !== null) {
            $page = $this->bitrix24->getLeads(
                ["@ID" => $ids],
                [
                    "STATUS_ID",
                    "DATE_CREATE",
                    "NAME",
                    "SECOND_NAME",
                    "LAST_NAME",
                    "UF_CRM_1668339568358", //Дата время записи
                    "UF_CRM_1727328936",    // Диагноз
                    "UF_CRM_1668352823231", // Год рождения
                    "UF_CRM_1635751283979",  // Город
                ],
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
}