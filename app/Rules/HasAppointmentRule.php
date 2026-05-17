<?php

namespace App\Rules;

class HasAppointmentRule implements LeadRuleInterface
{
    public function preferOldLead(object $oldLead, object $newLead): bool
    {
        return !empty($oldLead->UF_CRM_1668339568358);
    }
}
