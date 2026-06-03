<?php

namespace App\Rules;

/**
 * Правило для проверки наличия записи по диаграмме
 * Если у лида заполнено поле "Дата время приема" тогда новый, иначе продолжаем проверку
 */
class HasAppointmentRule implements LeadRuleInterface
{
    public function decide(object $oldLead, object $newLead): ?bool
    {
        $appointmentTime = $oldLead->UF_CRM_1668339568358 ?? $oldLead->ufCrm1668339568358 ?? null;

        if (empty($appointmentTime)) {
            return false;
        }

        return null;
    }
}
