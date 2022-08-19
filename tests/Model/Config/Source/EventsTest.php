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
     * @dataProvider setDataProvider
     */
    public function testToOptionArray(array $events): void
    {
        $subject = new Events(
            $events
        );

        $options = $subject->toOptionArray();

        $this->assertIsArray($options);
        $this->assertEquals(count($events), count($options));
    }

    public function setDataProvider(): array
    {
        return [
            'single_event' => [
                [
                    'value_1' => 'label_1'
                ]
            ],
            'multiple_events' => [
                [
                    'value_1' => 'label_1',
                    'value_2' => 'label_2',
                    'value_3' => 'label_2'
                ]
            ]
        ];
    }
}
