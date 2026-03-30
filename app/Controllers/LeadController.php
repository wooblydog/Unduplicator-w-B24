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
        $this->neededKeys = [
            'ID',
            'NAME',
            'SECOND_NAME',
            'LAST_NAME',
            'STATUS_ID',
            'DATE_CREATE',
            'UF_CRM_1668339568358',
            'UF_CRM_1727328936',
            'UF_CRM_1668352823231',
            'UF_CRM_1635751283979'
        ];
    }

   public function handle(array $request): void
   {
       $this->logger->notice("");
       $leadId = (int)($request['data']['FIELDS']['ID'] ?? 0);
       if ($leadId <= 0) {
           $this->logger->error('Некорректный ID лида', ['request' => $request]);
           return;
       }
       $this->logger->notice("Входящий лид",$leadId);

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
               $this->logger->notice('Дубли не найдены', ['leadId' => $leadId, ]);
               return;
           }

           $duplicates = $this->lead->getAll($dupeIds);
           $nonConverted = $this->filterNonConvertedLeads($duplicates);
           $lead = array_intersect_key($lead, array_flip($this->neededKeys));

           if (empty($nonConverted)) {
               $this->logger->notice("Дубли не найдены", ['leadId' => $leadId]);
               return;
           }

           $this->selector->setRules($this->rules);

           
           $result = $this->selector->chooseMainLead($nonConverted, $lead);

           $mainId = $result['mainLead']['ID'] ?? null;
           $toMerge = $result['leadsToMerge'] ?? [];

           //Test logs
            if (!empty($result)){
                $this->logger->info("Результат работы поиска основного лида", $result);
            }
           //End of test logs

           if (!$mainId || empty($toMerge)) {
               $this->logger->warning('Не удалось определить основного лида', ['mainId' => $mainId, 'toMerge' => $toMerge]);
               return;
           }

       }
       catch (\Throwable $e) {
           $this->logger->error('Критическая ошибка обработки дублей', [
               'leadId' => $leadId,
               "lead" => $lead ?? null,
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