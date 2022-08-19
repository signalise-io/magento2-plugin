<?php

/**
 * Copyright Elgentos BV. All rights reserved.
 * https://www.elgentos.nl/
 */

declare(strict_types=1);

namespace Signalise\Plugin\Test\Observer\Sales;

use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\TestCase;
use Signalise\Plugin\Helper\OrderDataObjectHelper;
use Signalise\Plugin\Model\SignaliseConfig;
use Signalise\Plugin\Observer\Sales\OrderPlaceAfterObserver;
use Signalise\Plugin\Publisher\OrderPublisher;

/**
 * @coversDefaultClass \Signalise\Plugin\Observer\Sales\OrderPlaceAfterObserver
 */
class OrderPlaceAfterObserverTest extends TestCase
{
    private const ACTIVE_EVENTS = [
        'sales_order_place_after'
    ];
    /**
     *
     * @covers ::__construct
     * @covers ::execute
     * @covers ::authorize
     * @dataProvider setDataProvider
     */
    public function testExecute(
        string $eventName,
        bool $authorize
    ): void {
        $subject = new OrderPlaceAfterObserver(
            $this->createMock(OrderPublisher::class),
            $this->createMock(OrderDataObjectHelper::class),
            $this->createSignaliseConfigMock()
        );

        $subject->execute(
            $this->createObserverMock($eventName, $authorize)
        );
    }

    public function createObserverMock(string $eventName, bool $authorize): Observer
    {
        $observer = $this->createMock(Observer::class);

        $observer
            ->expects(self::exactly($authorize ? 2 : 1))
            ->method('getEvent')
            ->willReturn(
                $this->createEventMock($eventName, $authorize)
            );

        return $observer;
    }

    private function createSignaliseConfigMock(): SignaliseConfig
    {
        $config = $this->createMock(SignaliseConfig::class);
        $config->expects(self::once())
            ->method('getActiveEvents')
            ->willReturn(self::ACTIVE_EVENTS);

        return $config;
    }

    public function createEventMock(string $eventName, bool $authorize): Event
    {
        $event = $this->createMock(Event::class);

        $event
            ->expects(self::once())
            ->method('getName')
            ->willReturn($eventName);

        $event
            ->expects($authorize ? self::once() : self::never())
            ->method('getData')
            ->with('order')
            ->willReturn(
                $this->createMock(Order::class)
            );


        return $event;
    }

    public function setDataProvider(): array
    {
        return [
            'valid' => ['sales_order_place_after', true],
            'invalid' => ['sales_order_payment_pay', false],
        ];
    }
}
