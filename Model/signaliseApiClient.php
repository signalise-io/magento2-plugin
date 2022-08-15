<?php

declare(strict_types=1);

namespace Signalise\Plugin\Model;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\AsyncClient\GuzzleAsyncClient;
use Magento\Framework\HTTP\AsyncClient\Request;

class signaliseApiClient
{
    private GuzzleAsyncClient $client;

    private SignaliseConfig $signaliseConfig;

    public function __construct(GuzzleAsyncClient $client, SignaliseConfig $signaliseConfig)
    {
        $this->client = $client;
        $this->signaliseConfig = $signaliseConfig;
    }

    /**
     * @throws LocalizedException
     */
    private function apiKey(): string
    {
        return $this->signaliseConfig->getApiKey();
    }

    /**
     * @throws LocalizedException
     */
    private function createRequest(string $serializedData): Request
    {
        return new Request(
            $this->apiKey(),
            Request::METHOD_POST, [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ],
            $serializedData
        );
    }

    /**
     * @throws Exception
     */
    public function pushData(string $serializedData): void
    {
        try {
            $this->client->request(
                $this->createRequest($serializedData)
            );
        } catch (Exception $exception) {
            Throw new Exception($exception->getMessage());
        }
    }
}
