<?php

declare(strict_types=1);

namespace Signalise\Plugin\Observer\Sales;

use Magento\Framework\Event\Observer;
use Magento\Sales\Model\Order;
use Signalise\Plugin\Helper\OrderDataObjectHelper;
use Signalise\Plugin\Publisher\OrderPublisher;
use Signalise\Plugin\Traits\AuthorizeObserver;

class OrderPaymentPayObserver
{
    use AuthorizeObserver;

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
        if(!self::authorize($observer->getEvent()->getName())) {
            return;
        }

        /** @var Order $order */
        $order = $observer->getEvent()->getInvoice()->getOrder();

        $dto = $this->orderDataObjectHelper->create($order);

        $this->orderPublisher->execute($dto);
    }
}
