<?php

namespace App\Rules;

interface LeadRuleInterface
{
    /**
     * @param object $oldLead  старый лид (дубликат)
     * @param object $newLead  новый лид (триггерный)
     * @return bool|null true - победил Старый, false - победил Новый, null - проверяем дальше
     */
    public function decide(object $oldLead, object $newLead): ?bool;
}