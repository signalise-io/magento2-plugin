<?php

declare(strict_types=1);

namespace Signalise\Plugin\Publisher;

use Exception;
use Magento\Framework\DataObject;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Signalise\Plugin\Logger\Logger;

class OrderPublisher
{
    private const TOPIC_NAME = "signalise.order.push";

    private Json $json;

    private PublisherInterface $publisher;

    private Logger $logger;

    public function __construct(
        Json $json,
        PublisherInterface $publisher,
        Logger $logger
    ) {
        $this->json      = $json;
        $this->publisher = $publisher;
        $this->logger    = $logger;
    }

    /**
     * @return mixed|null
     */
    public function execute(DataObject $orderDataObject, string $storeId): mixed
    {
        try {
           return $this->publisher->publish(
                self::TOPIC_NAME, $this->json->serialize([
                    'records' => [$orderDataObject->getData()],
                    'store_id' => $storeId
                ])
            );
        } catch (Exception $exception) {
            $this->logger->critical(
                $exception->getMessage()
            );
        }
    }
}
