<?php

namespace Signalise\Plugin\Consumer;

class OrderConsumer
{
    public function processMessage(string $serializedDto): void
    {
        // @ todo send data to SignaliseClient
    }
}
