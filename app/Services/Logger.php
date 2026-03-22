<?php
namespace App\Services;

class Logger
{
    private const LOG_TYPE = [
        'N' => 'NOTICE',
        'W' => 'WARNING',
        'E' => 'ERROR',
    ];
    private string $logFile;

    public function __construct(string $logFile = null)
    {
        $logDirectory = dirname(__DIR__, 2) . '/logs/';

        if (!is_dir($logDirectory)) {
            mkdir($logDirectory, 0755, true);
        }

        $this->logFile = $logDirectory . ($logFile ?? 'app.log');
    }

    public function notice(...$vars): void
    {
        $this->write('N', false, ...$vars);
    }

    public function warning(...$vars): void
    {
        $this->write('W', false, ...$vars);
    }

    public function error(...$vars): void
    {
        $this->write('E', false, ...$vars);
    }

    public function info(...$vars): void
    {
        $this->write('I', true, ...$vars);
    }

    private function write(string $typeKey, bool $pretty, ...$vars): void
    {
        $type = self::LOG_TYPE[$typeKey] ?? 'NOTICE';

        $json = json_encode($vars, JSON_UNESCAPED_UNICODE | ($pretty ? JSON_PRETTY_PRINT : 0));

        $entry = date("d.m.Y H:i:s") . " - {$type} - " . $json;

        if (file_exists($this->logFile) && filesize($this->logFile) > 5 * 1024 * 1024) {
            unlink($this->logFile);
        }

        file_put_contents($this->logFile, $entry . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}
