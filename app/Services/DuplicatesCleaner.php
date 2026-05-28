<?php

namespace App\Services;

use App\Models\Lead;

class DuplicatesCleaner
{
    private Logger $logger;
    private array $protectedFields;
    private Lead $lead;
    /**
     * @var array|string[]
     */
    private array $blockedFields;

    public function __construct()
    {
        $this->logger = new Logger();
        $this->lead = new Lead();

        $this->protectedFields = [
            'NAME',
            'SECOND_NAME',
            'LAST_NAME',
            'STATUS_ID',
            'UF_CRM_1668339568358', // Дата и время приема
            'UF_CRM_1727328936',    // Диагноз
            'UF_CRM_1668352823231', // Возраст
            'UF_CRM_1635751283979', // Город
            'UF_CRM_1726815456024', // Табличный идентификатор
        ];
        $this->blockedFields = [
            // 'PHONE', Временно убрал, чтобы номер тф переносился, т.к. конфликт из-за того что не перенесся (там "+" появился)
            'EMAIL',
            'TITLE',
            'STATUS_ID'
        ];
    }

    public function clearDuplicates(array $leadsData): void
    {
        $mainLeadData      = $leadsData["MainLead"];
        $duplicateFullData = $leadsData["DuplicateFullData"];
        $duplicateIds      = $leadsData["leadsToMerge"];
        $mainLeadId        = (int)$mainLeadData['ID'];

        $this->normalizeMainLeadUtmFields($mainLeadData);

        $allLeadsData = [$mainLeadData];
        foreach ($duplicateFullData as $dup) {
            if (is_object($dup)) {
                $allLeadsData[] = (array)$dup;
            } elseif (is_array($dup)) {
                $allLeadsData[] = $dup;
            }
        }

        $this->enrichMainLead($mainLeadId, $allLeadsData);

        $this->copyAllFieldsToDuplicates($mainLeadData, $duplicateIds);
    }

    private function enrichMainLead(int $mainLeadId, array $allLeadsData): void
    {
        $updateFields = [];

        foreach ($this->protectedFields as $field) {
            $bestValue = $this->findBestValue($allLeadsData, $field);
            $currentValue = $allLeadsData[0][$field] ?? null;
            if ($bestValue !== null && $bestValue !== '' && $bestValue !== $currentValue) {
                $updateFields[$field] = $bestValue;
            }
        }

        if (!empty($updateFields)) {
            $result = $this->lead->update($mainLeadId, $updateFields);

            if (isset($result->error_description)) {
                $this->logger->error("Ошибка обогащения основного лида {$mainLeadId}", $result);
            } else {
                $this->logger->info("Основной лид {$mainLeadId} успешно обогащён");
            }
        }
    }

    private function copyAllFieldsToDuplicates(array $mainLeadData, array $duplicateIds): void
    {
        $fieldsToCopy = [];

        foreach ($mainLeadData as $field => $value) {
            if (in_array($field, $this->blockedFields)) {
                continue;
            }

            if (str_starts_with($field, 'PARENT_ID_')) {
                $fieldsToCopy[$field] = "";
                continue;
            }


            $fieldsToCopy[$field] = is_null($value) ? '' : $value;
        }
        foreach ($duplicateIds as $dupId) {
            $result = $this->lead->update($dupId, $fieldsToCopy);

            if (isset($result->error_description)) {
                $this->logger->error("Ошибка копирования в дубликат {$dupId}");
            }
        }
    }

    private function normalizeMainLeadUtmFields(array &$mainLeadData): void
    {
        $utmFields = ['UTM_SOURCE', 'UTM_MEDIUM', 'UTM_CAMPAIGN', 'UTM_CONTENT', 'UTM_TERM'];

        foreach ($utmFields as $field) {
            if (!array_key_exists($field, $mainLeadData)) {
                $mainLeadData[$field] = '';
                continue;
            }

            if ($mainLeadData[$field] === null || $mainLeadData[$field] === false) {
                $mainLeadData[$field] = '';
            }
        }
    }

    private function findBestValue(array $leadsData, string $field): mixed
    {
        foreach ($leadsData as $lead) {
            if (is_object($lead)) {
                $lead = (array)$lead;
            }

            $value = $lead[$field] ?? null;

            if (is_array($value)) {
                if (!empty(array_filter($value, fn($v) => $v !== null && $v !== ''))) {
                    return $value;
                }
            } elseif ($value !== null && $value !== '' && $value !== '0' && $value !== 0) {
                return $value;
            }
        }

        return $leadsData[0][$field] ?? '';
    }
}
