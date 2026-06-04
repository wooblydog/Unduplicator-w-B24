<?php

namespace App\Controllers;

use App\Models\Lead;

use App\Rules\AppointmentRule;
use App\Rules\CreatedLessThan24hRule;

use App\Services\ConflictWriter;
use App\Services\DuplicatesCleaner;
use App\Services\Lead\LeadSelector;
use App\Services\Logger;

class LeadController
{
    private Logger $logger;
    private Lead $lead;
    private LeadSelector $selector;
    private array $rules;
    private DuplicatesCleaner $duplicatesCleaner;
    private ConflictWriter $conflictWriter;

    public function __construct()
    {
        $this->logger = new Logger();
        $this->lead = new Lead();
        $this->selector = new LeadSelector();
        $this->rules = [
            new CreatedLessThan24hRule(),
            new AppointmentRule(),
        ];
        $this->duplicatesCleaner = new DuplicatesCleaner();
        $this->conflictWriter = new ConflictWriter();
    }

    public function handle(array $request): void
    {
        $this->logger->notice("");
        $leadId = (int)($request['ID'] ?? 0);
        if ($leadId <= 0) {
            $this->logger->error('Некорректный ID лида', ['request' => $request]);
            return;
        }
        $this->logger->notice("Входящий лид", $leadId);

        try {
            $lead = (array) $this->lead->get($leadId);
            if (empty($lead)) {
                $this->logger->error('Лид не найден', ['leadId' => $leadId]);
                return;
            }

            $phones = array_column($lead['PHONE'] ?? [], 'VALUE');
            if (empty($phones)) {
                $this->logger->notice('Лид без телефона', ['leadId' => $leadId]);
                return;
            }

            $dupeIds = $this->lead->getDuplicatesByPhone($phones, $leadId);
            if (empty($dupeIds)) {
                $this->logger->notice('Дубли не найдены', ['leadId' => $leadId,]);
                return;
            }
            $duplicates = $this->lead->getAll($dupeIds);
            $nonConverted = $this->filterNonConvertedLeads($duplicates);

            if (empty($nonConverted)) {
                $this->logger->notice("Дубли не найдены", ['leadId' => $leadId]);
                return;
            }

            $this->selector->setRules($this->rules);
            $result = $this->selector->chooseMainLead($nonConverted, $lead);
            $mainId = $result['MainLead']['ID'] ?? null;

            if (!empty($result)) {
                $this->duplicatesCleaner->clearDuplicates($result);
                $mergeResult = $this->lead->merge($result['DuplicateData']);
                $result['MainLead'] = (array) $this->lead->get($mainId); // Перед отправкой актуализируется инфа о резлультате слияния

                $this->lead->sendDataToTable($this->selector->prepareDataForTableFromResult($result));
                if ($mergeResult->result->STATUS == "CONFLICT"){
                    $this->conflictWriter->addConflict($result);
                    return;
                }
            }

            if (!$mainId || empty($result['leadsToMerge'])) {
                $this->logger->warning('Не удалось определить основного лида', ['mainId' => $mainId, 'toMerge' => $result['leadsToMerge']]);
                return;
            }
        } catch (\Throwable $e) {
            $this->logger->error('Критическая ошибка обработки дублей', [
                'leadId' => $leadId,
                "lead" => $lead ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    private function filterNonConvertedLeads($leads): array
    {
        return array_filter($leads, function ($lead) {
            return $lead->STATUS_ID !== 'CONVERTED';
        });
    }
}
