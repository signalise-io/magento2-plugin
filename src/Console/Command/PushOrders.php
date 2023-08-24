<?php

declare(strict_types=1);

namespace Signalise\Plugin\Console\Command;

use Exception;
use Magento\Framework\Console\Cli;
use Magento\Sales\Api\OrderRepositoryInterface;
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

    private OrderRepositoryInterface $orderRepository;

    private OrderPublisher $orderPublisher;

    private OrderDataObjectHelper $orderDataObjectHelper;

    private CollectionFactory $collectionFactory;

    private Logger $logger;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        OrderPublisher $orderPublisher,
        OrderDataObjectHelper $orderDataObjectHelper,
        CollectionFactory $collectionFactory,
        Logger $logger,
        \Magento\Framework\Model\ResourceModel\Iterator $iterator,
        \Magento\Framework\DB\Helper $coreResourceHelper,
        string $name = self::DEFAULT_COMMAND_NAME,
        string $description = self::DEFAULT_COMMAND_DESCRIPTION
    ) {
        parent::__construct($name);
        $this->setDescription($description);
        $this->orderRepository       = $orderRepository;
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

    private function fetchOrder(int $orderId): Order
    {
        /** @var Order $order */
        $order = $this->orderRepository->get($orderId);

        return $order;
    }

    private function convert($size)
    {
        $unit=array('b','kb','mb','gb','tb','pb');
        return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
    }

    private function pushOrderToQueue($order, OutputInterface $output)
    {
        if (is_object($order)) {
            try {
                $dto = $this->orderDataObjectHelper->create($order);

                $this->orderPublisher->execute($dto, (string)$order->getStoreId());

                $output->writeln(
                    sprintf('Order_id: %s successfully added to the Signalise queue - %s memory used', $order->getEntityId(), $this->convert(memory_get_usage(true)))
                );
            } catch (Exception $e) {
                $this->logger->critical(
                    $e->getMessage()
                );
            }
        } else {
            try {
                $dto = $this->orderDataObjectHelper->create($order);

                $this->orderPublisher->execute($dto, (string)$order['store_id']);

                $output->writeln(
                    sprintf('Order_id: %s successfully added to the Signalise queue - %s memory used', $order['entity_id'], $this->convert(memory_get_usage(true)))
                );
            } catch (Exception $e) {
                $this->logger->critical(
                    $e->getMessage()
                );
            }
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
        if ($orderId) {
            $order = $this->fetchOrder(
                (int)$orderId
            );

            $this->pushOrderToQueue($order, $output);

            return Cli::RETURN_SUCCESS;
        }

        $collection = $this->collectionFactory->create();

        $useIterator = true;

        if (!$useIterator) {
            $collection->setPageSize(100);

            for ($currentPage = 1; $currentPage <= $collection->getLastPageNumber(); $currentPage++) {
                $collection->clear();
                $collection->setCurPage($currentPage);
                foreach ($collection as $order) {
                    $this->pushOrderToQueue($order, $output);
                    $this->output->writeln(
                        sprintf('Memory used %s', $this->convert(memory_get_usage(true)))
                    );
                }
            }
        } else {
            $this->_addAddressFields($collection);
            $this->_addPaymentFields($collection);
            $this->iterator->walk(
                $collection->getSelect(),
                [[$this, 'callback']]
            );
        }

        return Cli::RETURN_SUCCESS;
    }

    public function callback($args)
    {
        $this->pushOrderToQueue($args['row'], $this->output);
        $this->output->writeln(
            sprintf('Memory used %s', $this->convert(memory_get_usage(true)))
        );
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
                "{$type}_city",
                "{$aliasName}.city"
            )->addFilterToMap(
                "{$type}_country_id",
                "{$aliasName}.country_id"
            )->addFilterToMap(
                "{$type}_postcode",
                "{$aliasName}.postcode"
            );

            $collection->getSelect()->joinLeft(
                [$aliasName => $joinTable],
                "(main_table.entity_id = {$aliasName}.parent_id" .
                " AND {$aliasName}.address_type = '{$type}')",
                [
                    "{$aliasName}.firstname as {$type}_firstname",
                    "{$aliasName}.lastname as {$type}_lastname",
                    "{$aliasName}.telephone as {$type}_telephone",
                    "{$aliasName}.postcode as {$type}_postcode"
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
