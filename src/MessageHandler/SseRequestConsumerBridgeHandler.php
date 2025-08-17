<?php

namespace App\MessageHandler;

use Symfony\Component\Messenger\Handler\MessageSubscriberInterface;
use App\Message\SseRequestConsumerBridge;
use App\Service\MercurePublisher;
use Psr\Log\LoggerInterface;

class SseRequestConsumerBridgeHandler implements MessageSubscriberInterface
{
    private MercurePublisher $publisher;
    private LoggerInterface $logger;
    public function __construct(MercurePublisher $publisher, LoggerInterface $logger) {
        $this->publisher = $publisher;
        $this->logger = $logger;
    }

    public static function getHandledMessages(): iterable
    {
        yield SseRequestConsumerBridge::class => [
            'from_transport' => 'sse-request-consumer-bridge',
        ];
    }

    public function __invoke(SseRequestConsumerBridge  $message)
    {
        try {
            $payload = json_decode($message->getPayload(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \InvalidArgumentException('Invalid JSON: ' . json_last_error_msg());
            }

            $topic = "test";
            $this->publisher->publish($topic, $payload);

            $this->logger->info('Message published to Mercure', ['topic' => $topic]);

        } catch (\Exception $e) {
            $this->logger->error('Handler failed: ' . $e->getMessage());
            throw $e;
        }
    }

}