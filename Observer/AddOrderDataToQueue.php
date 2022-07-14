<?php

declare(strict_types=1);

namespace Signalise\Plugin\Observer;

use Magento\Framework\DataObject;
use Magento\Framework\Serialize\Serializer\Json;
use Signalise\Plugin\Observer\Frontend\Sales\SignalisePublisher;

class AddOrderDataToQueue implements \Magento\Framework\Event\ObserverInterface
{
    private Json $json;
    private SignalisePublisher $signalisePublisher;

    public function __construct(
        Json $json,
        SignalisePublisher $signalisePublisher
    ) {
        $this->json = $json;
        $this->signalisePublisher = $signalisePublisher;
    }

    /**
     * Execute observer
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(
        \Magento\Framework\Event\Observer $observer
    ) {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getOrder() ?? $observer->getInvoice()->getOrder();
        $dto = new DataObject();
        $dto->setData([
            'id' => $order->getIncrementId(),
            'store' => $order->getStore()->getCode(),
            'payment_method' => $order->getPayment()?->getMethod()?->getCode(),
            'shipping_method' => $order->getShippingMethod()->getCarrierCode(),
            'grand_total' => $order->getGrandTotal(),
            'base_grand_total' => $order->getBaseGrandTotal(),
            'shipping_amount' => $order->getShippingAmount(),
            'base_shipping_amount' => $order->getBaseShippingAmount(),
            'currency' => $order->getOrderCurrencyCode(),
            'base_currency' => $order->getBaseCurrencyCode(),
        ]);

        $this->signalisePublisher->execute($this->json->serialize($dto));
    }
}

