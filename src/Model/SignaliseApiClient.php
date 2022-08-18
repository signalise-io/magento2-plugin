<?php

declare(strict_types=1);

namespace Signalise\Plugin\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\AsyncClient\GuzzleAsyncClient;
use Magento\Framework\HTTP\AsyncClient\Request;
use Psr\Log\LoggerInterface;
use Throwable;

class SignaliseApiClient
{
    private GuzzleAsyncClient $client;

    private SignaliseConfig $signaliseConfig;

    private LoggerInterface $logger;

    public function __construct(
        GuzzleAsyncClient $client,
        SignaliseConfig $signaliseConfig,
        LoggerInterface $logger
    ) {
        $this->client = $client;
        $this->signaliseConfig = $signaliseConfig;
        $this->logger = $logger;
    }

    /**
     * @throws LocalizedException
     */
    private function apiUrl(): string
    {
        return $this->signaliseConfig->getApiUrl();
    }

    /**
     * @throws LocalizedException
     */
    private function createRequest(string $serializedData): Request
    {
        return new Request(
            $this->apiUrl(),
            Request::METHOD_POST, [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ],
            $serializedData
        );
    }


    public function pushData(string $serializedData): void
    {
        try {
            $response = $this->client->request(
                $this->createRequest($serializedData)
            )->get();


            $this->logger->info(
                sprintf(
                    '%s - Pushed date to Signalise with status code: %s',
                    date('d/m/Y'),
                    $response->getStatusCode()
                )
            );
        } catch (Throwable $throwable) {
            $this->logger->error($throwable->getMessage());
        }
    }
}
