<?php

declare(strict_types=1);

namespace Signalise\Plugin\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

class SignaliseConfig
{
    private const XML_PATH_API_KEY       = 'signalise_api_settings/connection/api_key';
    private const XML_PATH_CONNECT_ID    = 'signalise_api_settings/connection/connect_id';
    private const XML_PATH_ACTIVE_EVENTS = 'signalise_api_settings/connection/active_events';
    private const XML_PATH_DEVELOPMENT   = 'signalise_api_settings/debug/development';

    private ScopeConfigInterface $scopeConfig;

    private StoreManagerInterface $storeManager;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager
    ) {
        $this->scopeConfig  = $scopeConfig;
        $this->storeManager = $storeManager;
    }

    /**
     * @throws LocalizedException
     */
    public function getApiKey(): string
    {
        $apiKey = $this->scopeConfig->getValue(
            self::XML_PATH_API_KEY,
            ScopeInterface::SCOPE_STORE,
            Store::DEFAULT_STORE_ID
        );

        if (empty($apiKey)) {
            throw new LocalizedException(
                __('Api key has not been configured.')
            );
        }

        return $apiKey;
    }

    public function getActiveEvents(): array
    {
        $events = $this->scopeConfig->getValue(
            self::XML_PATH_ACTIVE_EVENTS,
            ScopeInterface::SCOPE_STORE,
            Store::DEFAULT_STORE_ID
        );

        return explode(',', $events);
    }

    /**
     * @throws LocalizedException
     */
    public function getConnectId(): string
    {
        $connectId = $this->scopeConfig->getValue(
            self::XML_PATH_CONNECT_ID,
            ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()->getId()
        );

        if (empty($connectId)) {
            throw new LocalizedException(
                __('Connect id has not been configured.')
            );
        }

        return $connectId;
    }

    public function isDevelopmentMode(): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_DEVELOPMENT,
            ScopeInterface::SCOPE_STORE,
            Store::DEFAULT_STORE_ID
        );
    }
}
