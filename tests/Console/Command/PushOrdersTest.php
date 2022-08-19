<?php

/**
 * Copyright Elgentos BV. All rights reserved.
 * https://www.elgentos.nl/
 */

declare(strict_types=1);

namespace Signalise\Plugin\Test\Console\Command;

use ArrayIterator;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use ReflectionMethod;
use Signalise\Plugin\Console\Command\PushOrders;
use Signalise\Plugin\Helper\OrderDataObjectHelper;
use Signalise\Plugin\Publisher\OrderPublisher;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Sales\Model\ResourceModel\Order\Collection;

/**
 * @coversDefaultClass \Signalise\Plugin\Console\Command\PushOrders
 */
class PushOrdersTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::configure
     * @covers ::fetchOrder
     * @covers ::pushOrderToQueue
     * @covers ::execute
     *
     * @dataProvider setDataProvider
     * @throws ReflectionException
     */
    public function testExecute(int $counter): void
    {
        $isSingle = $counter === 1;
        $subject  = new PushOrders(
            $this->createOrderRepositoryInterfaceMock($isSingle),
            $this->createMock(OrderPublisher::class),
            $this->createMock(OrderDataObjectHelper::class),
            $this->createCollectionFactoryMock($isSingle)
        );

        $reflectionMethod = new ReflectionMethod($subject, 'execute');
        $reflectionMethod->setAccessible(true);

        $reflectionMethod->invoke(
            $subject,
            $this->createInputInterfaceMock($isSingle),
            $this->createMock(OutputInterface::class)
        );
    }

    private function createCollectionFactoryMock(bool $isSingle): CollectionFactory
    {
        $collectionFactory = $this->createMock(CollectionFactory::class);
        $collection        = $this->createMock(Collection::class);

        $collection
            ->expects($isSingle ? self::never() : self::once())
            ->method('getIterator')
            ->willReturn(
                new ArrayIterator(
                    [
                        $this->createMock(Order::class)
                    ]
                )
            );

        $collectionFactory
            ->expects($isSingle ? self::never() : self::once())
            ->method('create')
            ->willReturn(
                $collection
            );

        return $collectionFactory;
    }

    private function createInputInterfaceMock(bool $isSingle): InputInterface
    {
        $inputInterface = $this->createMock(InputInterface::class);

        $inputInterface->expects(self::once())
            ->method('getArgument')
            ->with('order_id')
            ->willReturn(
                $isSingle ? 1 : null
            );

        return $inputInterface;
    }

    private function createOrderRepositoryInterfaceMock(bool $isSingle): OrderRepositoryInterface
    {
        $orderRepositoryInterface = $this->createMock(OrderRepositoryInterface::class);

        $orderRepositoryInterface
            ->expects($isSingle ? self::once() : self::never())
            ->method('get')
            ->willReturn(
                $this->createMock(Order::class)
            );

        return $orderRepositoryInterface;
    }

    public function setDataProvider(): array
    {
        return [
            'singleOrder' => [
                1,
            ],
            'multipleOrders' => [
                10
            ]
        ];
    }
}
