<?php

namespace App\Rules;

use App\Services\Logger;

class BlacklistRule implements LeadRuleInterface
{
    private int $blacklistStatusId = 29;

    public function preferOldLead(object|array $oldLead, object|array $newLead): bool
    {
        $result = ($oldLead['STATUS_ID'] ?? null) == $this->blacklistStatusId;
        return $result;
    }
}
