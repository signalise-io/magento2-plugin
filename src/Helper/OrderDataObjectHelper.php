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

    private const HOUSE_NUMBER_PATTERN = "/[^0-9.]/";

    private const STREET_PATTERN = "/[0-9]+/";

    public function __construct(
        TimezoneInterface $timezone
    ) {
        $this->timezone = $timezone;
    }

    /**
     * @throws Exception
     */
    public function create(Order $order, ?string $eventName = ''): DataObject
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
                'payment_costs' => $order->getPayment()->getAmountPaid() ?? '',
                'shipping_method' => $order->getShippingMethod(),
                'shipping_costs' => $order->getShippingAmount(),
                'zip' => $order->getShippingAddress()->getPostcode(),
                'street' => $this->getStreetOrHouseNumber(
                    $order->getShippingAddress()->getStreet()[0] ?? '',
                    self::STREET_PATTERN
                ),
                'house_number' => $this->getStreetOrHouseNumber(
                    $order->getShippingAddress()->getStreet()[0] ?? '',
                    self::HOUSE_NUMBER_PATTERN
                ),
                'city' => $order->getShippingAddress()->getCity(),
                'country' => $order->getShippingAddress()->getCountryId(),
                'status' => $order->getStatus(),
                'date' => $this->createFormattedDate($order->getCreatedAt()),
                'tag' => $eventName
            ]
        );
    }

    private function getStreetOrHouseNumber(string $street, string $pattern): string
    {
        return preg_replace($pattern, "", $street);
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
