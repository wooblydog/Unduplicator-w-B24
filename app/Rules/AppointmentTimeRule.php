<?php

namespace App\Rules;

/**
 * Правило для проверки "Записи" по диаграмме
 * Если "Дата время записи" в прошлом тогда новый, иначе старый
*/
class AppointmentTimeRule implements LeadRuleInterface
{
    public function decide(object $oldLead, object $newLead): ?bool
    {
        $appointmentTime = $oldLead->UF_CRM_1668339568358 ?? $oldLead->ufCrm1668339568358 ?? null;

        try {
            $appointment = new \DateTimeImmutable($appointmentTime);
            $now = new \DateTimeImmutable();

            if ($appointment < $now) {
                return false;
            }

            return true;

        } catch (\Exception $e) {
            return false;
        }
    }
}