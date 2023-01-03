<?php

/**
 * Copyright Elgentos BV. All rights reserved.
 * https://www.elgentos.nl/
 */

declare(strict_types=1);

namespace Signalise\Plugin\Model\Order;

use Magento\Sales\Api\Data\OrderSearchResultInterfaceFactory as SearchResultFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\ResourceModel\Metadata;
use Magento\Sales\Model\ResourceModel\Order as ResourceOrder;
use Zend_Db;

class SignaliseOrderRepository extends OrderRepository
{
    private ResourceOrder $resourceOrder;
    private Order $order;

    public function __construct(
        Metadata $metadata,
        SearchResultFactory $searchResultFactory,
        ResourceOrder $resourceOrder,
        Order $order
    ) {
        parent::__construct($metadata, $searchResultFactory);
        $this->resourceOrder = $resourceOrder;
        $this->order = $order;
    }

    public function getOrderById(int $orderId)
    {
        $queryBuilder = $this->resourceOrder->getConnection();

        $select = $queryBuilder->select()
            ->from('sales_order')
            ->where('entity_id = ?', $orderId);

        $data = $queryBuilder->fetchRow($select,[], Zend_Db::FETCH_ASSOC);

        return $this->order->setData(
            $data
        );
    }

}
