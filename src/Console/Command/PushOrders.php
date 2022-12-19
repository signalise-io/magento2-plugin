<?php

declare(strict_types=1);

namespace Signalise\Plugin\Console\Command;

use Exception;
use InvalidArgumentException;
use Magento\Framework\Console\Cli;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\ResourceModel\Iterator;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Signalise\Plugin\Helper\OrderDataObjectHelper;
use Signalise\Plugin\Publisher\OrderPublisher;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Signalise\Plugin\Logger\Logger;

class PushOrders extends Command
{
    private const DEFAULT_COMMAND_NAME              = 'signalise:push-orders';
    private const DEFAULT_COMMAND_DESCRIPTION       = 'Push all orders or specific order to Signalise Queue.';
    private const OPTION_STORE_CODE                 = 'store';
    private const OPTION_STORE_CODE_DESCRIPTION     = 'Select specific store by store code.';
    private const OPTION_CREATED_BEFORE             = 'created-before';
    private const OPTION_CREATED_BEFORE_DESCRIPTION = 'Select filter before date';
    private const OPTION_CREATED_AFTER              = 'created-after';
    private const OPTION_CREATED_AFTER_DESCRIPTION  = 'Select filter after date';
    private const PUBLISH_INFO                      = '<info>Published: %s order(s) to the Signalise queue</info>';

    private int $totalOrders;

    private OrderRepositoryInterface $orderRepository;

    private OrderPublisher $orderPublisher;

    private OrderDataObjectHelper $orderDataObjectHelper;

    private CollectionFactory $collectionFactory;

    private Logger $logger;

    private StoreRepositoryInterface $storeRepository;

    private Iterator $iterator;

    private Collection $collection;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        OrderPublisher $orderPublisher,
        OrderDataObjectHelper $orderDataObjectHelper,
        CollectionFactory $collectionFactory,
        StoreRepositoryInterface $storeRepository,
        Iterator $iterator,
        Logger $logger,
        Collection $collection,
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
        $this->storeRepository       = $storeRepository;
        $this->iterator              = $iterator;
        $this->collection            = $collection;
    }

    protected function configure(): void
    {
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

    private function fetchStoreByCode(?string $storeCode): ?StoreInterface
    {
        try {
            return $this->storeRepository->get($storeCode);
        } catch (NoSuchEntityException) {
            return null;
        }
    }

    public function fetchOrders(
        ?string $startDate,
        ?string $endDate,
        ?string $storeId
    ): void {
        if ($startDate !== null && !$this->isValidDate($startDate)) {
            throw new InvalidArgumentException(
                sprintf('Start date is not a valid date: %s', $startDate)
            );
        }

        if ($endDate !== null && !$this->isValidDate($endDate)) {
            throw new InvalidArgumentException(
                sprintf('End date is not a valid date: %s', $endDate)
            );
        }

        $orderCollection = $this->collectionFactory->create();

        /**
         * @todo bug fix if startDate & endDate = null, it will receive 0.
         */
        $orderCollection->addFieldToFilter('created_at', array('gteq' => $startDate));
        $orderCollection->addFieldToFilter('created_at', array('lteq' => $endDate));

        if($storeId) {
            $orderCollection->addFilter('store_id', $storeId, 'eq');
        }

        $this->iterator
            ->walk(
                $this->collection->getSelect(),
                [[$this, 'callback']]
            );

        $this->totalOrders = $orderCollection->count();
    }

    /**
     * @param $args
     */
    public function callback($args): void
    {
        /** @var Order $order */
        $order = $this->orderRepository->get(
            $args['row']['entity_id']
        );

        $this->pushOrderToQueue($order);
    }

    private function pushOrderToQueue(Order $order): void
    {
        try {
            $dto = $this->orderDataObjectHelper->create($order);

            $this->orderPublisher->execute($dto, (string)$order->getStoreId());
        } catch (Exception $e) {
            $this->logger->critical(
                $e->getMessage()
            );
        }
    }

    private function isValidDate(string $date): bool
    {
        if (date_create_from_format('Y-m-d', $date) === false) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $store = $this->fetchStoreByCode(
            $input->getOption(self::OPTION_STORE_CODE)
        );

        $this->fetchOrders(
            $input->getOption(self::OPTION_CREATED_BEFORE),
            $input->getOption(self::OPTION_CREATED_AFTER),
            (string)$store?->getId()
        );

        $output->writeln(
            sprintf(
                self::PUBLISH_INFO, $this->totalOrders
            )
        );

        return Cli::RETURN_SUCCESS;
    }
}
