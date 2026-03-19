<?php

namespace App\Services;

use App\Factories\LoggerFactory;

class PhoneFormatter
{
    private static \Monolog\Logger $logger;

    private static function initLogger(): void
    {
        if (!isset(self::$logger)) {
            self::$logger = LoggerFactory::create(session_id());
        }
    }

    public static function format(?string $phone): int
    {
        self::initLogger();

        $digits = preg_replace('/\D+/', '', $phone);

        if ($digits === null || $digits === '') {
            self::$logger->error('Некорректный номер телефона', ['phone' => $phone]);
            throw new \InvalidArgumentException("Некорректный номер телефона: {$phone}");
        }

        if ($digits[0] === '8') {
            $digits = '7' . substr($digits, 1);
        }

        if (strlen($digits) === 10) {
            $digits = '7' . $digits;
        }

        self::$logger->notice('Телефон отформатирован', ['original' => $phone, 'formatted' => $digits]);

        return (int)$digits;
    }
}
