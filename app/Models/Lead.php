<?php

namespace App\Models;

use App\Services\Bitrix24;

class Lead
{
    private Bitrix24 $bitrix24;
    private mixed $protectedFields;

    public function __construct()
    {
        $this->bitrix24 = new Bitrix24($_ENV["B24_DOMAIN"], $_ENV["B24_ID"], $_ENV["B24_HASH"]);
    }

    public function get(int $id): object
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
}
