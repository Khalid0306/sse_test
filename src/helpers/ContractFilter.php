<?php

namespace App\helpers;

use App\Exception\ContractNotFoundException;
use App\Exception\InvalidPayloadException;
use App\Exceptions\OwnerApplicationNotFoundException;
use Psr\Log\LoggerInterface;

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
            $type = strtoupper($context['type'] ?? '');

            if ($type === 'CONTRACT') {
                if (empty($context['id'])) {
                    throw new ContractNotFoundException('Contract found but ID is empty or missing');
                }

                $contractId = (string) $context['id'];
                $this->logger->info('Contract extracted: {contract_id}', [
                    'contract_id' => $contractId,
                ]);

                return $contractId;
            }
        }
        throw new ContractNotFoundException('No valid CONTRACT type found in payload context');
    }

    /**
     * @throws InvalidPayloadException
     * @throws OwnerApplicationNotFoundException
     */
    public function extractOwnerApplication(array $payload): string
    {
        $this->validatePayload($payload);

        if (empty($payload['ownerApplication'])) {
            throw new OwnerApplicationNotFoundException('ownerApplication is missing or empty in payload');
        }

        $ownerApp = (string) $payload['ownerApplication'];

        $this->logger->info('Owner application extracted: {owner_application}', [
            'owner_application' => $ownerApp,
        ]);

        return $ownerApp;
    }



    /**
     * @throws InvalidPayloadException
     */
    private function validatePayload(array $payload): void
    {
        if (empty($payload)) {
            throw new InvalidPayloadException('Payload is empty');
        }

        if (!isset($payload['ownerApplication'])) {
            throw new InvalidPayloadException('Missing required field: ownerApplication');
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