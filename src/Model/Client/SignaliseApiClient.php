<?php

/**
 * Copyright Elgentos BV. All rights reserved.
 * https://www.elgentos.nl/
 */

declare(strict_types=1);

namespace Signalise\Plugin\Model\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\AsyncClient\Request;
use Psr\Log\LoggerInterface;
use Signalise\Plugin\Model\Config\SignaliseConfig;

class SignaliseApiClient
{
    private Client $client;

    private SignaliseConfig $signaliseConfig;

    private LoggerInterface $logger;

    public function __construct(
        Client $client,
        SignaliseConfig $signaliseConfig,
        LoggerInterface $logger
    ) {

        $this->client          = $client;
        $this->signaliseConfig = $signaliseConfig;
        $this->logger          = $logger;
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
    public function pushData(string $serializedData): void
    {
        try {
            $response = $this->client->request(
                Request::METHOD_POST,
                $this->apiUrl(),
                [$serializedData]
            );

            $this->logger->info(
                sprintf(
                    '%s - Pushed date to Signalise with status code: %s',
                    date('d/m/Y'),
                    $response->getStatusCode()
                )
            );
        } catch (GuzzleException $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
