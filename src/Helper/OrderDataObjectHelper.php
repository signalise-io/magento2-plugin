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
                'zip' => $order->getShippingPostcode(),
                'street' => $this->getStreetOrHouseNumber(
                    $order->getShippinStreet()[0] ?? '',
                    self::STREET_PATTERN
                ),
                'house_number' => $this->getStreetOrHouseNumber(
                    $order->getShippingStreet()[0] ?? '',
                    self::HOUSE_NUMBER_PATTERN
                ),
                'city' => $order->getShippingCity(),
                'country' => $order->getShippingCountryId(),
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
