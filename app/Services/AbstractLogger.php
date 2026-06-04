<?php

namespace App\Services;

abstract class AbstractLogger
{
    protected string $logPath;
    protected string $defaultFileName;
    protected string $directorySuffix;
    protected const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5 МБ

    public function __construct(string $logFile = null)
    {
        $logDirectory = dirname(__DIR__, 2) . '/' . $this->directorySuffix . '/';

        if (!is_dir($logDirectory)) {
            mkdir($logDirectory, 0755, true);
        }

        $this->logPath = $logDirectory . ($logFile ?? $this->defaultFileName);
    }

    abstract protected function formatMessage(string $level, array $vars): string|array;
}
