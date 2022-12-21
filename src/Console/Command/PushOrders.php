<?php

declare(strict_types=1);

namespace Signalise\Plugin\Console\Command;

use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Exception;
use InvalidArgumentException;
use Magento\Framework\Console\Cli;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\ResourceModel\Iterator;
use Magento\Sales\Api\OrderRepositoryInterfaceFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
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
    private const DEFAULT_COMMAND_DESCRIPTION       = 'Push orders with/without filter to the Signalise queue.';
    private const OPTION_STORE_CODE                 = 'store';
    private const OPTION_STORE_CODE_DESCRIPTION     = 'Select specific store by store code.';
    private const OPTION_CREATED_BEFORE             = 'created-before';
    private const OPTION_CREATED_BEFORE_DESCRIPTION = 'Select filter before date using relative times';
    private const OPTION_CREATED_AFTER              = 'created-after';
    private const OPTION_CREATED_AFTER_DESCRIPTION  = 'Select filter after date using relative times';

    private int $totalOrders;

    private OrderRepositoryInterfaceFactory $orderRepositoryFactory;

    private OrderPublisher $orderPublisher;

    private OrderDataObjectHelper $orderDataObjectHelper;

    private CollectionFactory $collectionFactory;

    private Logger $logger;

    private StoreRepositoryInterface $storeRepository;

    private Iterator $iterator;

    public function __construct(
        OrderRepositoryInterfaceFactory $orderRepositoryFactory,
        OrderPublisher $orderPublisher,
        OrderDataObjectHelper $orderDataObjectHelper,
        CollectionFactory $collectionFactory,
        StoreRepositoryInterface $storeRepository,
        Iterator $iterator,
        Logger $logger,
        string $name = self::DEFAULT_COMMAND_NAME,
        string $description = self::DEFAULT_COMMAND_DESCRIPTION
    ) {
        parent::__construct($name);
        $this->setDescription($description);
        $this->orderRepositoryFactory = $orderRepositoryFactory;
        $this->orderPublisher         = $orderPublisher;
        $this->orderDataObjectHelper  = $orderDataObjectHelper;
        $this->collectionFactory      = $collectionFactory;
        $this->logger                 = $logger;
        $this->storeRepository        = $storeRepository;
        $this->iterator               = $iterator;
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

    private function fetchStoreIdByCode(?string $storeCode): ?string
    {
        try {
            return (string)$this->storeRepository->get($storeCode)->getId();
        } catch (NoSuchEntityException) {
            return null;
        }
    }

    public function fetchOrders(
        ?string $startDate,
        ?string $endDate,
        ?string $storeId
    ): void {
        $orderCollection = $this->collectionFactory->create();

        if($startDate !== null) {
            $orderCollection->addFieldToFilter('created_at', ['gteq' => $startDate]);
        }

        if($endDate !== null) {
            $orderCollection->addFieldToFilter('created_at', ['lteq' => $endDate]);
        }

        if($storeId !== null) {
            $orderCollection->addFilter('store_id', $storeId, 'eq');
        }

        $this->iterator
            ->walk(
                $orderCollection->getSelect(),
                [[$this, 'callback']]
            );

        $this->totalOrders = $orderCollection->count();
    }

    public function callback(array $args): void
    {
        $orderRepository = $this->orderRepositoryFactory->create();
        /** @var Order $order */
        $order = $orderRepository->get(
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
        } catch (Exception) {
            throw new InvalidArgumentException(
                sprintf('%s is not a valid date: %s', $option, $date)
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
        $storeId = $this->fetchStoreIdByCode(
            $input->getOption(self::OPTION_STORE_CODE)
        );

        $createBeforeDate = $this->createDateFromInput(
            $input,
            self::OPTION_CREATED_BEFORE
        );

        $createAfterDate = $this->createDateFromInput(
            $input,
            self::OPTION_CREATED_AFTER
        );

        $output->writeln(
            sprintf("<comment>Filter set: created-before= %s | created-after= %s | storeId= %s</comment>",
                $createBeforeDate,
                $createAfterDate,
                $storeId
            )
        );

        $this->fetchOrders(
            $createBeforeDate,
            $createAfterDate,
            $storeId
        );

        $output->writeln(
            sprintf(
                '<info>Published: %s order(s) to the Signalise queue</info>', $this->totalOrders
            )
        );

        return Cli::RETURN_SUCCESS;
    }
}
