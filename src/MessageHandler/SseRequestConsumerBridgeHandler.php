<?php

namespace App\MessageHandler;

use App\Exception\ContractNotFoundException;
use App\Exception\InvalidPayloadException;
use App\Exception\MercureException;
use App\helpers\ContractFilter;
use App\Message\SseRequestConsumerBridge;
use App\Service\MercurePublisher;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Handler\MessageSubscriberInterface;

class SseRequestConsumerBridgeHandler implements MessageSubscriberInterface
{
    private MercurePublisher $mercurePublisher;
    private ContractFilter $contractFilter;
    private LoggerInterface $logger;
    public function __construct(MercurePublisher $mercurePublisher, ContractFilter $contractFilter, LoggerInterface $logger) {
        $this->mercurePublisher = $mercurePublisher;
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

            $contractId = $this->contractFilter->extractContracts($payload);
            $ownerApplication = $this->contractFilter->extractOwnerApplication($payload);

            $this->mercurePublisher->publish($contractId, $payload, $ownerApplication);

            return;

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