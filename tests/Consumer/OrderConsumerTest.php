<?php

/**
 * Copyright Elgentos BV. All rights reserved.
 * https://www.elgentos.nl/
 */

declare(strict_types=1);

namespace Signalise\Plugin\Test\Consumer;

use PHPUnit\Framework\TestCase;
use Signalise\Plugin\Consumer\OrderConsumer;
use Signalise\Plugin\Model\SignaliseApiClient;

/**
 * @coversDefaultClass \Signalise\Plugin\Consumer\OrderConsumer
 */
class OrderConsumerTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::processMessage
     */
    public function testProcessMessage(): void
    {
        $subject = new OrderConsumer(
            $this->createSignaliseApiClientMock()
        );

        $subject->processMessage('');
    }

    private function createSignaliseApiClientMock(): SignaliseApiClient
    {
        $client = $this->createMock(SignaliseApiClient::class);

        $client->expects(self::once())
            ->method('pushData');

        return $client;
    }
}
