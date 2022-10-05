<?php

declare(strict_types=1);

namespace Signalise\Plugin\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Events implements OptionSourceInterface
{
    private array $events;

    public function __construct(
        array $events = []
    ) {
        $this->events = $events;
    }

    public function toOptionArray(): array
    {
        $options = [];

        foreach ($this->events as $event => $label) {
            $options[] = [
                'value' => $event,
                'label' => $label
            ];
        }

        return $options;
    }
}
