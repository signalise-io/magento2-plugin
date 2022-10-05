<?php

declare(strict_types=1);

namespace Signalise\Plugin\Observer\Sales;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Signalise\Plugin\Helper\OrderDataObjectHelper;
use Signalise\Plugin\Model\Config\SignaliseConfig;
use Signalise\Plugin\Publisher\OrderPublisher;

class OrderPaymentPayObserver implements ObserverInterface
{
    private OrderPublisher $orderPublisher;

    private OrderDataObjectHelper $orderDataObjectHelper;

    private SignaliseConfig $signaliseConfig;

    public function __construct(
        OrderPublisher $orderPublisher,
        OrderDataObjectHelper $orderDataObjectHelper,
        SignaliseConfig $signaliseConfig
    ) {
        $this->orderPublisher        = $orderPublisher;
        $this->orderDataObjectHelper = $orderDataObjectHelper;
        $this->signaliseConfig       = $signaliseConfig;
    }

    private function authorize(string $eventName): bool
    {
        return in_array(
            $eventName,
            $this->signaliseConfig->getActiveEvents(),
            true
        );
    }

    public function execute(
        Observer $observer
    ): void {
        $eventName = $observer->getEvent()->getName();
        if (!$this->authorize($eventName)) {
            return;
        }

        /** @var Order\Invoice $invoice */
        $invoice = $observer->getEvent()->getData('invoice');

        $dto = $this->orderDataObjectHelper->create($invoice->getOrder());

        $this->orderPublisher->execute($dto);
    }
}
