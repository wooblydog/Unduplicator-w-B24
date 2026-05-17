<?php

namespace App\Rules;

interface LeadRuleInterface
{
    /**
     * @param object $oldLead  старый лид (дубликат)
     * @param object $newLead  новый лид (триггерный)
     * @return bool
     */
    public function preferOldLead(object $oldLead, object $newLead): bool;
}