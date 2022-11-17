<?php

/**
 * Copyright Elgentos BV. All rights reserved.
 * https://www.elgentos.nl/
 */

declare(strict_types=1);

namespace Signalise\Plugin\Console\Command;

use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Console\Cli;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
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
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

class Setup extends Command
{
    private const XML_PATH_API_URL                    = 'signalise_api_settings/connection/api_url';
    private const XML_PATH_API_KEY                    = 'signalise_api_settings/connection/api_key';
    private const XML_PATH_CONNECT_ID                 = 'signalise_api_settings/connection/connect_id';
    private const ENTER_API_URL_LABEL                 = '<info>Enter api url: </info>';
    private const ENTER_API_KEY_LABEL                 = '<info>Enter api key: </info>';
    private const DEFAULT_COMMAND_NAME                = 'signalise:setup';
    private const DEFAULT_COMMAND_DESCRIPTION         = 'Signalise configuration automatic setup';
    private const DEFAULT_TYPE                        = 'magento';
    private const SKIP_CREDENTIALS_OPTION_NAME        = 'skip-credentials';
    private const SKIP_CREDENTIALS_OPTION_DESCRIPTION = 'Skip the url and key credentials step';
    private const STORE_CODE_OPTION_NAME              = 'select-store';
    private const STORE_CODE_OPTION_DESCRIPTION       = 'Select the store code you want to create a connection for';
    private const DEFAULT_STORES_TYPE_NAME            = 'stores';

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
            self::SKIP_CREDENTIALS_OPTION_NAME,
            's',
            InputOption::VALUE_NONE,
            self::SKIP_CREDENTIALS_OPTION_DESCRIPTION
        );

        $this->addOption(
            self::STORE_CODE_OPTION_NAME,
            'c',
            InputOption::VALUE_NONE,
            self::STORE_CODE_OPTION_DESCRIPTION
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
        StoreInterface $store
    ): array {
        if($input->getOption(self::SKIP_CREDENTIALS_OPTION_NAME)) {
            $this->apiUrl = $this->config->getApiUrl();
            $this->apiKey = $this->config->getApiKey();
        } else {
            $this->apiUrl =  $this->askQuestion($input, $output, self::ENTER_API_URL_LABEL);
            $this->apiKey =  $this->askQuestion($input, $output, self::ENTER_API_KEY_LABEL);
        }

        return [
            'name' => $store->getName(),
            'type' => self::DEFAULT_TYPE
        ];
    }

    /**
     * @throws NoSuchEntityException
     */
    private function retrieveStore(InputInterface $input, OutputInterface $output): StoreInterface
    {
        $stores = [];
        foreach($this->storeManager->getStores() as $store) {
            $stores[] = $store->getCode();
        }

        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion(
            'Select the store you want to create a connection for.',
            $stores,
            0
        );

        $question->setErrorMessage('Store %s is invalid.');

        return $this->storeManager->getStore(
           $helper->ask($input, $output, $question)
        );
    }

    private function defaultStoreConfigCheck(StoreInterface $store): bool
    {
        if($store->getCode() === ScopeConfigInterface::SCOPE_TYPE_DEFAULT) {
            return true;
        }

        return false;
    }

    private function setConfigData(OutputInterface $output, string $path, string $value, StoreInterface $store): void
    {
        $this->configWriter->save(
            $path,
            $value,
            $this->defaultStoreConfigCheck($store) ? ScopeConfigInterface::SCOPE_TYPE_DEFAULT : self::DEFAULT_STORES_TYPE_NAME,
            $this->defaultStoreConfigCheck($store) ? 0 : $store->getStoreGroupId()
        );

        $output->writeln(
            sprintf('<comment>Saved: %s in config with value: %s</comment>', $path, $value)
        );
    }

    /**
     * @throws NoSuchEntityException|ResponseException|GuzzleException|LocalizedException
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        if($input->getOption(self::STORE_CODE_OPTION_NAME)) {
            $store = $this->retrieveStore($input, $output);
        } else {
            $store = $this->storeManager->getStore();
        }

        $formData = $this->retrieveAnswerData(
            $input,
            $output,
            $store
        );

        foreach($this->retrieveSetupCredentials($formData) as $path => $value) {
            $this->setConfigData(
                $output,
                $path,
                $value,
                $store
            );
        }

        return Cli::RETURN_SUCCESS;
    }
}
