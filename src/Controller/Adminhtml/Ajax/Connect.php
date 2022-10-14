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
use Signalise\Plugin\Model\Config\SignaliseConfig;

class Connect extends Action
{
    protected JsonFactory $resultJsonFactory;

    private ApiClient $apiClient;

    private SignaliseConfig $config;

    private const VALID_CLASS   = 'valid';
    private const INVALID_CLASS = 'invalid';


    public function __construct(
        Context         $context,
        JsonFactory     $resultJsonFactory,
        ApiClient       $apiClient,
        SignaliseConfig $config
    )
    {
        parent::__construct($context);

        $this->resultJsonFactory = $resultJsonFactory;
        $this->apiClient         = $apiClient;
        $this->config            = $config;
    }

    /**
     * @param string|Phrase $message
     */
    private function returnResult($message, string $class): Json
    {
        $data = [
            'message' => $message,
            'class' => $class
        ];

        $result = $this->resultJsonFactory->create();

        return $result->setData($data);
    }

    /**
     * @throws ResponseException
     */
    public function execute(): Json
    {
        try {
            $validConnectIds = $this->apiClient->getConnects(
                $this->config->getApiKey()
            );

            if (in_array($this->config->getConnectId(), $validConnectIds)) {
                return $this->returnResult(
                    __('Connect is valid'),
                    self::VALID_CLASS
                );
            } else {
                return $this->returnResult(
                    __('Connect is invalid'),
                    self::INVALID_CLASS
                );
            }
        } catch (LocalizedException|GuzzleException $exception) {
           return $this->returnResult(
                $exception->getMessage(),
                self::INVALID_CLASS
            );
        }
    }
}
