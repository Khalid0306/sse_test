<?php

namespace App\MessageHandler;

use Symfony\Component\Messenger\Handler\MessageSubscriberInterface;
use App\Message\SseRequestConsumerBridge;
use App\Service\MercurePublisher;

class SseRequestConsumerBridgeHandler implements MessageSubscriberInterface
{
    private MercurePublisher $publisher;
    public function __construct(MercurePublisher $publisher) {
        $this->publisher = $publisher;
    }

    public static function getHandledMessages(): iterable
    {
        yield SseRequestConsumerBridge::class => [
            'from_transport' => 'sse-request-consumer-bridge',
        ];
    }

    public function __invoke(SseRequestConsumerBridge  $message)
    {
        $payload = json_decode($message->getPayload(), true);
        $topic = "test";
        $this->publisher->publish($topic, $payload);
    }

}