<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use App\Exception\ContractNotFoundException;
use App\Exception\InvalidPayloadException;

class ContractFilter
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
    }

    /**
     * @throws InvalidPayloadException
     * @throws ContractNotFoundException
     */
    public function extractContracts(array $payload): string
    {
        $this->validatePayload($payload);

        foreach ($payload['context'] as $context) {
            if (($context['type'] ?? null) === 'CONTRACT') {
                if (empty($context['id'])) {
                    throw new ContractNotFoundException('Contract found but ID is empty or missing');
                }
                $contractId = (string) $context['id'];
                $this->logger->info('Contract extracted', ['contract_id' => $contractId]);
                return $contractId;
            }
        }
        throw new ContractNotFoundException('No valid CONTRACT type found in payload context');
    }

    /**
     * @throws InvalidPayloadException
     */
    private function validatePayload(array $payload): void
    {
        if (empty($payload)) {
            throw new InvalidPayloadException('Payload is empty');
        }

        if (!isset($payload['context'])) {
            throw new InvalidPayloadException('Missing required field: context');
        }

        if (!is_array($payload['context'])) {
            throw new InvalidPayloadException('Context field must be an array');
        }

        if (empty($payload['context'])) {
            throw new InvalidPayloadException('Context array is empty');
        }
    }
}