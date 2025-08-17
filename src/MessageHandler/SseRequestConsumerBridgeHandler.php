<?php

namespace App\MessageHandler;

use Symfony\Component\Messenger\Handler\MessageSubscriberInterface;
use App\Message\SseRequestConsumerBridge;
use App\Service\MercurePublisher;
use App\Service\ContractFilter;
use Psr\Log\LoggerInterface;

class SseRequestConsumerBridgeHandler implements MessageSubscriberInterface
{
    private MercurePublisher $publisher;
    private ContractFilter $contractFilter;
    private LoggerInterface $logger;
    public function __construct(MercurePublisher $publisher, ContractFilter $contractFilter, LoggerInterface $logger) {
        $this->publisher = $publisher;
        $this->contractFilter = $contractFilter;
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

            $contracts = $this->contractFilter->extractContracts($payload);

            if (!empty($contracts)) {
                $this->publisher->publish($contracts, $payload);
                $this->logger->info('Published to contracts', ['contracts' => $contracts]);
            } else {
                $this->logger->warning('No contracts found in payload', ['payload' => $payload]);
            }

            $this->logger->info('Message published to Mercure', ['contracts' => $contracts]);

        } catch (\Exception $e) {
            $this->logger->error('Handler failed: ' . $e->getMessage());
            throw $e;
        }
    }

}