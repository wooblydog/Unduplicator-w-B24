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


    public function __construct()
    {
        $this->logger = new Logger();
        $this->lead = new Lead();
        $this->selector = new LeadSelector();
        $this->conflictWriter = new ConflictWriter();
    }

    public function init(): void
    {
        $failedTries = json_decode(file_get_contents($this->defaultFilePath), true);
        $this->retryMerge($failedTries);
    }

    //TODO посмотреть как будет работать без clearDuplicates
    private function retryMerge(array $failedTries)
    {
        foreach ($failedTries as $failedTry) {
            $ID = $failedTry['ID'];
            $DuplicateIDs = $failedTry['message'];

            try {
                $leadsToGet = $this->lead->getAll($DuplicateIDs);

                try {
                    if ($failedTry['attempts'] == 3){
                        $this->logger->conflict("https://{$_ENV["B24_DOMAIN"]}/crm/lead/merge/?id=" . implode(",",$failedTry['message']));
                        $this->conflictWriter->cleanup();
                        continue;
                    }
                    $this->conflictWriter->addAttempt($ID);
//                    $mergeResult = $this->lead->merge($DuplicateIDs);

                    try {
                        $result['MainLead'] = (array)$this->lead->get($DuplicateIDs[0]); // Перед отправкой актуализируется инфа о резлультате слияния
                        try {
//                            $this->lead->sendDataToTable($this->selector->prepareDataForTableFromResult($result));
                        } catch (\Exception $e) {
                            $this->logger->error('Не удалость отправить в таблицу', $e->getMessage());
                        }
                    } catch (\Exception $e) {
                        $this->logger->error('Не удалость получить лида перед слиянием', $e->getMessage());
                    }
                } catch (\Exception $e) {
                    $this->logger->error('Не удалось смержить лиды', $e->getMessage());
                }
            } catch (\Exception $exception) {
                $this->logger->error('Не удалось получить лидов перед слиянием', $exception->getMessage());
            }
        }
    }
}