<?php

namespace App\Messenger;

use App\Message\SseRequestConsumerBridge;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

class ExternalJsonMessageSerializer implements SerializerInterface
{

    public function encode(Envelope $envelope): array
    {
        throw new \Exception('Transport & serializer not meant for sending messages');
    }

    public function decode(array $encodedEnvelope): Envelope
    {
        $body = $encodedEnvelope['body'];
        $message = new SseRequestConsumerBridge($body);

        return new Envelope($message);
    }

}