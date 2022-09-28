<?php

/**
 * Copyright Elgentos BV. All rights reserved.
 * https://www.elgentos.nl/
 */

declare(strict_types=1);

namespace Signalise\Plugin\Test\Consumer;

use PHPUnit\Framework\TestCase;

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
        // @ todo | Add orderConsumerTest

        self::assertTrue(true);
    }
}
