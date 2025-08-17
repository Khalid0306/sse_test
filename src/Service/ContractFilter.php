<?php

namespace App\Service;

class ContractFilter
{
    public function shouldNotify(array $payload, string $contractId): bool
    {
        if (!isset($payload['context'])) {
            return false;
        }

        foreach ($payload['context'] as $context) {
            if ($context['type'] === 'CONTRACT' && $context['id'] === $contractId) {
                return true;
            }
        }

        return false;
    }

    public function extractContracts(array $payload): array
    {
        $contracts = [];
        if (isset($payload['context'])) {
            foreach ($payload['context'] as $context) {
                if ($context['type'] === 'CONTRACT') {
                    $contracts[] = $context['id'];
                }
            }
        }
        return $contracts;
    }
}