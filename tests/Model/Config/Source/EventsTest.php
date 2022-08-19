<?php

/**
 * Copyright Elgentos BV. All rights reserved.
 * https://www.elgentos.nl/
 */

declare(strict_types=1);

namespace Signalise\Plugin\Test\Model\Config\Source;

use PHPUnit\Framework\TestCase;
use Signalise\Plugin\Model\Config\Source\Events;

/**
 * @coversDefaultClass \Signalise\Plugin\Model\Config\Source\Events
 */
class EventsTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::toOptionArray
     */
    public function testToOptionArray(): void
    {
        $subject = new Events(
            [
                ['0' => 'TestEvent'],
                ['1' => 'TestEvent2']
            ]
        );

        $options = $subject->toOptionArray();

        $this->assertIsArray($options);
    }
}
