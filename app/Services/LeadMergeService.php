<?php

namespace App\Services;

class LeadMergeService
{
    private Bitrix24 $bitrix;

    public function __construct($bitrix)
    {
        $this->bitrix = $bitrix;
    }

    //TODO при обновлении надо писать названия лидов которые были смержены в отдельный комментарий
    public function merge(int $sourceId, int $targetId): array
    {
        $this->transferCalls($sourceId, $targetId);
        $this->transferComments($sourceId, $targetId);
        $this->transferActivities($sourceId, $targetId);

        return $this->mergeLeadData($sourceId, $targetId);
    }

    //TODO методы ниже должны быть приватными
    public function transferCalls(int $from = null, int $to = null): void {}
    public function transferComments(int $from = null, int $to = null): void
    {
        $comments = $this->getComments(1242007);

        dd($comments);
    }
    public function transferActivities(int $from = null, int $to = null): void
    {
        $activities = $this->getActivities(1242007);

        dd($activities);
        
        //SMS
        $fields = [
            "fields" => [
                "OWNER_TYPE_ID" => 1, //тип сущности
                "OWNER_ID" => 754688, // id сущности
                "TYPE_ID" => 6, // кастом (надо добавить PROVIDER поля)
                "SUBJECT" => "Тест тест тест", // для сообщений мимо
                "SETTINGS" => [
                    "DISABLE_SENDING_MESSAGE_COPY" => "Y"
                ],
                "COMMUNICATIONS" => [
                    "VALUE" => "+79999999999", // номер лида
                    "ENTITY_ID" => 754688, // id сущности
                    "ENTITY_TYPE_ID" => 1 // тип сущности
                ],
                "COMPLETED"  => "Y",
                "DESCRIPTION" => "тест деск", // содержание смски
                "PROVIDER_ID"  => "CRM_SMS",
                "PROVIDER_TYPE_ID"  => "SMS",
            ]
        ];


    }
    public function mergeLeadData(int $from = null, int $to = null): array
    {
        return [];
    }
    private function getCalls() {}

    private function getComments($id)
    {
        $filter = [
            "ID" => $id,
            "entityType" => "lead",
        ];

        return $this->bitrix->getEntityComments($filter);
    }

    private function getActivities($id)
    {
        return $this->bitrix->getLeadActivities($id);
    }
}
