<?php

namespace App\Rules;

/**
 * Правило для проверки "Записи" по диаграмме
 * Если "Дата время записи" в прошлом тогда новый, иначе старый
*/
class AppointmentRule implements LeadRuleInterface
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

    /**
     * @param object $oldLead
     * @return int
     *
     * Подсчитывает баллы на основании поля "Дата и время приема".
     * 1. Если у лида запись в прошлом, тогда он становится токисчным и не должен стать основным, поэтому -9999
     * 2. Если запись в будущем, то это наиболее подходящий лид, поэтому 2
     */
    public function getScore(object $oldLead): int
    {
        $appointmentTime = $oldLead->UF_CRM_1668339568358 ?? $oldLead->ufCrm1668339568358 ?? null;

        if (empty($appointmentTime)) return 0;

        try {
            $appointment = new \DateTimeImmutable($appointmentTime);
            $now = new \DateTimeImmutable();

            if ($appointment < $now) return -9999;

            if ($appointment > $now) return 2;

            return 0;

        } catch (\Exception $e) {
            return 0;
        }
    }
}