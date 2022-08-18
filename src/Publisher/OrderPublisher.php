<?php

declare(strict_types=1);

namespace Signalise\Plugin\Publisher;

use Magento\Framework\DataObject;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Serialize\Serializer\Json;

class OrderPublisher
{
    private const TOPIC_NAME = "signalise.order.push";

    private Json $json;

    private PublisherInterface $publisher;

    public function __construct(
        Json $json,
        PublisherInterface $publisher
    ) {
        $this->json      = $json;
        $this->publisher = $publisher;
    }

    /**
     * @param DataObject $orderDataObject
     * @param string     $eventName
     *
     * @return mixed|null
     */
    public function execute(DataObject $orderDataObject, string $eventName)
    {
        return $this->publisher->publish(
            self::TOPIC_NAME,
            $this->json->serialize([$orderDataObject->getData(), $eventName])
        );
    }
}
