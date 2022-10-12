<?php

namespace Signalise\Plugin\Consumer;

use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\Exception\LocalizedException;
use Signalise\PhpClient\Client\ApiClient;
use Signalise\PhpClient\Exception\ResponseException;
use Signalise\Plugin\Logger\Logger;
use Signalise\Plugin\Model\Config\SignaliseConfig;

class OrderConsumer
{
    private SignaliseConfig $config;

    private ApiClient $apiClient;

    private Logger $logger;

    public function __construct(
        SignaliseConfig $config,
        ApiClient $apiClient,
        Logger $logger
    ) {
        $this->config    = $config;
        $this->apiClient = $apiClient;
        $this->logger    = $logger;
    }

    /**
     * @throws LocalizedException
     */
    public function processMessage(string $serializedData)
    {
        if ($this->config->isDevelopmentMode()) {
             return;
        }

        try {
            $this->apiClient->postOrderHistory(
                $this->config->getApiKey(),
                $serializedData,
                $this->config->getConnectId()
            );
        } catch (GuzzleException | ResponseException $e) {
            $this->logger->critical(
                $e->getMessage()
            );
        }
    }
}
