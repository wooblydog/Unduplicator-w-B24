<?php

namespace App\Rules;

interface LeadRuleInterface
{
    /**
     * @param object|array $oldLead  старый лид (дубликат)
     * @param object|array $newLead  новый лид (триггерный)
     * @return bool
     */
    public function preferOldLead(object|array $oldLead, object|array $newLead): bool;
}