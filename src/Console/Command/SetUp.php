<?php

/**
 * Copyright Elgentos BV. All rights reserved.
 * https://www.elgentos.nl/
 */

declare(strict_types=1);

namespace Signalise\Plugin\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;

class setUp extends Command
{
    private const DEFAULT_COMMAND_NAME        = 'signalise:setup';
    private const DEFAULT_COMMAND_DESCRIPTION = 'Signalise configuration automatic setup';

    private WriterInterface $configWriter;

    public function __construct(
        WriterInterface $configWriter,
        string $name = self::DEFAULT_COMMAND_NAME,
        string $description = self::DEFAULT_COMMAND_DESCRIPTION
    ) {
        parent::__construct($name);
        $this->setDescription($description);
        $this->configWriter = $configWriter;
    }

    private function setConfigData($path, $value): void
    {
        $this->configWriter->save($path, $value);
    }

    private function retrieveSetupCredentials(): array
    {
        /**
         *  Output: [
         *   'path' => 'value',
         *   'path' => 'value'
         */

        /** @todo Retrieve data from /setup signalise endpoint */

        return [];
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $credentials = $this->retrieveSetupCredentials();

        foreach ($credentials as $credential => $path) {
            $this->setConfigData($path, $credential);
        }

        return 0;
    }
}
