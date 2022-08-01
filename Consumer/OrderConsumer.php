<?php

namespace Signalise\Plugin\Consumer;

use Signalise\Plugin\Model\signaliseApiClient;

class OrderConsumer
{
    private signaliseApiClient $signaliseApiClient;

    public function __construct(signaliseApiClient $signaliseApiClient)
    {
        $this->signaliseApiClient = $signaliseApiClient;
    }

    public function processMessage(string $serializedDto): void
    {
        $this->signaliseApiClient->pushData($serializedDto);
    }
}
