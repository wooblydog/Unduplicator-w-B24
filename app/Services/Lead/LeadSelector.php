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
    if (is_array($newLead)) {
        $newLead = (object)$newLead;
    }

    $mainLead = $newLead;
    $mergeTarget = $newLead;

    foreach ($duplicates as $oldLead) {
        if (is_array($oldLead)) {
            $oldLead = (object)$oldLead;
        }

        foreach ($this->rules as $rule) {
            if ($rule->preferOldLead($oldLead, $newLead)) {
                $mainLead = $oldLead;
                $mergeTarget = $oldLead;
                break 2;
            }
        }
    }

    $toMerge = array_filter($duplicates, function ($l) use ($mergeTarget) {
        if (is_array($l)) $l = (object)$l;
        if (is_array($mergeTarget)) $mergeTarget = (object)$mergeTarget;
        return $l->ID !== $mergeTarget->ID;
    });

    if ($mainLead->ID !== $newLead->ID) {
        $toMerge[] = $newLead;
    }

    $duplicateIDs = [$mainLead->ID ?? null];

    foreach ($toMerge as $item) {
        if (is_array($item)) $item = (object)$item;
        $duplicateIDs[] = $item->ID ?? null;
    }

    return [
        'mainLead'      => (array)$mergeTarget,
        'leadsToMerge'  => array_map(fn($l) => (array)$l, array_values($toMerge)),
        'duplicateIds'  => array_filter($duplicateIDs),
    ];
}
}
