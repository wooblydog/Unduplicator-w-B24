<?php

namespace App\Services\Lead;

use App\Factories\LoggerFactory;

class LeadSelector
{
    private array $rules;
    private \Monolog\Logger $logger;

    public function __construct(array $rules)
    {
        $this->rules = $rules;
        $this->logger = LoggerFactory::create(session_id());
    }

    public function chooseMainLead(array $leads, array $newLead): array
    {
        $this->logger->notice('Начало выбора основного лида', ['newLeadId' => $newLead['ID']]);

        if (count($leads) === 1) {
            $this->logger->notice('Дублей нет, завершаем процесс', ['leadId' => $newLead['ID']]);
            die('Дублей нет, завершение программы.');
        }

        $leads = $this->filterOldConvertedLeads($leads);

        if (count($leads) === 1) {
            $this->logger->notice('Дублей нет, завершаем процесс', ['leadId' => $newLead['ID']]);
            die('Дублей нет, завершение программы.');
        }

        $mainLead = $newLead;

        foreach ($leads as $lead) {
            if ($lead['ID'] === $newLead['ID']) continue;

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

    private function filterOldConvertedLeads(array $leads): array
    {
        $this->logger->notice('Поиск качественных лидов, старше двух недель.', ['leads' => $leads]);
        $twoWeeks = 60 * 60 * 24 * 14;
        $field = 'UF_CRM_1668339568358';

        $filtered = array_filter($leads, function ($lead) use ($twoWeeks, $field) {
            $status = $lead['STATUS_ID'] ?? null;

            if ($status !== 'CONVERTED') {
                return true;
            }

            $date = $lead[$field] ?? null;
            if ($date === null || $date === '' || $date === false) {
                return false;
            }

            return (time() - strtotime($date)) <= $twoWeeks;
        });

        $this->logger->notice('Список отфильтрованных лидов', ['leads' => $filtered]);

        return array_values($filtered);
    }
}
