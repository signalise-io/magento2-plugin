<?php

declare(strict_types=1);

namespace Signalise\Plugin\Helper;

use Magento\Framework\DataObject;
use Magento\Sales\Model\Order;

class OrderDataObjectHelper
{
    public function create(Order $order): DataObject
    {
        $dto = new DataObject();

        return $dto->setData([
            'id' => $order->getIncrementId(),
            'store' => $order->getStore()->getCode(),
            'payment_method' => $order->getPayment()->getMethod(),
            'shipping_method' => $order->getShippingMethod(),
            'grand_total' => $order->getGrandTotal(),
            'base_grand_total' => $order->getBaseGrandTotal(),
            'shipping_amount' => $order->getShippingAmount(),
            'base_shipping_amount' => $order->getBaseShippingAmount(),
            'currency' => $order->getOrderCurrencyCode(),
            'base_currency' => $order->getBaseCurrencyCode(),
        ]);
    }
}
