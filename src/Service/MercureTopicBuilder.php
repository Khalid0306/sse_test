<?php

namespace App\Service;

class MercureTopicBuilder
{
    public function buildTopic(string $contractId): string
    {
        return "contract/{$contractId}/updates";
    }
}