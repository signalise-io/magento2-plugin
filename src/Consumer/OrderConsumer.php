<?php

namespace Signalise\Plugin\Consumer;

use Magento\Framework\Exception\LocalizedException;
use Signalise\Plugin\Model\Client\SignaliseApiClient;

class OrderConsumer
{
    private SignaliseApiClient $signaliseApiClient;

    public function __construct(
        SignaliseApiClient $signaliseApiClient
    ) {
        $this->signaliseApiClient = $signaliseApiClient;
    }

    /**
     * @throws LocalizedException
     */
    public function processMessage(string $serializedDto): void
    {
        $this->signaliseApiClient->pushData($serializedDto);
    }
}
