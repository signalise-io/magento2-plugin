<?php

/**
 * Copyright Elgentos BV. All rights reserved.
 * https://www.elgentos.nl/
 */

declare(strict_types=1);

namespace Signalise\Plugin\Test\Publisher;

use Magento\Framework\DataObject;
use PHPUnit\Framework\TestCase;
use Signalise\Plugin\Publisher\OrderPublisher;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * @coversDefaultClass \Signalise\Plugin\Publisher\OrderPublisher
 */
class OrderPublisherTest extends TestCase
{
    /**
     * @return void
     *
     * @covers ::__construct
     * @covers ::execute
     */
    public function testExecute(): void
    {
        $dataObject = $this->createMock(DataObject::class);

        $subject = new OrderPublisher(
            $this->createMock(Json::class),
            $this->createPublisherInterfaceMock()
        );

        $subject->execute(
            $dataObject
        );
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
}
