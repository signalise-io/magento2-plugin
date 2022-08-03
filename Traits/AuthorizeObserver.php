<?php

declare(strict_types=1);

namespace Signalise\Plugin\Traits;

use Signalise\Plugin\Model\SignaliseConfig;

trait AuthorizeObserver
{
    private SignaliseConfig $signaliseConfig;

    public function __construct(SignaliseConfig $signaliseConfig)
    {
        $this->signaliseConfig = $signaliseConfig;
    }

    private function authorize(string $eventName): bool
    {
        return in_array($eventName, $this->signaliseConfig->getActiveEvents());
    }
}
