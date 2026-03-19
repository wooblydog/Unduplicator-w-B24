<?php
namespace App\Factories;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class LoggerFactory
{
    /**
     * Создаёт PSR-3 логгер с разбиением по год/месяц и дневным файлом
     *
     * @param string $channel Имя канала логгера
     * @param string $basePath Корневая папка для логов
     * @param int $level Уровень логирования Monolog
     * @return Logger
     */
    public static function create(string $channel = 'app', string $basePath = __DIR__ . '/../../logs', int $level = Logger::NOTICE): Logger
    {
        $year = date('Y');
        $month = date('m');
        $day = date('d');

        $monthPath = "$basePath/$year/$month";

        if (!is_dir($monthPath)) {
            mkdir($monthPath, 0777, true);
        }

        $file = $monthPath . '/' . $day . 'log.log';

        $logger = new Logger($channel);
        $logger->pushHandler(new StreamHandler($file, $level));

        return $logger;
    }
}
