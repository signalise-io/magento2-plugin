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
use Signalise\PhpClient\Client\ApiClient;
use Signalise\PhpClient\Exception\ResponseException;
use Signalise\Plugin\Model\Config\SignaliseConfig;

class Connect extends Action
{
    protected JsonFactory $resultJsonFactory;

    private ApiClient $apiClient;

    private SignaliseConfig $config;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        ApiClient $apiClient,
        SignaliseConfig $config
    ) {
        parent::__construct($context);

        $this->resultJsonFactory = $resultJsonFactory;
        $this->apiClient         = $apiClient;
        $this->config            = $config;
    }

    /**
     * @throws LocalizedException|GuzzleException|ResponseException
     */
    public function execute(): Json
    {
        $validConnectIds = $this->apiClient->getConnects(
            $this->config->getApiKey()
        );

        if(in_array($this->config->getConnectId(), $validConnectIds)) {
            $data = [
                'message' => __('Connect is valid'),
                'class' => 'valid'
            ];
        } else {
            $data = [
                'message' => __('Connect is invalid'),
                'class' => 'invalid'
            ];
        }

        $result = $this->resultJsonFactory->create();

        return $result->setData($data);
    }
}
