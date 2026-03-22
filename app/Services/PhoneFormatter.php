<?php

namespace App\Services;

class PhoneFormatter
{
    private Logger $logger;

    public function __construct()
    {
        $this->logger = new Logger();
    }

    //TODO сделать статику
    public function format(?string $phone): int
    {
        $digits = preg_replace('/\D+/', '', $phone);

        if ($digits === null || $digits === '') {
            $this->logger->error('Некорректный номер телефона', ['phone' => $phone]);
        }

        if ($digits[0] === '8') {
            $digits = '7' . substr($digits, 1);
        }

        if (strlen($digits) === 10) {
            $digits = '7' . $digits;
        }

        return $digits;
    }
}
