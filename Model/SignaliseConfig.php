<?php

declare(strict_types=1);

namespace Signalise\Plugin\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;

class SignaliseConfig
{
    private const XML_PATH_API_KEY = 'signalise_api_settings/general/api_key';
    private const XML_PATH_ACTIVE_EVENTS = 'signalise_api_settings/general/active_events';

    private ScopeConfigInterface $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
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

        if(empty($apiKey)) {
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
}
