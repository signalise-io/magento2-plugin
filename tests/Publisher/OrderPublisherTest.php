<?php

/**
 * Copyright Elgentos BV. All rights reserved.
 * https://www.elgentos.nl/
 */

declare(strict_types=1);

namespace Signalise\Plugin\Test\Publisher;

use Magento\Framework\DataObject;
use PHPUnit\Framework\TestCase;
use Signalise\Plugin\Logger\Logger;
use Signalise\Plugin\Publisher\OrderPublisher;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * @coversDefaultClass \Signalise\Plugin\Publisher\OrderPublisher
 */
class OrderPublisherTest extends TestCase
{
    /**
     * @param string $storeId
     *
     * @return void
     *
     * @covers ::__construct
     * @covers ::execute
     * @dataProvider setDataProvider
     */
    public function testExecute(string $storeId): void
    {
        $dataObject = $this->createMock(DataObject::class);

        $subject = new OrderPublisher(
            $this->createJsonMock(
                $dataObject,
                $storeId
            ),
            $this->createPublisherInterfaceMock(),
            $this->createMock(Logger::class)
        );

        $subject->execute(
            $dataObject,
            $storeId
        );
    }

    public function createJsonMock(
        DataObject $orderDataObject,
        string $storeId
    ): Json {
        $json = $this->createMock(Json::class);

        $json->expects(self::once())
            ->method('serialize')
            ->willReturn(
                [
                    'records' => [
                        $orderDataObject->getData()
                    ],
                    'store_id' => $storeId
                ]
            );

        return $json;
    }

    public function createPublisherInterfaceMock(): PublisherInterface
    {
        $publisher = $this->createMock(PublisherInterface::class);

        $publisher->expects(self::once())
            ->method('publish')
            ->with('signalise.order.push')
            ->willReturn(null);

        return $publisher;
    }

    public function setDataProvider(): array
    {
        return [
            'valid_default_store' => [
                'store_id' => "0"
            ],
            'valid_random_store' => [
                'store_id' => "22"
            ]
        ];
    }
}
