<?php

/**
 * Copyright Elgentos BV. All rights reserved.
 * https://www.elgentos.nl/
 */

declare(strict_types=1);

namespace Signalise\Plugin\Controller\Adminhtml\Ajax;

use GuzzleHttp\Exception\GuzzleException;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Signalise\PhpClient\Client\ApiClient;
use Signalise\PhpClient\Exception\ResponseException;
use Signalise\Plugin\Logger\Logger;
use Signalise\Plugin\Model\Config\SignaliseConfig;

class Connect extends Action
{
    private const VALID_CLASS   = 'valid';
    private const VALID_LABEL   = 'Connect is valid';
    private const INVALID_CLASS = 'invalid';
    private const INVALID_LABEL = 'Connect is invalid, check error log for more information.';

    protected JsonFactory $resultJsonFactory;

    private ApiClient $apiClient;

    private SignaliseConfig $config;

    private Logger $logger;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        ApiClient $apiClient,
        SignaliseConfig $config,
        Logger $logger
    ) {
        parent::__construct($context);

        $this->resultJsonFactory = $resultJsonFactory;
        $this->apiClient         = $apiClient;
        $this->config            = $config;
        $this->logger            = $logger;
    }

    /**
     * @param string|Phrase $message
     */
    private function returnResult($message, string $class): Json
    {
        $result = $this->resultJsonFactory->create();

        return $result->setData(
            [
                'message' => $message,
                'class' => $class
            ]
        );
    }

    /**
     * @throws ResponseException
     */
    public function execute(): Json
    {
        try {
            $validConnectIds = $this->apiClient->getConnects(
                $this->config->getApiUrl(),
                $this->config->getApiKey()
            );

            if (!in_array($this->config->getConnectId(), $validConnectIds)) {
                $this->logger->critical(
                    __(self::INVALID_LABEL)
                );

                return $this->returnResult(
                    __(self::INVALID_LABEL),
                    self::INVALID_CLASS
                );
            }

            return $this->returnResult(
                __(self::VALID_LABEL),
                self::VALID_CLASS
            );
        } catch (LocalizedException | GuzzleException $exception) {
            $this->logger->critical(
                $exception->getMessage()
            );

            return $this->returnResult(__(self::INVALID_LABEL), self::INVALID_CLASS);
        }
    }
}
