<?php

namespace App\Service;

use Psr\Log\LoggerInterface;

class ContractFilter
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
    }
    public function extractContracts(array $payload): ?string
    {
        if (!isset($payload['context'])) {
            $this->logger->warning('No context found in payload.');
            return null;
        }

        foreach ($payload['context'] as $context) {
            if (($context['type'] ?? null) === 'CONTRACT') {
                $this->logger->info('Contract extracted.', [
                    'contract_id' => $context['id'] ?? null,
                ]);
                return $context['id'] ?? null;
            }
        }

        $this->logger->debug('Context present but no CONTRACT type found.');
        return null;
    }
}