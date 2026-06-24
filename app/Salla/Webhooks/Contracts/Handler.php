<?php

namespace App\Salla\Webhooks\Contracts;

interface Handler
{
    public function handle(array $event): void;
}
