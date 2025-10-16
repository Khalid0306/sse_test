<?php

namespace App\Service;

use App\Exception\MercureException;
use App\helpers\MercureTopicBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

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
    public function publish(string $contractId, array $payload, string $ownerApplication): void
    {

        try {
            $topic = $this->topicBuilder->buildTopic($contractId, $ownerApplication);
            $jsonPayload = json_encode($payload, JSON_THROW_ON_ERROR);

            $update = new Update($topic, $jsonPayload);

            $this->hub->publish($update);

            $this->logger->info(
                'Successfully published to Mercure (topic: {topic})',
                [
                    'topic' => $topic,
                ]
            );

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
}