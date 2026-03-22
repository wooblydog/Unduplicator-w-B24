<?php

namespace App\Controllers;

use App\Models\Lead;

use App\Rules\AppointmentInFutureRule;
use App\Rules\CreatedLessThan24hRule;

use App\Services\Lead\LeadSelector;
use App\Services\Logger;

class LeadController
{
    private Logger $logger;
    private Lead $lead;
    private LeadSelector $selector;
    private array $rules;

    public function __construct()
    {
        $this->logger = new Logger();
        $this->lead = new Lead();
        $this->selector = new LeadSelector();
        $this->rules = [
            new CreatedLessThan24hRule(),
            new AppointmentInFutureRule(),
        ];
    }

    //TODO response убрать, он для теста
   public function handle(array $request): void
   {
       $leadId = (int)($request['id'] ?? 0);
       if ($leadId <= 0) {
           $this->logger->error('Некорректный ID лида', ['request' => $request]);
           return;
       }

       try {
           $lead = (array)$this->lead->get($leadId);
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
               $this->logger->notice('Дубли не найдены', ['leadId' => $leadId]);
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

           $mainId = $result['mergeInto']['ID'] ?? null;
           $toMerge = $result['duplicatesToMerge'] ?? [];
           ///insteadof below

           //Test logs
           if (!empty($mainLead)) {
               $this->logger->notice("Основной лид: ", $result['mergeInto']);
           }

           if (!empty($leadsToMerge)) {
               $this->logger->notice("Дубликаты: ", $result['duplicatesToMerge']);
           }
           //End of test logs

           if (!$mainId || empty($toMerge)) {
               $this->logger->warning('Не удалось определить основного лида', ['leadId' => $leadId]);
               return;
           }

           foreach ($leadsToMerge as $leadToMerge) {
               $this->lead->delete($leadToMerge["ID"]); ///????
               unset($leadToMerge["ID"]);
               $this->lead->update($mainId, $leadToMerge);
           }
       }
       catch (\Throwable $e) {
           $this->logger->error('Критическая ошибка обработки дублей', [
               'leadId' => $leadId,
               'error'  => $e->getMessage(),
               'trace'  => $e->getTraceAsString()
           ]);
       }
    }


    public function filterNonConvertedLeads($leads): array
    {
        return array_filter($leads, function ($lead) {
            return $lead->STATUS_ID !== 'CONVERTED';
        });
    }
}