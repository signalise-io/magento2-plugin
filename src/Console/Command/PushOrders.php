<?php

declare(strict_types=1);

namespace Signalise\Plugin\Console\Command;

use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Throwable;
use InvalidArgumentException;
use Magento\Framework\Console\Cli;
use Magento\Framework\DataObject;
use Magento\Framework\DB\Helper as ResourceHelper;
use Magento\Framework\Model\ResourceModel\Iterator;
use Magento\Sales\Model\Order;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Signalise\Plugin\Helper\OrderDataObjectHelper;
use Signalise\Plugin\Publisher\OrderPublisher;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Signalise\Plugin\Logger\Logger;

class PushOrders extends Command
{
    private const DEFAULT_COMMAND_NAME              = 'signalise:push-orders-to-queue';
    private const DEFAULT_COMMAND_DESCRIPTION       = 'Push orders with/without filter to the Signalise queue.';
    private const ARGUMENT_ORDER                    = 'order_id';
    private const ARGUMENT_ORDER_DESCRIPTION        = 'Select the order you want to send to Signalise';
    private const OPTION_STORE_CODE                 = 'store';
    private const OPTION_STORE_CODE_DESCRIPTION     = 'Select specific store by store code';
    private const OPTION_CREATED_BEFORE             = 'before';
    private const OPTION_CREATED_BEFORE_DESCRIPTION = 'Select filter before date using relative times';
    private const OPTION_CREATED_AFTER              = 'after';
    private const OPTION_CREATED_AFTER_DESCRIPTION  = 'Select filter after date using relative times';

    private OutputInterface $output;

    private OrderPublisher $orderPublisher;

    private OrderDataObjectHelper $orderDataObjectHelper;

    private CollectionFactory $collectionFactory;

    private Logger $logger;

    private Iterator $iterator;

    private ResourceHelper $coreResourceHelper;

    private StoreRepositoryInterface $storeRepository;

    public function __construct(
        OrderPublisher $orderPublisher,
        OrderDataObjectHelper $orderDataObjectHelper,
        CollectionFactory $collectionFactory,
        Logger $logger,
        Iterator $iterator,
        ResourceHelper $coreResourceHelper,
        StoreRepositoryInterface $storeRepository,
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
        $this->coreResourceHelper    = $coreResourceHelper;
        $this->storeRepository       = $storeRepository;
    }

    protected function configure(): void
    {
        $this->addArgument(
            self::ARGUMENT_ORDER,
            InputArgument::OPTIONAL,
            self::ARGUMENT_ORDER_DESCRIPTION
        );

        $this->addOption(
            self::OPTION_STORE_CODE,
            null,
            InputOption::VALUE_OPTIONAL,
            self::OPTION_STORE_CODE_DESCRIPTION
        );

        $this->addOption(
            self::OPTION_CREATED_BEFORE,
            null,
            InputOption::VALUE_OPTIONAL,
            self::OPTION_CREATED_BEFORE_DESCRIPTION
        );

        $this->addOption(
            self::OPTION_CREATED_AFTER,
            null,
            InputOption::VALUE_OPTIONAL,
            self::OPTION_CREATED_AFTER_DESCRIPTION
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
        $collection = $this->collectionFactory->create();

        $orderId = $input->getArgument('order_id');
        if ($orderId) {
            $collection->addFieldToFilter('entity_id', $orderId);
        }

        $store = $input->getOption(self::OPTION_STORE_CODE);
        if ($store) {
            $storeId = is_numeric($store) ? $store : $this->fetchStoreIdByCode($store);
            $collection->addFieldToFilter('store_id', $storeId);
        }

        $createBeforeDate = $this->createDateFromInput(
            $input,
            self::OPTION_CREATED_BEFORE
        );
        if ($createBeforeDate) {
            $collection->addFieldToFilter('created_at', ['lt' => $createBeforeDate]);
        }

        $createAfterDate = $this->createDateFromInput(
            $input,
            self::OPTION_CREATED_AFTER
        );
        if ($createAfterDate) {
            $collection->addFieldToFilter('created_at', ['gt' => $createAfterDate]);
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
                "{$type}_country_id",
                "{$aliasName}.country_id"
            );

            $collection->getSelect()->joinLeft(
                [$aliasName => $joinTable],
                "(main_table.entity_id = {$aliasName}.parent_id" .
                " AND {$aliasName}.address_type = '{$type}')",
                [
                    "{$aliasName}.country_id as {$type}_country_id"
                ]
            );
        }

        $this->coreResourceHelper->prepareColumnsList($collection->getSelect());
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
        $this->coreResourceHelper->prepareColumnsList($collection->getSelect());
        return $collection;
    }

    private function fetchStoreIdByCode(?string $storeCode): ?string
    {
        try {
            return (string)$this->storeRepository->get($storeCode)->getId();
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }

    private function createDateFromInput(InputInterface $input, string $option): ?string
    {
        $date = $input->getOption($option);

        if (!$date) {
            return null;
        }

        try {
            return DateTimeImmutable::createFromMutable(
                new DateTime($date, new DateTimeZone('UTC'))
            )->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            throw new InvalidArgumentException(
                sprintf('%s is not a valid date: %s', $option, $date)
            );
        }
    }

    private function convert($size)
    {
        $unit=array('b','kb','mb','gb','tb','pb');
        return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
    }
}
