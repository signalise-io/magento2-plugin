<?php

namespace Signalise\Plugin\Consumer;

use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\Exception\LocalizedException;
use Signalise\PhpClient\Client\ApiClient;
use Signalise\PhpClient\Exception\ResponseException;
use Signalise\Plugin\Model\Config\SignaliseConfig;

class OrderConsumer
{
    private SignaliseConfig $config;

    private ApiClient $apiClient;

    public function __construct(SignaliseConfig $config, ApiClient $apiClient)
    {
        $this->config    = $config;
        $this->apiClient = $apiClient;
    }

    /**
     * @throws LocalizedException|GuzzleException|ResponseException
     */
    public function processMessage(string $serializedData): void
    {
        if ($this->config->isDevelopmentMode()) {
            return;
        }

        $this->apiClient->postOrderHistory(
            $this->config->getApiKey(),
            $serializedData,
            $this->config->getConnectId()
        );
    }
}
