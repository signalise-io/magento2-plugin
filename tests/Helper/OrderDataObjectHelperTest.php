<?php

/**
 * Copyright Elgentos BV. All rights reserved.
 * https://www.elgentos.nl/
 */

declare(strict_types=1);

namespace Signalise\Plugin\Test\Helper;

use DateTime;
use Exception;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Address;
use PHPUnit\Framework\TestCase;
use Signalise\Plugin\Helper\OrderDataObjectHelper;

/**
 * @coversDefaultClass \Signalise\Plugin\Helper\OrderDataObjectHelper
 */
class OrderDataObjectHelperTest extends TestCase
{
    /**
     * @return void
     *
     * @covers ::__construct
     * @covers ::create
     * @covers ::createFormattedDate
     * @throws Exception
     */
    public function testCreate(): void
    {
        $subject = new OrderDataObjectHelper(
            $this->createTimezoneInterfaceMock()
        );

        $subject->create(
            $this->createOrderMock()
        );
    }

    private function createOrderMock(): Order
    {
        $order = $this->createMock(Order::class);

        $order
            ->expects(self::once())
            ->method('getPayment')
            ->willReturn(
                $this->createMock(OrderPaymentInterface::class)
            );

        $order->expects(self::any())
            ->method('getShippingAddress')
            ->willReturn(
                $this->createMock(Address::class)
            );

        return $order;
    }

    private function createTimezoneInterfaceMock(): TimezoneInterface
    {
        $timeZoneInterface =  $this->createMock(TimezoneInterface::class);

        $timeZoneInterface->expects(self::once())
            ->method('date')
            ->willReturn(
                $this->createDateTimeMock()
            );

        return $timeZoneInterface;
    }

    private function createDateTimeMock(): DateTime
    {
        $dateTime = $this->createMock(DateTime::class);

        $dateTime->expects(self::once())
            ->method('format')
            ->willReturn(
                '2022-09-28 04:58:56'
            );

        return $dateTime;
    }
}
