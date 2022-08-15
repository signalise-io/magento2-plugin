<?php

declare(strict_types=1);

namespace Signalise\Plugin\Console\Command;

use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Signalise\Plugin\Helper\OrderDataObjectHelper;
use Signalise\Plugin\Model\SignaliseConfig;
use Signalise\Plugin\Publisher\OrderPublisher;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PushOrder extends Command
{
    private const DEFAULT_COMMAND_NAME = 'signalise:push-order';
    private const DEFAULT_COMMAND_DESCRIPTION = 'Push order to the queue';
    private const COMMAND_EVENT_NAME = 'push_order_command';
    private const ARGUMENT_ORDER = 'order_id';
    private const ARGUMENT_ORDER_DESCRIPTION = 'Select the order you want to send to Signalise';
    private const ARGUMENT_EVENT = 'event';
    private const ARGUMENT_EVENT_DESCRIPTION = 'Select the event you want to trigger';

    private OrderRepositoryInterface $orderRepository;

    private ManagerInterface $eventManager;

    private SignaliseConfig $signaliseConfig;

    private OrderPublisher $orderPublisher;

    private OrderDataObjectHelper $orderDataObjectHelper;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        ManagerInterface $eventManager,
        SignaliseConfig $signaliseConfig,
        OrderPublisher $orderPublisher,
        OrderDataObjectHelper $orderDataObjectHelper,
        string $name = self::DEFAULT_COMMAND_NAME,
        string $description = self::DEFAULT_COMMAND_DESCRIPTION
    ) {
        parent::__construct($name);
        $this->setDescription($description);
        $this->orderRepository = $orderRepository;
        $this->eventManager = $eventManager;
        $this->signaliseConfig = $signaliseConfig;
        $this->orderPublisher = $orderPublisher;
        $this->orderDataObjectHelper = $orderDataObjectHelper;
    }

    protected function configure()
    {
        $this->addArgument(
            self::ARGUMENT_ORDER,
            InputArgument::REQUIRED,
            self::ARGUMENT_ORDER_DESCRIPTION
        );

        $this->addArgument(
            self::ARGUMENT_EVENT,
            InputArgument::OPTIONAL,
            self::ARGUMENT_EVENT_DESCRIPTION
        );

        parent::configure();
    }

    private function fetchOrder(int $orderId): OrderInterface
    {
        return $this->orderRepository->get($orderId);
    }

    private function activeEvent(string $eventName): bool
    {
        return in_array(
            $eventName,
            $this->signaliseConfig->getActiveEvents()
        );
    }

    /**
     * @throws LocalizedException
     */
    private function triggerEvent(string $eventName, OrderInterface $order, OutputInterface $output)
    {
        if(!$this->activeEvent($eventName)) {
            throw new LocalizedException(
                __(
                    sprintf('%s is not active or could not be found.', $eventName)
                )
            );
        }

        $this->eventManager->dispatch(
            $eventName,
            ['order' => $order]
        );

        $output->writeln(
            sprintf('Event: %s successfully triggered with order_id: %s', $eventName, $order->getEntityId())
        );
    }

    /**
     * {@inheritdoc}
     * @throws LocalizedException
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): void {
        /** @var Order $order */
        $order = $this->fetchOrder(
            (int)$input->getArgument('order_id')
        );

        $eventName = $input->getArgument('event');

        if($eventName) {
            $this->triggerEvent($eventName, $order, $output);
            return;
        }

        $dto = $this->orderDataObjectHelper->create($order);

        $this->orderPublisher->execute($dto, self::COMMAND_EVENT_NAME);

        $output->writeln(
            sprintf('Order_id: %s successfully added to the Signalise queue.', $order->getEntityId())
        );
    }
}
