<?php

declare(strict_types=1);

namespace Signalise\Plugin\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;

class SignaliseConfig
{
    private const XML_PATH_API_URL       = 'signalise_api_settings/connection/api_url';
    private const XML_PATH_API_KEY       = 'signalise_api_settings/connection/api_key';
    private const XML_PATH_CONNECT_ID    = 'signalise_api_settings/connection/connect_id';
    private const XML_PATH_ACTIVE_EVENTS = 'signalise_api_settings/connection/active_events';
    private const XML_PATH_DEVELOPMENT   = 'signalise_api_settings/debug/development';

    private ScopeConfigInterface $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @throws LocalizedException
     */
    public function getApiUrl(int $storeId = 0): string
    {
        $apiKey = $this->scopeConfig->getValue(
            self::XML_PATH_API_URL,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if (empty($apiKey)) {
            throw new LocalizedException(
                __('Api url has not been configured.')
            );
        }

        return $apiKey;
    }

    /**
     * @throws LocalizedException
     */
    public function getApiKey(int $storeId = 0): string
    {
        $apiKey = $this->scopeConfig->getValue(
            self::XML_PATH_API_KEY,
            ScopeInterface::SCOPE_STORE,
            $storeId
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
    public function getConnectId(int $storeId = 0): string
    {
        $connectId = $this->scopeConfig->getValue(
            self::XML_PATH_CONNECT_ID,
            ScopeInterface::SCOPE_STORES,
            $storeId
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
