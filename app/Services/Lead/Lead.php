<?php

namespace App\Services\Lead;

class Lead
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function __toString(): string
    {
        return json_encode($this->data, JSON_UNESCAPED_UNICODE);
    }
}