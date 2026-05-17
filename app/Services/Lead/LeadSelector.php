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
        $bestLead = $newLead;
        $bestScore = 0;
        $duplicates[] = $newLead;

        foreach ($duplicates as $oldLead) {
            $score = 0;

            foreach ($this->rules as $rule) {
                if ($rule->preferOldLead($oldLead, $newLead)) {
                    $score++;
                }
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestLead = $oldLead;
            } elseif ($score === $bestScore && $score > 0) {
                if (strtotime($oldLead->DATE_CREATE ?? '1970-01-01') < strtotime($bestLead->DATE_CREATE ?? '1970-01-01')) {
                    $bestLead = $oldLead;
                }
            }
        }

        return $bestLead;
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

        $fullBestLead = $this->leadRepo->get($bestLead->ID);
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
                'Id' => $duplicateData['MainLead']['ID'],
                'Uid' => $duplicateData['MainLead']['UF_CRM_1726815456024'] ?? '',
            ],
            'Duplicates' => []
        ];

        foreach ($duplicateData['DuplicateFullData'] as $duple) {
            $preparedData['Duplicates'][] = [
                'Id' => $duple->ID,
                'Uid' => $duple->UF_CRM_1726815456024 ?? '',
            ];
        }
        return $preparedData;
    }
}
