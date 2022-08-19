<?php

/**
 * Copyright Elgentos BV. All rights reserved.
 * https://www.elgentos.nl/
 */

declare(strict_types=1);

namespace Signalise\Plugin\Test\Helper;

use Magento\Framework\DataObject;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\Store;
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
     * @covers ::create
     */
    public function testCreate(): void
    {
        $subject = new OrderDataObjectHelper();

        $subject->create(
            $this->createOrderMock()
        );
    }

    public function createOrderMock(): Order
    {
        $order = $this->createMock(Order::class);

        $order
            ->expects(self::once())
            ->method('getStore')
            ->willReturn(
                $this->createMock(Store::class)
            );

        $order
            ->expects(self::once())
            ->method('getPayment')
            ->willReturn(
                $this->createMock(OrderPaymentInterface::class)
            );

        return $order;
    }
}
