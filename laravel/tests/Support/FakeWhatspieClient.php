<?php

namespace Tests\Support;

use App\Gateways\WhatsApp\WhatspieClient;

class FakeWhatspieClient implements WhatspieClient
{
    public array $sent = [];

    public array $response = ['data' => ['id' => 45136466]];

    public ?\Throwable $exception = null;

    public function sendMessage(string $to, string $message): array
    {
        if ($this->exception) {
            throw $this->exception;
        }

        $this->sent[] = compact('to', 'message');

        return $this->response;
    }
}