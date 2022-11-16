<?php

/**
 * Copyright Elgentos BV. All rights reserved.
 * https://www.elgentos.nl/
 */

declare(strict_types=1);

namespace Signalise\Plugin\Console\Command;

use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

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


    private function setConfigData(string $path, string $value): void
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

    private function askQuestion(
        InputInterface $input,
        OutputInterface $output,
        string $question
    ): string {
        $helper = $this->getHelper('question');

        $question = new Question($question);

        return $helper->ask($input, $output, $question);
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        // ask api url && api key



        //$credentials = $this->retrieveSetupCredentials();

        //foreach ($credentials as $credential => $path) {
        //    $this->setConfigData($path, $credential);
        //}

        return Cli::RETURN_SUCCESS;
    }
}
