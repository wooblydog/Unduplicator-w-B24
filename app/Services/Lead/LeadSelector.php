<?php

namespace App\Services\Lead;

use App\Services\Logger;

class LeadSelector
{
    private array $rules;
    private Logger $logger;

    public function __construct()
    {
        $this->logger = new Logger();
    }

    public function setRules(array $rules): void
    {
        $this->rules = $rules;
    }

    public function chooseMainLead1(array $leads, object $newLead): array
    {
        $mainLead = $newLead;
        foreach ($leads as $lead) {
            foreach ($this->rules as $rule) {
                if ($rule->applies($lead, $newLead)) {
                    $mainLead = $lead;
                    break 2;
                }
            }
        }

        $duplicates = array_filter($leads, fn($lead) => $lead['ID'] !== $mainLead['ID']);
        $this->logger->notice('Выбор завершён', [
            'mainLeadId' => $mainLead['ID'],
            'duplicateIds' => array_column($duplicates, 'ID')
        ]);

        return [
            'main' => $mainLead,
            'duplicates' => array_values($duplicates)
        ];
    }

    public function chooseMainLead(array $duplicates, $newLead): array
    {
        $mainLead = $newLead;
        $mergeTarget = $newLead;

        foreach ($duplicates as $oldLead) {
            foreach ($this->rules as $rule) {
                if ($rule->preferOldLead($oldLead, $newLead)) {
                    $mainLead = $oldLead;
                    $mergeTarget = $oldLead;
                    break 2;
                }
            }
        }

        $toMerge = array_filter($duplicates, fn($l) => $l->ID !== $mergeTarget->ID);
        if ($mainLead->ID !== $newLead->ID) {
            $toMerge[] = $newLead;
        }

        return [
            'mainLead'      => $mergeTarget,
            'leadsToMerge' => array_values($toMerge),
        ];
    }
}
