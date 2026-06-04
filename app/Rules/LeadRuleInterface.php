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

    /**
     * Возвращает количество баллов, которые получает старый лид.
     * Может возвращать отрицательные значения для блокировки лида.
     */
    public function getScore(object $oldLead): int;
}