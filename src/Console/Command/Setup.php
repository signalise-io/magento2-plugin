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
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Signalise\PhpClient\Client\ApiClient;
use Signalise\PhpClient\Exception\ResponseException;
use Signalise\Plugin\Model\Config\SignaliseConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

class Setup extends Command
{
    private const DEFAULT_COMMAND_NAME                             = 'signalise:setup';
    private const DEFAULT_COMMAND_DESCRIPTION                      = 'Onboard setup command.';
    private const API_KEY_INFO                                     = '<info>Api key: %s</info>';
    private const API_KEY_QUESTION                                 = '<info>Enter api key:</info>';
    private const XML_PATH_API_KEY                                 = 'signalise_api_settings/connection/api_key';
    private const XML_PATH_CONNECT_ID                              = 'signalise_api_settings/connection/connect_id';
    private const DEFAULT_SCOPE                                    = "0";
    private const CONNECT_CREATE_NAME                              = 'Create connect name';
    private const CONNECT_CREATE_NAME_QUESTION                     = '<info>Enter your connect name: </info>';
    private const CONNECT_CREATE_NAME_FROM_SELECTED_STORE          = 'Create connect name from selected store';
    private const CONNECT_CREATE_NAME_FROM_SELECTED_STORE_QUESTION = '<info>Select the store you want to create a connect for:</info>';

    private SignaliseConfig $config;
    private StoreManagerInterface $storeManager;
    private WriterInterface $configWriter;
    private ApiClient $client;
    private StoreRepositoryInterface $storeRepository;

    private bool $defaultScope;

    private string $apiKey;

    public function __construct(
        SignaliseConfig $config,
        StoreManagerInterface $storeManager,
        StoreRepositoryInterface $storeRepository,
        WriterInterface $configWriter,
        ApiClient $client,
        $defaultScope = false,
        string $name = self::DEFAULT_COMMAND_NAME,
        string $description = self::DEFAULT_COMMAND_DESCRIPTION
    ) {
        parent::__construct($name);
        $this->setDescription($description);
        $this->config = $config;
        $this->storeManager = $storeManager;
        $this->configWriter = $configWriter;
        $this->defaultScope = $defaultScope;
        $this->client = $client;
        $this->storeRepository = $storeRepository;
    }

    private function askQuestion(InputInterface $input, OutputInterface $output, string $question): string
    {
        $helper = $this->getHelper('question');

        $question = new Question($question);

        return $helper->ask($input, $output, $question);
    }

    private function setApiKey(InputInterface $input, OutputInterface $output): void
    {
        try {
            $this->apiKey = $this->config->getApiKey();
        } catch (LocalizedException $e) {
            $this->apiKey = $this->askQuestion(
                $input,
                $output,
                self::API_KEY_QUESTION
            );
        }

        $output->writeln(
            sprintf(self::API_KEY_INFO, $this->apiKey)
        );
    }

    private function askChoiceQuestion(
        InputInterface $input,
        OutputInterface $output,
        string $question,
        array $choices
    ): string {
        $helper = $this->getHelper('question');

        $question = new ChoiceQuestion(
            $question,
            $choices,
            0
        );

        return $helper->ask($input, $output, $question);
    }

    private function getStores(): array
    {
        $stores = [];

        foreach($this->storeManager->getStores() as $store) {
            $stores[] = $store->getCode();
        }

        return $stores;
    }

    private function getConnectName(InputInterface $input, OutputInterface $output): string
    {
        switch($this->askChoiceQuestion(
            $input,
            $output,
            self::CONNECT_CREATE_NAME_QUESTION, [
                self::CONNECT_CREATE_NAME,
                self::CONNECT_CREATE_NAME_FROM_SELECTED_STORE
            ]
        )) {
            case self::CONNECT_CREATE_NAME: {
                $this->defaultScope = true;
                return $this->askQuestion($input, $output, self::CONNECT_CREATE_NAME_QUESTION);
            }
            case self::CONNECT_CREATE_NAME_FROM_SELECTED_STORE: {
                return $this->askChoiceQuestion(
                    $input,
                    $output,
                    self::CONNECT_CREATE_NAME_FROM_SELECTED_STORE_QUESTION,
                    $this->getStores()
                );
            }
        }
    }

    /**
     * @throws ResponseException|GuzzleException|LocalizedException
     */
    private function createConnect(string $connectName): array
    {
        return $this->client->createConnect(
            $this->config->getApiUrl(),
            $this->apiKey, [
                'name' => $connectName,
                'type' => 'magento'
            ]
        );
    }

    /**
     * @throws NoSuchEntityException
     */
    private function getStoreId(string $storeCode): string
    {
        return (string)$this->storeRepository->get($storeCode)->getId();
    }

    /**
     * @throws NoSuchEntityException
     */
    private function formatConfigData(array $connect): array
    {
        $scope = [
            'scope' => $this->defaultScope ? ScopeConfigInterface::SCOPE_TYPE_DEFAULT : ScopeInterface::SCOPE_STORES,
            'id' => $this->defaultScope ? self::DEFAULT_SCOPE : $this->getStoreId($connect['data']['name'])
        ];

        $config = [];
        foreach([self::XML_PATH_API_KEY, self::XML_PATH_CONNECT_ID] as $path) {
            $config[] = [
                'path' => $path,
                'value' => $path === self::XML_PATH_API_KEY ? $this->apiKey : $connect['data']['id'],
                'scope' => $path === self::XML_PATH_API_KEY ? ScopeConfigInterface::SCOPE_TYPE_DEFAULT : $scope['scope'],
                'scope_id' => $path === self::XML_PATH_API_KEY ? self::DEFAULT_SCOPE : $scope['id']
            ];
        }

        return $config;
    }

    private function setConfigData(OutputInterface $output, string $path, string $value, string $scope, string $scopeId): void
    {
        $this->configWriter->save(
            $path,
            $value,
            $scope,
            $scopeId
        );

        $output->writeln(
            sprintf(
                "<comment>Saved in Config path: %s with Value: %s for Scope: %s with Scope id: %s</comment>",
                $path,
                $value,
                $scope,
                $scopeId
            )
        );
    }

    /**
     * @throws ResponseException|GuzzleException|LocalizedException
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->setApiKey($input, $output);

        $connectName = $this->getConnectName($input, $output);

        $configData = $this->formatConfigData(
            $this->createConnect($connectName)
        );

        foreach($configData as $config) {
            $this->setConfigData(
                $output,
                $config['path'],
                $config['value'],
                $config['scope'],
                $config['scope_id']
            );
        }

        return Cli::RETURN_SUCCESS;
    }
}
