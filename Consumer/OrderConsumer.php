<?php

namespace Signalise\Plugin\Consumer;

use Exception;
use Signalise\Plugin\Model\signaliseApiClient;

class OrderConsumer
{
    private signaliseApiClient $signaliseApiClient;

    public function __construct(
        signaliseApiClient $signaliseApiClient
    ) {
        $this->signaliseApiClient = $signaliseApiClient;
    }

    /**
     * @throws Exception
     */
    public function processMessage(string $serializedDto): void
    {
        $this->signaliseApiClient->pushData($serializedDto);
    }
}
