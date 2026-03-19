<?php

namespace App\Services\Lead;

use App\Factories\LoggerFactory;

class LeadIdExtractor
{
    private static \Monolog\Logger $logger;

    private static function initLogger(): void
    {
        if (!isset(self::$logger)) {
            self::$logger = LoggerFactory::create(session_id());
        }
    }

    public static function extractIds(array $result): array
    {
        self::initLogger();
        $ids = [];

        if (!empty($result['main']['ID'])) {
            $ids[] = $result['main']['ID'];
        }

        if (!empty($result['duplicates'])) {
            foreach ($result['duplicates'] as $duplicate) {
                if (isset($duplicate['ID'])) {
                    $ids[] = $duplicate['ID'];
                }
            }
        }

        self::$logger->info('Извлечены ID лидов', ['ids' => $ids]);
        return $ids;
    }
}
