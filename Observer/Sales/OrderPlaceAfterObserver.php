<?php

declare(strict_types=1);

namespace Signalise\Plugin\Observer\Sales;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Signalise\Plugin\Helper\OrderDataObjectHelper;
use Signalise\Plugin\Publisher\OrderPublisher;

class OrderPlaceAfterObserver implements ObserverInterface
{
    private OrderPublisher $orderPublisher;
    private OrderDataObjectHelper $orderDataObjectHelper;

    public function __construct(
        OrderPublisher $orderPublisher,
        OrderDataObjectHelper $orderDataObjectHelper
    ) {
        $this->orderPublisher = $orderPublisher;
        $this->orderDataObjectHelper = $orderDataObjectHelper;
    }

    /**
     * Execute observer
     *
     * @param Observer $observer
     * @return string
     */
    public function execute(
        Observer $observer
    ): void {
        /** @var Order $order */
        $order = $observer->getEvent()->getOrder();

        $dto = $this->orderDataObjectHelper->create($order);

        $this->orderPublisher->execute($dto);
    }
}
