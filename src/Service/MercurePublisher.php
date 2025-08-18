<?php

namespace App\Service;

use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Psr\Log\LoggerInterface;
use App\Exception\MercureException;

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

    /**
     * @throws MercureException
     */
    public function publish(string $contractId, array $payload): void
    {
        $this->validatePublishParameters($contractId, $payload);

        try {
            $topic = $this->topicBuilder->buildTopic($contractId);
            $jsonPayload = json_encode($payload, JSON_THROW_ON_ERROR);

            $update = new Update($topic, $jsonPayload);

            $this->logger->info('Publishing to Mercure', [
                'topic' => $topic,
                'contractId' => $contractId,
                'payload_size' => strlen($jsonPayload)
            ]);

            $this->hub->publish($update);

            $this->logger->info('Successfully published to Mercure', [
                'contractId' => $contractId,
                'topic' => $topic
            ]);

        } catch (\JsonException $e) {
            $error = "JSON encoding failed for contract {$contractId}: " . $e->getMessage();
            $this->logger->error($error);
            throw new MercureException($error, 0, $e);

        } catch (\Exception $e) {
            $error = "Failed to publish to Mercure for contract {$contractId}: " . $e->getMessage();
            $this->logger->error($error, [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            throw new MercureException($error, 0, $e);
        }
    }

    private function validatePublishParameters(string $contractId, array $payload): void
    {
        if (empty(trim($contractId))) {
            throw new MercureException('Contract ID cannot be empty');
        }

        if (empty($payload)) {
            throw new MercureException('Payload cannot be empty');
        }

    }
}