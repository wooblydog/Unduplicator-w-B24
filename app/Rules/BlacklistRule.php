<?php

namespace App\Rules;

use App\Factories\LoggerFactory;
use App\Rules\LeadRuleInterface;

class BlacklistRule implements LeadRuleInterface
{
    private int $blacklistStatusId = 29;
    private \Monolog\Logger $logger;

    public function __construct()
    {
        $this->logger = LoggerFactory::create(session_id());
    }

    public function applies(array $lead, array $newLead): bool
    {
        $result = ($lead['STATUS_ID'] ?? null) == $this->blacklistStatusId;

        $this->logger->notice('Проверка правила BlacklistRule', [
            'leadId' => $lead['ID'] ?? null,
            'statusId' => $lead['STATUS_ID'] ?? null,
            'applies' => $result
        ]);

        return $result;
    }
}
