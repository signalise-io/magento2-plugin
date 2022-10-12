<?php

declare(strict_types=1);

namespace Signalise\Plugin\Helper;

use DateTime;
use Exception;
use Magento\Framework\DataObject;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Model\Order;

class OrderDataObjectHelper
{
    private TimezoneInterface $timezone;

    public function __construct(
        TimezoneInterface $timezone
    ) {
        $this->timezone = $timezone;
    }

    /**
     * @throws Exception
     */
    public function create(Order $order): DataObject
    {
        $dto = new DataObject();

        return $dto->setData(
            [
                'id' => $order->getIncrementId(),
                'total_products' => $order->getItems() ? count($order->getItems()) : 0,
                'total_costs' => $order->getGrandTotal(),
                'valuta' => $order->getOrderCurrencyCode(),
                'tax' => $order->getTaxAmount(),
                'payment_method' => $order->getPayment()->getMethod(),
                'payment_costs' => '',
                'shipping_method' => $order->getShippingMethod(),
                'shipping_costs' => $order->getShippingAmount(),
                'zip' => $order->getShippingAddress()->getPostcode(),
                'street' => $order->getShippingAddress() ?? $order->getShippingAddress()->getStreet()[0],
                'house_number' => '',
                'city' => $order->getShippingAddress()->getCity(),
                'country' => $order->getShippingAddress()->getCountryId(),
                'status' => $order->getStatus(),
                'date' => $this->createFormattedDate($order->getCreatedAt()),
                'tag' => ''
            ]
        );
    }
    /**
     * @throws Exception
     */
    private function createFormattedDate(?string $createdAt): string
    {
        return $this->timezone->date(
            new DateTime($createdAt ?? 'now'),
        )->format('Y-m-d H:i:s');
    }
}
