<?php

namespace App\Services;

abstract class AbstractLogger
{
    protected string $logPath;
    protected string $defaultFileName;
    protected string $directorySuffix;
    private const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5 МБ


    public function __construct(string $logFile = null)
    {
        $logDirectory = dirname(__DIR__, 2) . '/' . $this->directorySuffix . '/';

        if (!is_dir($logDirectory)) {
            mkdir($logDirectory, 0755, true);
        }

        $this->logPath = $logDirectory . ($logFile ?? $this->defaultFileName);
    }

    protected function write(string $level, mixed $vars): void
    {
        if (file_exists($this->logPath) && filesize($this->logPath) > self::MAX_FILE_SIZE) {
            unlink($this->logPath);
        }

        $message = $this->formatMessage($level, $vars);
        file_put_contents($this->logPath, $message . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    abstract protected function formatMessage(string $level, array $vars): string;
}
