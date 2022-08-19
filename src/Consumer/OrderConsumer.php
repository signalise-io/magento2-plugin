<?php

namespace Signalise\Plugin\Consumer;

use Exception;
use Signalise\Plugin\Model\SignaliseApiClient;

class OrderConsumer
{
    private SignaliseApiClient $signaliseApiClient;

    public function __construct(
        SignaliseApiClient $signaliseApiClient
    ) {
        $this->signaliseApiClient = $signaliseApiClient;
    }

    public function processMessage(string $serializedDto): void
    {
        $this->signaliseApiClient->pushData($serializedDto);
    }
}
