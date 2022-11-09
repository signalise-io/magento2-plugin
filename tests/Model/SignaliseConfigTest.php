<?php

/**
 * Copyright Elgentos BV. All rights reserved.
 * https://www.elgentos.nl/
 */

declare(strict_types=1);

namespace Signalise\Plugin\Test\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;
use Signalise\Plugin\Model\Config\SignaliseConfig;

/**
 * @coversDefaultClass \Signalise\Plugin\Model\Config\SignaliseConfig
 */
class SignaliseConfigTest extends TestCase
{
    private const XML_PATH_API_URL       = 'signalise_api_settings/connection/api_url';
    private const XML_PATH_API_KEY       = 'signalise_api_settings/connection/api_key';
    private const XML_PATH_CONNECT_ID    = 'signalise_api_settings/connection/connect_id';
    private const XML_PATH_ACTIVE_EVENTS = 'signalise_api_settings/connection/active_events';
    private const XML_PATH_DEVELOPMENT   = 'signalise_api_settings/debug/development';


    /**
     * @covers ::getApiUrl
     * @covers ::__construct
     * @throws LocalizedException
     * @dataProvider setApiUrlDataProvider
     */
    public function testGetApiUrl(string $value): void
    {
        $subject = new SignaliseConfig(
            $this->createScopeConfigInterfaceMock(
                self::XML_PATH_API_URL,
                $value,
                Store::DEFAULT_STORE_ID
            ),
            $this->createMock(StoreManagerInterface::class)
        );

        if (empty($value)) {
            $this->expectException(LocalizedException::class);
        }

        $subject->getApiUrl();
    }

    /**
     * @covers ::getActiveEvents
     * @covers ::__construct
     */
    public function testGetActiveEvents(): void
    {
        $subject = new SignaliseConfig(
            $this->createScopeConfigInterfaceMock(
                self::XML_PATH_ACTIVE_EVENTS,
                '',
                Store::DEFAULT_STORE_ID
            ),
            $this->createMock(StoreManagerInterface::class)
        );

        $subject->getActiveEvents();
    }

    /**
     * @covers ::getApiKey
     * @covers ::__construct
     * @throws LocalizedException
     * @dataProvider setApiKeyDataProvider
     */
    public function testGetApiKey(string $value): void
    {
        $subject = new SignaliseConfig(
            $this->createScopeConfigInterfaceMock(
                self::XML_PATH_API_KEY,
                $value,
                Store::DEFAULT_STORE_ID
            ),
            $this->createMock(StoreManagerInterface::class)
        );

        if (empty($value)) {
            $this->expectException(LocalizedException::class);
        }

        $subject->getApiKey();
    }

    /**
     * @throws LocalizedException
     * @covers ::getConnectId
     * @dataProvider setConnectIdDataProvider
     */
    public function testGetConnectId(string $connectId, int $storeId): void
    {
        $subject = new SignaliseConfig(
            $this->createScopeConfigInterfaceMock(
                self::XML_PATH_CONNECT_ID,
                $connectId,
                $storeId
            ),
            $this->createStoreManagerInterfaceMock($storeId)
        );

        if (empty($connectId)) {
            $this->expectException(LocalizedException::class);
        }

        $subject->getConnectId();
    }

    /**
     * @covers ::isDevelopmentMode
     */
    public function testIsDevelopmentMode()
    {
        $subject = new SignaliseConfig(
            $this->createScopeConfigInterfaceMock(
                self::XML_PATH_DEVELOPMENT,
                false,
                Store::DEFAULT_STORE_ID
            ),
            $this->createMock(StoreManagerInterface::class)
        );

        $subject->isDevelopmentMode();
    }

    /**
     * @param string      $configPath
     * @param string|bool $returnValue
     * @param int         $storeId
     *
     * @return ScopeConfigInterface
     */
    private function createScopeConfigInterfaceMock(
        string $configPath,
        $returnValue,
        int $storeId
    ): ScopeConfigInterface {
        $scopeConfigInterface = $this->createMock(ScopeConfigInterface::class);

        $scopeConfigInterface
            ->expects(self::once())
            ->method('getValue')
            ->with(
                $configPath,
                ScopeInterface::SCOPE_STORE,
                $storeId
            )->willReturn(
                $returnValue
            );

        return $scopeConfigInterface;
    }

    private function createStoreManagerInterfaceMock(int $storeId): StoreManagerInterface
    {
        $storeManagerInterface = $this->createMock(StoreManagerInterface::class);

        $storeManagerInterface->expects(self::once())
            ->method('getStore')
            ->willReturn(
                $this->createStoreInterfaceMock($storeId)
            );

        return $storeManagerInterface;
    }

    private function createStoreInterfaceMock(int $storeId): StoreInterface
    {
        $storeInterface = $this->createMock(StoreInterface::class);

        $storeInterface->expects(self::once())
            ->method('getId')
            ->willReturn($storeId);

        return $storeInterface;
    }

    public function setConnectIdDataProvider(): array
    {
        return [
            'valid' => [
                'connectId' => '2135325125312',
                'store' => 1
            ],
            'invalid' => [
                'connectId' => '',
                'store' => 0
            ]
        ];
    }

    public function setApiUrlDataProvider(): array
    {
        return [
            'valid' => [
                'https://signalise.nl/'
            ],
            'invalid' => [
                ''
            ]
        ];
    }

    public function setApiKeyDataProvider(): array
    {
        return [
            'valid' => [
                '423848242737',
            ],
            'invalid' => [
                ''
            ]
        ];
    }
}
