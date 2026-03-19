<?php

namespace App\Rules;

interface LeadRuleInterface
{
    public function applies(array $lead, array $newLead): bool;
}