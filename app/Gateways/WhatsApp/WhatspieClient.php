<?php

namespace App\Gateways\WhatsApp;

interface WhatspieClient
{
    /**
     * @return array<string, mixed>
     */
    public function sendMessage(string $to, string $message): array;
}