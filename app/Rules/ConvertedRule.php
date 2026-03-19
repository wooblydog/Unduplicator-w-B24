<?php

namespace App\Rules;

use App\Factories\LoggerFactory;
use App\Rules\LeadRuleInterface;

class ConvertedRule implements LeadRuleInterface
{
    private \Monolog\Logger $logger;

    public function __construct()
    {
        $this->logger = LoggerFactory::create(session_id());
    }

    public function applies(array $lead, array $newLead): bool
    {
        $result = ($lead['STATUS_ID'] ?? null) === 'CONVERTED';

        $this->logger->notice('Проверка правила ConvertedRule', [
            'leadId' => $lead['ID'] ?? null,
            'statusId' => $lead['STATUS_ID'] ?? null,
            'applies' => $result
        ]);

        return $result;
    }
}
