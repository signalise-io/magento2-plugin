<?php

declare(strict_types=1);

namespace Signalise\Plugin\Console\Command;

use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Exception;
use InvalidArgumentException;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Console\Cli;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderRepositoryInterfaceFactory;
use Magento\Sales\Model\Order;
use Magento\Store\Api\StoreRepositoryInterface;
use Signalise\Plugin\Helper\OrderDataObjectHelper;
use Signalise\Plugin\Publisher\OrderPublisher;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Signalise\Plugin\Logger\Logger;
use Magento\Sales\Api\OrderRepositoryInterface;

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
    private const OPTION_PAGE_SIZE                  = 'page-size';
    private const OPTION_CURRENT_PAGE               = 'current-page';

    private OrderPublisher $orderPublisher;
    private OrderDataObjectHelper $orderDataObjectHelper;
    private StoreRepositoryInterface $storeRepository;
    private SearchCriteriaBuilder $searchCriteriaBuilder;
    private OrderRepositoryInterface $orderRepository;
    private Logger $logger;

    public function __construct(
        OrderPublisher $orderPublisher,
        OrderDataObjectHelper $orderDataObjectHelper,
        StoreRepositoryInterface $storeRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Logger $logger,
        OrderRepositoryInterface $orderRepository,
        string $name = self::DEFAULT_COMMAND_NAME,
        string $description = self::DEFAULT_COMMAND_DESCRIPTION
    ) {
        parent::__construct($name);
        $this->setDescription($description);
        $this->orderPublisher           = $orderPublisher;
        $this->orderDataObjectHelper    = $orderDataObjectHelper;
        $this->storeRepository          = $storeRepository;
        $this->searchCriteriaBuilder    = $searchCriteriaBuilder;
        $this->orderRepository          = $orderRepository;
        $this->logger = $logger;
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

        $this->addOption(
            self::OPTION_PAGE_SIZE,
            null,
            InputOption::VALUE_OPTIONAL,
            '',
            1000
        );

        $this->addOption(
            self::OPTION_CURRENT_PAGE,
            null,
            InputOption::VALUE_OPTIONAL,
            '',
            1
        );

        parent::configure();
    }

    private function fetchStoreIdByCode(?string $storeCode): ?string
    {
        try {
            return (string)$this->storeRepository->get($storeCode)->getId();
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }

    public function fetchOrders(
        ?string $startDate,
        ?string $endDate,
        ?string $storeId,
        int $pageSize,
        int $currentPage
    ) {
        $searchCriteria = $this->searchCriteriaBuilder
            ->setPageSize($pageSize)
            ->setCurrentPage($currentPage);

        if($startDate !== null) {
            $searchCriteria->addFilter('created_at', $startDate, 'gteq');
        }

        if($endDate !== null) {
            $searchCriteria->addFilter('created_at', $endDate, 'lteq');
        }

        if($storeId !== null) {
            $searchCriteria->addFilter('store_id', $storeId);
        }

        $orders = $this->orderRepository->getList($searchCriteria->create());

        ///** @var Order $order */
        //foreach($orders as $order) {
        //    $this->pushOrderToQueue($order);
        //
        //    yield $order;
        //}
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
        } catch (Exception $e) {
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
            $storeId,
            (int)$input->getOption(self::OPTION_PAGE_SIZE),
            (int)$input->getOption(self::OPTION_CURRENT_PAGE)
        );

        return Cli::RETURN_SUCCESS;
    }
}
