<?php

namespace Signalise\Plugin\Consumer;

use Signalise\Plugin\Model\signaliseApiClient;
use Magento\Framework\Serialize\Serializer\Json;

class OrderConsumer
{
    private signaliseApiClient $signaliseApiClient;
    private Json $json;

    public function __construct(
        signaliseApiClient $signaliseApiClient,
        Json $json
    ) {
        $this->signaliseApiClient = $signaliseApiClient;
        $this->json = $json;
    }

    public function processMessage(string $serializedDto): void
    {
        $data = $this->json->unserialize($serializedDto);

        $this->signaliseApiClient->pushData($data);
    }
}
