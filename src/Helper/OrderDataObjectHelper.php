<?php

declare(strict_types=1);

namespace Signalise\Plugin\Helper;

use DateTime;
use DateTimeZone;
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
    public function create(DataObject $order, ?string $eventName = ''): DataObject
    {
        $dto = new DataObject();

        return $dto->setData(
            [
                'id' => $order->getIncrementId(),
                'total_products' => $order->getTotalItemCount(),
                'total_costs' => $order->getGrandTotal(),
                'valuta' => $order->getOrderCurrencyCode(),
                'tax' => $order->getTaxAmount(),
                'payment_method' => $order->getPaymentMethod(),
                'payment_costs' => $order->getPaymentAmountPaid() ?? '',
                'shipping_method' => $order->getShippingMethod(),
                'shipping_costs' => $order->getShippingAmount(),
                'country' => $order->getShippingCountryId(),
                'status' => $order->getStatus(),
                'date' => $this->createFormattedDate($order->getCreatedAt()),
                'tag' => $eventName
            ]
        );

    }

    /**
     * @throws Exception
     */
    private function createFormattedDate(?string $createdAt): string
    {
       $date = $this->timezone->date(
           new DateTime($createdAt ?? 'now')
       );

       $date->setTimezone(
            new DateTimeZone('UTC')
       );

       return $date->format('Y-m-d H:i:s');
    }
}
