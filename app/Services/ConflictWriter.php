<?php

namespace App\Services;

use RuntimeException;

class ConflictWriter extends AbstractLogger
{
    protected string $defaultFileName = 'mergeFails.json';
    protected string $directorySuffix = 'fails';

    public function __construct(string $logFile = null)
    {
        parent::__construct($logFile);
        $this->ensureValidJsonArray();
    }

    protected function formatMessage(string $level, array $vars): array
    {
        return [
            'ID' => $vars["MainLead"]["ID"],
            'timestamp' => date('c'),
            'message' => $vars,
            'attempts' => 1,
            'last_updated' => time(),
        ];
    }

    public function addConflict(array $conflictData): void
    {
        $fails = $this->readJson();
        $newFail = $this->formatMessage("", $conflictData);

        $matches = $this->checkMatches($fails, $newFail);


        if ($matches) {
            $matches[] = $newFail;
            $this->writeJsonArray($matches);
        }
    }

    public function addAttempt($id): void
    {
        $currentFails = json_decode($this->readJson(), true);

        $index = array_search($id, array_column($currentFails, 'ID'), true);
        if ($index !== false) {
            $currentFails[$index]['attempts']++;
        }

        $this->writeJsonArray($currentFails);
    }

    // ====================== ВНУТРЕННИЕ МЕТОДЫ ======================

    private function ensureValidJsonArray(): void
    {
        if (!file_exists($this->logPath) || filesize($this->logPath) === 0) {
            file_put_contents($this->logPath, "[]", LOCK_EX);
        }
    }

    private function readJson(): string
    {
        $this->ensureValidJsonArray();
        return file_get_contents($this->logPath);
    }

    private function writeJsonArray(array $data): void
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        if ($json === false) {
            throw new RuntimeException('Ошибка json_encode');
        }

        $tempFile = $this->logPath . '.tmp_' . uniqid();

        if (file_put_contents($tempFile, $json, LOCK_EX) === false) {
            @unlink($tempFile);
            throw new RuntimeException("Не удалось записать {$this->logPath}");
        }

        if (!rename($tempFile, $this->logPath)) {
            @unlink($tempFile);
            throw new RuntimeException("Не удалось переименовать временный файл");
        }

        @chmod($this->logPath, 0664);
    }

    private function checkMatches(string $jsonData, array $newMessage): bool|array
    {
        $existingData = json_decode($jsonData, true);
        $searchId = $newMessage["ID"];

        foreach ($existingData as $item) {
            if (in_array($searchId, $item['message']['DuplicateData'])) {
                return false;
            }
        }
        return $existingData;
    }

    public function removeById(string $id): bool
    {
        $data = json_decode($this->readJson(), true);
        $initialCount = count($data);

        $filtered = array_filter($data, function($item) use ($id) {
            return $item['ID'] !== $id;
        });

        if (count($filtered) < $initialCount) {
            $this->writeJsonArray(array_values($filtered));
            return true;
        }

        return false;
    }
}