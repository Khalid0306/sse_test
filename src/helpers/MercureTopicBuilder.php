<?php

namespace App\helpers;

class MercureTopicBuilder
{
    public function buildTopic(string $contractId, string $ownerApplication): string
    {
        return "realtime-event/{$ownerApplication}/contract/{$contractId}";
    }
}