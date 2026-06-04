<?php

namespace App\Rules;

/**
 * Правило для проверки создания по диаграмме
 * Если лид создан в <24 тогда новый, иначе продолжаем проверку
 */
class CreatedLessThan24hRule implements LeadRuleInterface
{
    private const ONE_DAY_SECONDS = 86400;

    public function decide(object $oldLead, object $newLead): ?bool
    {
        $created = strtotime($oldLead->DATE_CREATE ?? 'now');
        $now = time();

        if (($now - $created) <= self::ONE_DAY_SECONDS) {
            return true;
        }

        return null;
    }

    public function getScore(object $oldLead): int
    {
        $created = strtotime($oldLead->DATE_CREATE ?? 'now');
        $now = time();

        if (($now - $created) <= self::ONE_DAY_SECONDS) {
            return 1;
        }

        return 0;
    }
}
