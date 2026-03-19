<?php

namespace App\Rules;

use App\Rules\LeadRuleInterface;

class IsCallTouchRule implements LeadRuleInterface
{
    private int $oneDayPerSec = 60 * 60 * 24;
    private string $dateRegisterField = "UF_CRM_1668339568358";

    public function applies(array $lead, array $newLead): bool
    {
        $leadCreatedDiff = abs(strtotime($lead['DATE_CREATE']) - strtotime($newLead['DATE_CREATE']));
        $leadRegisterDiff = isset($lead[$this->dateRegisterField])
            ? abs(strtotime($newLead['DATE_CREATE']) - strtotime($lead[$this->dateRegisterField]))
            : PHP_INT_MAX;

        return $leadCreatedDiff <= $this->oneDayPerSec || $leadRegisterDiff <= $this->oneDayPerSec;
    }
}