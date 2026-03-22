<?php

namespace App\Rules;

use DateTimeImmutable;

/**
 * Инвертированное правило записи в прошлом, для того чтобы унифицировать приоритезацию по лидам.
*/
class AppointmentInFutureRule implements LeadRuleInterface
{
    public function preferOldLead(object|array $oldLead, object|array $newLead): bool
    {
        $appointmentTime = $oldLead->UF_CRM_1668339568358 ?? null;

        if (empty($appointmentTime)) {
            return false;
        }

        $appointment = new DateTimeImmutable($appointmentTime);
        $now = new DateTimeImmutable();

        return $appointment > $now;
    }
}