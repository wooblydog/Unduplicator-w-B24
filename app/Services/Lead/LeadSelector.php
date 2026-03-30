<?php

namespace App\Services\Lead;

class LeadSelector
{
    private array $rules;

    public function __construct()
    {
    }

    public function setRules(array $rules): void
    {
        $this->rules = $rules;
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

        $duplicateIDs = array_merge([$mainLead->ID], array_column($toMerge, 'ID'));

        return [
            'mainLead'      => $mergeTarget,
            'leadsToMerge' => array_values($toMerge),
            'duplicateIds' => $duplicateIDs,
        ];
    }
}
