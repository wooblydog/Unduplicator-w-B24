<?php

namespace App\Controllers;

use App\Models\Lead;

use App\Rules\AppointmentInFutureRule;
use App\Rules\CreatedLessThan24hRule;

use App\Rules\HasAppointmentRule;
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

    public function __construct()
    {
        $this->logger = new Logger();
        $this->lead = new Lead();
        $this->selector = new LeadSelector();
        $this->rules = [
            new CreatedLessThan24hRule(),
            new AppointmentInFutureRule(),
            new HasAppointmentRule(),
        ];
        $this->duplicatesCleaner = new DuplicatesCleaner();
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
//               $preparedData = $this->selector->prepareDataForTableFromResult($result);
//               $this->sendDataToTable($preparedData);
               dump($this->lead->merge($result['DuplicateData']));

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

    private function sendDataToTable(array $preparedData)
    {
        try {
            if (empty($preparedData)) {
                $this->logger->error("Ошибка отправки данных в таблицу. Список лидов пуст");
                return;
            }

            $mainLeadGuid = $preparedData['MainLead']['Uid'] ?? '';
            $mainLeadId = $preparedData['MainLead']['Id'] ?? 0;

            if (empty($mainLeadGuid)) {
                $this->logger->info("Пропускаю отправку данных в таблицу. MainLead {$mainLeadId} с пустым табличным идентификатором.");
                return;
            }

            $jsonData = json_encode($preparedData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json',],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_URL => $_ENV["UD_TABLE_URL"],
                CURLOPT_POSTFIELDS => $jsonData,
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            curl_close($ch);

            if ($httpCode < 200 || $httpCode >= 300) {
                $this->logger->info("Не удалось отправить данные в таблицу: {$httpCode}");
                $this->logger->error("Ошибка отправки данных в таблицу: {$httpCode} — {$response}");
                return;
            }
        } catch (\Exception $ex) {
            $this->logger->error("Вызвано исключение при отправки данных в таблицу: " . $ex->getMessage());
        }
    }
}
