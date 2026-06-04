<?php

namespace App\Services;

class Logger extends AbstractLogger
{
    protected string $defaultFileName = 'app.log';
    protected string $directorySuffix = 'logs';
    private const LOG_TYPE = [
        'N' => 'NOTICE',
        'W' => 'WARNING',
        'E' => 'ERROR',
        'C' => 'CONFLICT',
        'I' => 'INFO',
    ];

    public function notice(...$vars): void
    {
        $this->write('N', $vars);
    }

    public function info(...$vars): void
    {
        $this->write('I', $vars);
    }

    public function warning(...$vars): void
    {
        $this->write('W', $vars);
    }

    public function error(...$vars): void
    {
        $this->write('E', $vars);
    }


    public function conflict(...$vars): void
    {
        parent::__construct('conflicts.log') ;
        $this->write('C', $vars);
    }

    protected function formatMessage(string $level, $vars): string
    {
        $json = json_encode($vars, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return date("d.m.Y H:i:s") . " - " . self::LOG_TYPE[$level] . " - " . $json;
    }

    private function write(string $level, mixed $vars): void
    {
        if (file_exists($this->logPath) && filesize($this->logPath) > $this::MAX_FILE_SIZE) {
            unlink($this->logPath);
        }

        $message = $this->formatMessage($level, $vars);
        file_put_contents($this->logPath, $message . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}