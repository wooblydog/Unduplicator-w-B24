<?php

namespace App\Services;

class ConflictWriter extends AbstractLogger
{
    protected string $defaultFileName = 'mergeFails.json';
    protected string $directorySuffix = 'fails';
    private const LOG_TYPE = [
        'C' => 'CONFLICT',
    ];

    protected function formatMessage(string $level, array $vars): string
    {
        $logEntry = [
            'timestamp' => date('c'),
            'level' => self::LOG_TYPE[$level],
            'message' => $vars,
        ];

        return json_encode($logEntry, JSON_PRETTY_PRINT);
    }

    public function critical(...$vars): void
    {
        $this->write('C', $vars);
    }

}