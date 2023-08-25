<?php

declare(strict_types=1);

namespace Signalise\Plugin\Console\Command;

use Throwable;
use Magento\Framework\Console\Cli;
use Magento\Framework\DataObject;
use Magento\Framework\DB\Helper as ResourceHelper;
use Magento\Framework\Model\ResourceModel\Iterator;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Signalise\Plugin\Helper\OrderDataObjectHelper;
use Signalise\Plugin\Publisher\OrderPublisher;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Signalise\Plugin\Logger\Logger;

class PushOrders extends Command
{
    private const DEFAULT_COMMAND_NAME        = 'signalise:push-orders';
    private const DEFAULT_COMMAND_DESCRIPTION = 'Push all orders or specific order to Signalise Queue.';
    private const ARGUMENT_ORDER              = 'order_id';
    private const ARGUMENT_ORDER_DESCRIPTION  = 'Select the order you want to send to Signalise';

    private OrderPublisher $orderPublisher;

    private OrderDataObjectHelper $orderDataObjectHelper;

    private CollectionFactory $collectionFactory;

    private Logger $logger;

    public function __construct(
        OrderPublisher $orderPublisher,
        OrderDataObjectHelper $orderDataObjectHelper,
        CollectionFactory $collectionFactory,
        Logger $logger,
        Iterator $iterator,
        ResourceHelper $coreResourceHelper,
        string $name = self::DEFAULT_COMMAND_NAME,
        string $description = self::DEFAULT_COMMAND_DESCRIPTION
    ) {
        parent::__construct($name);
        $this->setDescription($description);
        $this->orderPublisher        = $orderPublisher;
        $this->orderDataObjectHelper = $orderDataObjectHelper;
        $this->collectionFactory     = $collectionFactory;
        $this->logger                = $logger;
        $this->iterator              = $iterator;
        $this->_coreResourceHelper   = $coreResourceHelper;
    }

    protected function configure(): void
    {
        $this->addArgument(
            self::ARGUMENT_ORDER,
            InputArgument::OPTIONAL,
            self::ARGUMENT_ORDER_DESCRIPTION
        );

        parent::configure();
    }

    private function pushOrderToQueue(DataObject $order, OutputInterface $output)
    {
        try {
            $dto = $this->orderDataObjectHelper->create($order);

            $this->orderPublisher->execute($dto, (string)$order->getStoreId());

            $output->writeln(
                sprintf('Order_id: %s successfully added to the Signalise queue - %s memory used', $order->getEntityId(), $this->convert(memory_get_usage(true)))
            );
        } catch (Throwable $t) {
            $this->logger->critical(
                $t->getMessage()
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $this->output = $output;
        $orderId = $input->getArgument('order_id');
        $collection = $this->collectionFactory->create();

        if ($orderId) {
            $collection->addFieldToFilter('entity_id', $orderId);
        }

        $this->_addAddressFields($collection);
        $this->_addPaymentFields($collection);
        $this->iterator->walk(
            $collection->getSelect(),
            [[$this, 'callback']]
        );

        return Cli::RETURN_SUCCESS;
    }

    public function callback($args)
    {
        $order = new DataObject();
        $order->setData($args['row']);

        $this->pushOrderToQueue($order, $this->output);
    }

    protected function _addAddressFields($collection)
    {
        $aliases = [
            'billing' => 'billing_o_a',
            'shipping' => 'shipping_o_a'
        ];
        $joinTable = $collection->getTable('sales_order_address');

        foreach ($aliases as $type => $aliasName) {
            $collection->addFilterToMap(
                "{$type}_firstname",
                "{$aliasName}.firstname"
            )->addFilterToMap(
                "{$type}_lastname",
                "{$aliasName}.lastname"
            )->addFilterToMap(
                "{$type}_telephone",
                "{$aliasName}.telephone"
            )->addFilterToMap(
                "{$type}_street",
                "{$aliasName}.street"
            )->addFilterToMap(
                "{$type}_country_id",
                "{$aliasName}.country_id"
            )->addFilterToMap(
                "{$type}_postcode",
                "{$aliasName}.postcode"
            )->addFilterToMap(
                "{$type}_city",
                "{$aliasName}.city"
            );

            $collection->getSelect()->joinLeft(
                [$aliasName => $joinTable],
                "(main_table.entity_id = {$aliasName}.parent_id" .
                " AND {$aliasName}.address_type = '{$type}')",
                [
                    "{$aliasName}.firstname as {$type}_firstname",
                    "{$aliasName}.lastname as {$type}_lastname",
                    "{$aliasName}.telephone as {$type}_telephone",
                    "{$aliasName}.postcode as {$type}_postcode",
                    "{$aliasName}.street as {$type}_street",
                    "{$aliasName}.city as {$type}_city",
                    "{$aliasName}.country_id as {$type}_country_id"
                ]
            );
        }

        $this->_coreResourceHelper->prepareColumnsList($collection->getSelect());
        return $collection;
    }

    protected function _addPaymentFields($collection)
    {
        $joinTable = $collection->getTable('sales_order_payment');
        $paymentAliasName = 'payment';
        $collection->addFilterToMap(
            'payment_method',
            $paymentAliasName . '.method'
        )->addFilterToMap(
            'payment_amount_paid',
            $paymentAliasName . '.amount_paid'
        );
        $collection->getSelect()->joinLeft(
            [$paymentAliasName => $joinTable],
            "main_table.entity_id = {$paymentAliasName}.parent_id",
            [
                $paymentAliasName . '.method as payment_method',
                $paymentAliasName . '.amount_paid as payment_amount_paid'
            ]
        );
        $this->_coreResourceHelper->prepareColumnsList($collection->getSelect());
        return $collection;
    }
}
