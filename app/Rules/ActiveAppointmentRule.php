<?php

namespace App\Rules;

use App\Factories\LoggerFactory;
use App\Rules\LeadRuleInterface;

class ActiveAppointmentRule implements LeadRuleInterface
{
    private \Monolog\Logger $logger;

    public function __construct()
    {
        $this->logger = LoggerFactory::create(session_id());
    }

    public function applies(array $lead, array $newLead): bool
    {
        $value = $lead['UF_CRM_1668339568358'] ?? null;
        $result = ($value !== null && $value !== '' && $value !== 0 && $value !== '0');

        $this->logger->notice('Проверка правила ActiveAppointmentRule', [
            'leadId' => $lead['ID'] ?? null,
            'hasActiveAppointment' => $result,
            'value' => $value,
        ]);

        return $result;
    }

}
