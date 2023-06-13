<?php

declare(strict_types=1);

namespace Signalise\Plugin\Console\Command;

use Throwable;
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

    private function pushOrderToQueue(Order $order, OutputInterface $output)
    {
        try {
            $dto = $this->orderDataObjectHelper->create($order);

            $this->orderPublisher->execute($dto, (string)$order->getStoreId());

            $output->writeln(
                sprintf('Order ID %s successfully added to the Signalise queue.', $order->getEntityId())
            );
        } catch (Throwable $t) {
            $output->writeln(sprintf('Order ID %s could not be added to the queue; %s', $order->getEntityId(), $t->getMessage());
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
        $orderId = $input->getArgument('order_id');
        if ($orderId) {
            $order = $this->fetchOrder(
                (int)$orderId
            );

            $this->pushOrderToQueue($order, $output);

            return Cli::RETURN_SUCCESS;
        }

        foreach ($this->collectionFactory->create() as $order) {
            $this->pushOrderToQueue($order, $output);
        }

        return Cli::RETURN_SUCCESS;
    }
}
