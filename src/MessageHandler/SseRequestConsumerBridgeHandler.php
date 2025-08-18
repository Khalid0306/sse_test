<?php

namespace App\MessageHandler;

use Symfony\Component\Messenger\Handler\MessageSubscriberInterface;
use App\Message\SseRequestConsumerBridge;
use App\Service\MercurePublisher;
use App\Service\ContractFilter;
use App\Exception\InvalidPayloadException;
use App\Exception\ContractNotFoundException;
use App\Exception\MercureException;
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

            $contract = $this->contractFilter->extractContracts($payload);

            $this->publisher->publish($contract, $payload);

            $this->logger->info('Message published to Mercure', ['contract' => $contract]);

        }catch (InvalidPayloadException $e) {
            $this->logger->warning('Invalid payload received', [
                'error' => $e->getMessage()
            ]);
            return;

        } catch (ContractNotFoundException $e) {
            $this->logger->warning('No contract found in message', [
                'error' => $e->getMessage()
            ]);
            return;

        } catch (MercureException $e) {
            $this->logger->error('Mercure error during processing', [
                'error' => $e->getMessage(),
                'exception_type' => get_class($e)
            ]);
            throw $e;

        }catch (\Exception $e) {
            $this->logger->error('Handler failed: ' . $e->getMessage());
            throw $e;
        }
    }

}