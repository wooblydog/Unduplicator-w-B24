<?php

namespace App\Services;

use App\Models\Lead;

class TimelineMergeService
{
    private Bitrix24 $bitrix;
    private Lead $lead;
    private string $phone;

    public function __construct(Bitrix24 $bitrix)
    {
        $this->bitrix = $bitrix;
        $this->lead = new Lead();
    }

    public function mergeTimeline(array $sourceIds, int $targetId): array
    {
        $this->transferTimeline($sourceIds, $targetId);

        // TODO: перенос основных полей лида(+) | создание комментария о слиянии (-) | запись звонка (-)
        return [
            'success' => true,
            'target_id' => $targetId,
            'sources_merged' => $sourceIds
        ];
    }

    /**
     * Основной метод: собирает комментарии и активности со всех лидов,
     * сортирует по времени и переносит в целевой лид в историческом порядке
     */
    public function transferTimeline(array $sourceIds, int $targetId): void
    {
        $timeline = [];

        $allLeadIds = array_merge($sourceIds, [$targetId]);

        foreach ($allLeadIds as $leadId) {
            $result = $this->bitrix->getEntityComments([
                'ID' => $leadId,
                'entityType' => 'lead'
            ]);

            if (!empty($result->result)) {
                foreach ($result->result as $comment) {
                    $comment->type = 'comment';
                    $comment->source_lead_id = $leadId;
                    $comment->sort_time = $comment->CREATED ?? '1970-01-01';
                    $timeline[] = $comment;
                }
            }
        }

        foreach ($allLeadIds as $leadId) {
            $result = $this->bitrix->getLeadActivities($leadId);

            if (!empty($result->result)) {
                foreach ($result->result as $activity) {
                    $activity->type = 'activity';
                    $activity->source_lead_id = $leadId;
                    $activity->sort_time = $activity->LAST_UPDATED ?? $activity->CREATED ?? '1970-01-01';
                    $timeline[] = $activity;
                }
            }
        }

        usort($timeline, fn($a, $b) => strtotime($a->sort_time) <=> strtotime($b->sort_time));

        $this->clearTargetComments($targetId);

        foreach ($timeline as $item) {
            if ($item->source_lead_id === $targetId) {
                continue;
            }

            if ($item->type === 'comment') {
                $this->transferComment($item, $targetId);
            } else {
                $this->processActivity($item, $targetId);
            }
        }
    }

    private function clearTargetComments(int $targetId): void
    {
        $existing = $this->bitrix->getEntityComments([
            'ID' => $targetId,
            'entityType' => 'lead'
        ]);

        if (!empty($existing->result)) {
            foreach ($existing->result as $comment) {
                $this->bitrix->deleteComment($comment->ID);
            }
        }
    }

    private function transferComment(object $comment, int $targetId): void
    {
        $this->bitrix->addComment(
            $targetId,
            'lead',
            $comment->COMMENT ?? ''
        );
    }

    private function processActivity(object $act, int $targetId): void
    {
        $providerId = $act->PROVIDER_ID ?? null;

        if ($providerId === 'CRM_SMS') {
            $this->recreateSmsActivity($act, $targetId);
        } elseif ($providerId === 'VOXIMPLANT_CALL') {
            $this->recreateCallActivity($act, $targetId);
        } else {
            $text = "[$act->SUBJECT]\n\n" . ($act->DESCRIPTION ?? '');
            if (trim($text) !== '') {
                $this->bitrix->addComment($targetId, 'lead', trim($text));
            }
        }
    }

    private function recreateSmsActivity(object $act, int $targetId): void
    {
        $fields = [
            "OWNER_TYPE_ID" => 1,
            "OWNER_ID" => $targetId,
            "TYPE_ID" => $act->TYPE_ID ?? 6,
            "SUBJECT" => $act->SUBJECT ?? 'SMS сообщение',
            "COMPLETED" => "Y",
            "DESCRIPTION" => $act->DESCRIPTION ?? '',
            "PROVIDER_ID" => "CRM_SMS",
            "PROVIDER_TYPE_ID" => "SMS",
            "COMMUNICATIONS" => [
                [
                    "VALUE" => $this->phone ?? $this->getPhoneFromActivity($act),
                    "ENTITY_ID" => $targetId,
                    "ENTITY_TYPE_ID" => 1
                ]
            ],
            "SETTINGS" => [
                "DISABLE_SENDING_MESSAGE_COPY" => "Y"
            ]
        ];

        $this->bitrix->addActivity($fields);
    }

    private function recreateCallActivity(object $act, int $targetId): void
    {
        $this->phone = $this->extractPhoneFromSubject($act->SUBJECT ?? '');

        $registerData = [
            "USER_ID" => $act->RESPONSIBLE_ID ?? 1,
            "PHONE_NUMBER" => $this->phone,
            "TYPE" => 1,
            "CRM_CREATE" => 0,
            "CRM_ENTITY_TYPE" => "LEAD",
            "CRM_ENTITY_ID" => $targetId,
        ];

        $regResult = $this->bitrix->externalCallRegister($registerData);

        if (empty($regResult->result->CALL_ID ?? null)) {
            $this->bitrix->addComment($targetId, 'lead', "Не удалось восстановить звонок: " . ($act->SUBJECT ?? ''));
            return;
        }

        $callId = $regResult->result->CALL_ID;

        $finishData = [
            "CALL_ID" => $callId,
            "USER_ID" => $act->RESPONSIBLE_ID ?? 1,
            "DURATION" => 60,
            "STATUS_CODE" => "200",
            "ADD_TO_CHAT" => "Y",
        ];

        $this->bitrix->externalCallFinish($finishData);

        if (!empty($act->FILES) && !empty($act->FILES[0]->url)) {
            try {
                $this->bitrix->externalCallAttachRecord(
                    $callId,
                    $act->FILES[0]->url,
                    'record_' . $act->ID . '.mp3'
                );
            } catch (\Exception $e) {
                $this->bitrix->addComment($targetId, 'lead', "Не удалось прикрепить запись звонка, прямая ссылка для скачивания: " . $act->FILES[0]->url ?? '');
            }
        }
    }

    private function getPhoneFromActivity(object $act): string
    {
        if (!empty($act->COMMUNICATIONS[0]->VALUE)) {
            return $act->COMMUNICATIONS[0]->VALUE;
        }
        return $this->extractPhoneFromSubject($act->SUBJECT ?? '');
    }

    private function extractPhoneFromSubject(string $subject): string
    {
        preg_match('/\+?7?\s*[\d\s\-\(\)]{10,}/', $subject, $m);
        return $m[0] ?? '';
    }

    private function deleteDuplicates(array $duplicateIds)
    {
        foreach ($duplicateIds as $duplicateId) {
            $this->lead->delete($duplicateId);
        }
    }

}