<?php

namespace App\Services\Lead;

use App\Models\Lead;

class LeadSelector
{
    private array $rules;
    private $leadRepo;

    public function setRules(array $rules): void
    {
        $this->rules = $rules;
        $this->leadRepo = new Lead();
    }

    public function chooseMainLead(array $duplicates, $newLead): array
    {
        if (is_array($newLead)) {
            $newLead = (object)$newLead;
        }

        foreach ($duplicates as &$dup) {
            if (is_array($dup)) {
                $dup = (object)$dup;
            }
        }
        unset($dup);

        $bestLead = $this->findBestLead($duplicates, $newLead);

        return $this->buildResult($bestLead, $newLead, $duplicates);
    }
    /**
     * Находит лучший лид по количеству сработавших правил
     */
    private function findBestLead(array $duplicates, object $newLead): object
    {
        $validOldLeads = [];

        foreach ($duplicates as $oldLead) {
            $oldLeadWins = false;

            foreach ($this->rules as $rule) {
                $decision = $rule->decide($oldLead, $newLead);

                if ($decision === true) {
                    $oldLeadWins = true;
                    break;
                }
                elseif ($decision === false) {
                    break;
                }
            }

            if ($oldLeadWins) {
                $validOldLeads[] = $oldLead;
            }
        }

        if (empty($validOldLeads)) {
            return $newLead;
        }

        if (count($validOldLeads) === 1) {
            return $validOldLeads[0];
        }

        usort($validOldLeads, function($a, $b) {
            return strtotime($a->DATE_CREATE ?? 'now') <=> strtotime($b->DATE_CREATE ?? 'now');
        });

        return $validOldLeads[0];
    }

    private function buildResult(object $bestLead, object $newLead, array $duplicates): array
    {
        $toMerge = array_filter($duplicates, fn($l) => $l->ID !== $bestLead->ID);

        if ($bestLead->ID !== $newLead->ID) {
            $toMerge[] = $newLead;
        }

        $duplicateIDs = [$bestLead->ID];

        foreach ($toMerge as $item) {
            $duplicateIDs[] = $item->ID ?? null;
        }

        $fullBestLead =  $bestLead;
        return [
            'MainLead' => (array)$fullBestLead,
            'leadsToMerge' => array_column($toMerge, 'ID'),
            'DuplicateData' => $duplicateIDs,
            'DuplicateFullData' => $toMerge,
        ];
    }

    public function prepareDataForTableFromResult(array $duplicateData)
    {
        $preparedData = [
            'MainLead' => [
                'Id'  => $duplicateData['MainLead']['ID'],
                'Uid' => $duplicateData['MainLead']['UF_CRM_1726815456024'] ?? '00000000-0000-0000-0000-000000000000',
            ],
            'Duplicates' => []
        ];

        foreach ($duplicateData['DuplicateFullData'] as $duple) {
            $preparedData['Duplicates'][] = [
                'Id'  => is_object($duple) ? $duple->ID : $duple['ID'],
                'Uid' => is_object($duple) ? ($duple->UF_CRM_1726815456024 ?? '00000000-0000-0000-0000-000000000000') : ($duple['UF_CRM_1726815456024'] ?? '00000000-0000-0000-0000-000000000000'),
            ];
        }

        return $preparedData;
    }
}
