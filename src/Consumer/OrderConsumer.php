<?php

namespace Signalise\Plugin\Consumer;

use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\Exception\LocalizedException;
use Signalise\PhpClient\Client\ApiClient;
use Signalise\PhpClient\Exception\ResponseException;
use Signalise\Plugin\Logger\Logger;
use Signalise\Plugin\Model\Config\SignaliseConfig;
use Magento\Framework\Serialize\Serializer\Json;

class OrderConsumer
{
    private SignaliseConfig $config;

    private ApiClient $apiClient;

    private Logger $logger;

    private Json $json;

    private int $storeId;

    public function __construct(
        SignaliseConfig $config,
        ApiClient $apiClient,
        Logger $logger,
        Json $json
    ) {
        $this->config    = $config;
        $this->apiClient = $apiClient;
        $this->logger    = $logger;
        $this->json      = $json;
    }

    private function rebuildSerializedData(string $serializedData): string
    {
        $data = $this->json->unserialize($serializedData);

        $this->storeId = (int)$data['store_id'];

        return $this->json->serialize([
            "records" => $data['records']
        ]);
    }

    /**
     * @throws LocalizedException
     */
    public function processMessage(string $serializedData): void
    {
        if ($this->config->isDevelopmentMode()) {
             return;
        }

        try {
            $this->apiClient->postOrderHistory(
                $this->config->getApiUrl(),
                $this->config->getApiKey(),
                $this->rebuildSerializedData($serializedData),
                $this->config->getConnectId($this->storeId)
            );
        } catch (GuzzleException | ResponseException $e) {
            $this->logger->critical(
                $e->getMessage()
            );
        }
    }
}
