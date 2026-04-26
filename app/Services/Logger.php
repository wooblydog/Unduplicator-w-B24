<?php
namespace App\Services;

class Logger
{
    private const LOG_TYPE = [
        'N' => 'NOTICE',
        'W' => 'WARNING',
        'E' => 'ERROR',
        'C' => 'CONFLICT',
        'I' => 'INFO',
    ];

    private string $appLogFile;
    private string $conflictsLogFile;

    public function __construct(string $logFile = null)
    {
        $logDirectory = dirname(__DIR__, 2) . '/logs/';
        
        if (!is_dir($logDirectory)) {
            mkdir($logDirectory, 0755, true);
        }

        $this->appLogFile = $logDirectory . ($logFile ?? 'app.log');
        $this->conflictsLogFile = $logDirectory . 'conflicts.log';
    }

    public function notice(...$vars): void
    {
        $this->write('N', false, $this->appLogFile, ...$vars);
    }
    public function info(...$vars): void
    {
        $this->write('I', false, $this->appLogFile, ...$vars);
    }

    public function warning(...$vars): void
    {
        $this->write('W', false, $this->appLogFile, ...$vars);
    }

    public function error(...$vars): void
    {
        $this->write('E', false, $this->appLogFile, ...$vars);
    }

    /**
     * Специальный метод для логирования конфликтов дубликатов
     * Пишет в conflicts.log
     */
    public function conflict(...$vars): void
    {
        $this->write('C', false, $this->conflictsLogFile, ...$vars);
    }

    private function write(string $typeKey, bool $pretty, string $filePath, ...$vars): void
    {
        if ($filePath == null){
            $filePath = $this->appLogFile;
        }
        $type = self::LOG_TYPE[$typeKey] ?? 'NOTICE';

        $json = json_encode($vars, JSON_UNESCAPED_UNICODE | ($pretty ? JSON_PRETTY_PRINT : 0) | JSON_UNESCAPED_SLASHES);

        $entry = date("d.m.Y H:i:s") . " - {$type} - " . $json;

        if (file_exists($filePath) && filesize($filePath) > 5 * 1024 * 1024) {
            unlink($filePath);
        }

        file_put_contents($filePath, $entry . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}