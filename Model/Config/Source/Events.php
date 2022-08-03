<?php

declare(strict_types=1);

namespace Signalise\Plugin\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Signalise\Plugin\Model\Config\RegisteredEvents;

class Events implements OptionSourceInterface
{
    private RegisteredEvents $registeredEvents;

    public function __construct(RegisteredEvents $registeredEvents)
    {
        $this->registeredEvents = $registeredEvents;
    }

    public function toOptionArray(): array
    {
        $options = [];

        foreach ($this->registeredEvents->getRegisteredEvents() as $event) {
            $options[] = [
                'value' => $event,
                'label' => ucfirst(str_replace('_', ' ', $event))
            ];
        }

        return $options;
    }
}
