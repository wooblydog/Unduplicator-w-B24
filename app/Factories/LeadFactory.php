<?php

namespace App\Factories;

use App\Models\CallModel;
use App\Services\Lead\Lead;

class LeadFactory
{
    private CallModel $callModel;

    public function __construct(CallModel $callModel)
    {
        $this->callModel = $callModel;
    }

    public function createFromId(int $id): ?Lead
    {
        $leadData = $this->callModel->getLead($id);
        return $leadData ? new Lead($leadData) : null;
    }

    public function createFromPhone(string $phone): array
    {
        $ids = $this->callModel->getDuplicatesList($phone);
        $leads = [];

        foreach ($ids as $id) {
            $leadData = $this->callModel->getLead((int)$id);
            if ($leadData) {
                $leads[] = $leadData;
            }
        }

        return $leads;
    }
}
