<?php

namespace App\Rules;

use DateTimeImmutable;

class CreatedLessThan24hRule implements LeadRuleInterface
{
    private const ONE_DAY_SECONDS = 86400;

    public function preferOldLead(object|array $oldLead, object|array $newLead): bool
    {
        $created = strtotime($oldLead->DATE_CREATE ?? 'now');
        $now     = time();

        return ($now - $created) <= self::ONE_DAY_SECONDS;
    }
}
