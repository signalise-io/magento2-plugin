<?php

/**
 * Copyright Elgentos BV. All rights reserved.
 * https://www.elgentos.nl/
 */

declare(strict_types=1);

namespace Signalise\Plugin\Console\Command;

use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\Console\Cli;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Signalise\PhpClient\Client\ApiClient;
use Signalise\PhpClient\Exception\ResponseException;
use Signalise\Plugin\Logger\Logger;
use Signalise\Plugin\Model\Config\SignaliseConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Symfony\Component\Console\Question\Question;

class setUp extends Command
{
    private const XML_PATH_API_URL            = 'signalise_api_settings/connection/api_url';
    private const XML_PATH_API_KEY            = 'signalise_api_settings/connection/api_key';
    private const XML_PATH_CONNECT_ID         = 'signalise_api_settings/connection/connect_id';
    private const ENTER_API_URL_LABEL         = '<info>Enter api url: </info>';
    private const ENTER_API_KEY_LABEL         = '<info>Enter api key: </info>';
    private const DEFAULT_COMMAND_NAME        = 'signalise:setup';
    private const DEFAULT_COMMAND_DESCRIPTION = 'Signalise configuration automatic setup';
    private const DEFAULT_TYPE                = 'magento';
    private const OVERRIDE_NAME               = 'override';
    private const OVERRIDE_DESCRIPTION        = 'Override api url and api key value in config';

    private WriterInterface $configWriter;

    private StoreManagerInterface $storeManager;

    private Logger $logger;

    private ApiClient $client;

    private SignaliseConfig $config;

    private string $apiUrl;

    private string $apiKey;

    public function __construct(
        WriterInterface $configWriter,
        StoreManagerInterface $storeManager,
        ApiClient $client,
        Logger $logger,
        SignaliseConfig $config,
        string $name = self::DEFAULT_COMMAND_NAME,
        string $description = self::DEFAULT_COMMAND_DESCRIPTION
    ) {
        parent::__construct($name);
        $this->setDescription($description);

        $this->addOption(
            self::OVERRIDE_NAME,
            'o',
            InputOption::VALUE_REQUIRED,
            self::OVERRIDE_DESCRIPTION
        );

        $this->configWriter = $configWriter;
        $this->storeManager = $storeManager;
        $this->client       = $client;
        $this->logger       = $logger;
        $this->config       = $config;
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

    /**
     * @throws ResponseException|GuzzleException
     */
    private function retrieveSetupCredentials(array $formData): array
    {
        $response = $this->client->createConnect(
            $this->apiUrl,
            $this->apiKey,
            $formData
        );

        return [
            self::XML_PATH_API_URL => $this->apiUrl,
            self::XML_PATH_API_KEY => $this->apiKey,
            self::XML_PATH_CONNECT_ID => $response['data']['id']
        ];
    }

    /**
     * @throws LocalizedException
     */
    private function retrieveAnswerData(
        InputInterface $input,
        OutputInterface $output,
        string $storeName
    ): array {
        if($input->getOption(self::OVERRIDE_NAME)) {
            $this->apiUrl =  $this->askQuestion($input, $output, self::ENTER_API_URL_LABEL);
            $this->apiKey =  $this->askQuestion($input, $output, self::ENTER_API_KEY_LABEL);
        } else {
            $this->apiUrl = $this->config->getApiUrl();
            $this->apiKey = $this->config->getApiKey();
        }

        return [
            'name' => $storeName,
            'type' => self::DEFAULT_TYPE
        ];
    }

    private function setConfigData(OutputInterface $output, string $path, string $value): void
    {
        /** @todo set config path for given argument store */
        $this->configWriter->save($path, $value);

        $output->writeln(
            sprintf('<comment>Saved: %s in config with value: %s</comment>', $path, $value)
        );
    }

    /**
     * @throws NoSuchEntityException|ResponseException|GuzzleException|LocalizedException
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        /**
         * @todo give option that you can select which store you want to create a connect for.
         */

        $formData = $this->retrieveAnswerData(
            $input,
            $output,
            $this->storeManager->getStore()->getName()
        );

        foreach($this->retrieveSetupCredentials($formData) as $path => $value) {
            $this->setConfigData(
                $output,
                $path,
                $value
            );
        }

        return Cli::RETURN_SUCCESS;
    }
}
