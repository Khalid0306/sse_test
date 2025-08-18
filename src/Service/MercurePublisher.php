<?php

namespace App\Service;

use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Psr\Log\LoggerInterface;

class MercurePublisher
{
    private HubInterface $hub;
    private LoggerInterface $logger;
    private MercureTopicBuilder $topicBuilder;

    public function __construct(HubInterface $hub, MercureTopicBuilder $topicBuilder, LoggerInterface $logger)
    {
        $this->hub = $hub;
        $this->topicBuilder = $topicBuilder;
        $this->logger = $logger;
    }

    public function publish(string $contractId, array $payload): void
    {
        try {
            $update = new Update(
                $topic = $this->topicBuilder->buildTopic($contractId),
                json_encode($payload)
            );

            $this->logger->info('Attempting to publish to Mercure', [
                'topic' => $topic,
                'contractId' => $contractId,
                'payload' => $payload
            ]);

            $this->hub->publish($update);

            $this->logger->info('Successfully published to Mercure', [
                'contractId' => $contractId,
                'topic' => $topic
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to publish to Mercure for contract: ' . $contractId . ' - ' . $e->getMessage());
            throw $e;
        }
    }
}