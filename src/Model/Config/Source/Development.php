<?php

/**
 * Copyright Elgentos BV. All rights reserved.
 * https://www.elgentos.nl/
 */

declare(strict_types=1);

namespace Signalise\Plugin\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Development implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            true => 'Enabled',
            false => 'Disabled'
        ];
    }
}
