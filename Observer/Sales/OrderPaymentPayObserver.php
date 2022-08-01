<?php

declare(strict_types=1);

namespace Signalise\Plugin\Observer\Sales;

use Magento\Framework\Event\Observer;
use Magento\Sales\Model\Order;
use Signalise\Plugin\Helper\OrderDataObjectHelper;
use Signalise\Plugin\Publisher\OrderPublisher;

class OrderPaymentPayObserver
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
     * @return void
     */
    public function execute(
        Observer $observer
    ): void {
        /** @var Order $order */
        $order = $observer->getInvoice()->getOrder();

        $dto = $this->orderDataObjectHelper->create($order);

        $this->orderPublisher->execute($dto);
    }
}
