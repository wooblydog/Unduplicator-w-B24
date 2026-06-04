<?php

namespace App\Services;

use App\Models\Lead;
use App\Services\Lead\LeadSelector;

class RetryFailsService
{
    private Logger $logger;
    private Lead $lead;
    protected string $defaultFilePath = __DIR__ . '/../../fails/mergeFails.json';
    private LeadSelector $selector;
    private ConflictWriter $conflictWriter;
    private $duplicatesCleaner;


    public function __construct()
    {
        $this->logger = new Logger('retry.log');
        $this->lead = new Lead();
        $this->selector = new LeadSelector();
        $this->conflictWriter = new ConflictWriter();
        $this->duplicatesCleaner = new DuplicatesCleaner();
    }

    public function init(): void
    {
        $failedTries = json_decode(file_get_contents($this->defaultFilePath), true);
        $this->retryMerge($failedTries);
    }

    private function retryMerge(array $failedTries): void
    {
        foreach ($failedTries as $failedTry) {
            $ID = $failedTry['ID'];
            $DuplicateIDs = $failedTry['message']['DuplicateData'];

            try {
                if ($failedTry['attempts'] >= 3) {
                    $this->logger->conflict("https://{$_ENV["B24_DOMAIN"]}/crm/lead/merge/?id=" . implode(",", $DuplicateIDs));
                    $this->conflictWriter->removeById($ID);
                    continue;
                }

                $this->duplicatesCleaner->clearDuplicates($failedTry['message']);
                $mergeResult = $this->lead->merge($DuplicateIDs);

                if (isset($mergeResult->result->STATUS) && $mergeResult->result->STATUS == "SUCCESS") {
                    $this->conflictWriter->removeById($ID);
                } else {
                    $this->conflictWriter->addAttempt($ID);
                    continue;
                }

                try {
                    $failedTry['message']['MainLead'] = (array)$this->lead->get($ID);

                    try {
                        $this->lead->sendDataToTable($this->selector->prepareDataForTableFromResult($failedTry['message']));
                    } catch (\Exception $e) {
                        $this->logger->error('Не удалость отправить в таблицу', $e->getMessage());
                    }

                } catch (\Exception $e) {
                    $this->logger->error('Не удалость получить лида перед слиянием', $e->getMessage());
                }

            } catch (\Exception $e) {
                $this->logger->error('Не удалось смержить лиды', $e->getMessage());
                $this->conflictWriter->addAttempt($ID);
            }
        }
    }
}