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
            'ID' => $vars[0],
            'timestamp' => date('c'),
            'message' => $vars,
            'attempts' => 1,
            'last_updated' => time(),
        ];
    }

    public function addConflict(array $conflictData): void
    {
        $data = $this->readJson();
        $newMessage = $this->formatMessage("", $conflictData);

        $matches = $this->checkMatches($data, $newMessage);

        if ($matches) {
            $matches[] = $newMessage;
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
        return !str_contains($jsonData, $newMessage["ID"]) ? json_decode($jsonData, true) : false;
    }


    /**
     * Удаляет записи с attempts >= $maxAttempts и возвращает их
     */
    public function cleanup(): array
    {
        $maxAttempts = 3;
        $data = json_decode($this->readJson(), true);
        $toLog = [];
        $remaining = [];

        foreach ($data as $item) {
            if (($item['attempts'] ?? 0) >= $maxAttempts) {
                $toLog[] = $item;
            } else {
                $remaining[] = $item;
            }
        }

        if ($toLog !== []) {
            $this->writeJsonArray($remaining);
        }

        return $toLog;
    }
}