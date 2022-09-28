<?php

namespace Signalise\Plugin\Consumer;

use Signalise\Plugin\Model\Config\SignaliseConfig;

class OrderConsumer
{
    public function processMessage(string $serializedDto): void
    {
        // @ todo send data to SignaliseClient
    }
}
