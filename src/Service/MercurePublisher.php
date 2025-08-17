<?php

namespace App\Service;

use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Psr\Log\LoggerInterface;

class MercurePublisher
{
    private HubInterface $hub;
    private ?LoggerInterface $logger;

    public function __construct(HubInterface $hub, LoggerInterface $logger = null)
    {
        $this->hub = $hub;
        $this->logger = $logger;
    }

    public function publish(string $topic, array $payload): void
    {
        try {
            $update = new Update(
                $topic,
                json_encode($payload)
            );

            $this->logger?->info('Attempting to publish to Mercure', [
                'topic' => $topic,
                'payload' => $payload
            ]);

            $this->hub->publish($update);

            $this->logger?->info('Successfully published to Mercure');
        } catch (\Exception $e) {
            $this->logger?->error('Failed to publish to Mercure: ' . $e->getMessage());
            throw $e;
        }
    }
}