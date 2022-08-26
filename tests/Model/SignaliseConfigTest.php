<?php

/**
 * Copyright Elgentos BV. All rights reserved.
 * https://www.elgentos.nl/
 */

declare(strict_types=1);

namespace Signalise\Plugin\Test\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use PHPUnit\Framework\TestCase;
use Signalise\Plugin\Model\Config\SignaliseConfig;

/**
 * @coversDefaultClass \Signalise\Plugin\Model\Config\SignaliseConfig
 */
class SignaliseConfigTest extends TestCase
{
    private const XML_PATH_ACTIVE_EVENTS = 'signalise_api_settings/general/active_events';
    private const XML_PATH_API_URL       = 'signalise_api_settings/general/api_url';

    /**
     * @covers ::getActiveEvents
     * @covers ::__construct
     */
    public function testGetActiveEvents(): void
    {
        $subject = new SignaliseConfig(
            $this->createScopeConfigInterfaceMock(
                self::XML_PATH_ACTIVE_EVENTS,
                ''
            )
        );

        $subject->getActiveEvents();
    }

    /**
     * @covers ::getApiUrl
     * @covers ::__construct
     * @throws LocalizedException
     * @dataProvider setDataProvider
     */
    public function testGetApiUrl(string $value): void
    {
        $subject = new SignaliseConfig(
            $this->createScopeConfigInterfaceMock(
                self::XML_PATH_API_URL,
                $value
            )
        );

        if (empty($value)) {
            $this->expectException(LocalizedException::class);
        }

        $subject->getApiUrl();
    }


    private function createScopeConfigInterfaceMock(
        string $configPath,
        string $returnValue
    ): ScopeConfigInterface {
        $scopeConfigInterface = $this->createMock(ScopeConfigInterface::class);

        $scopeConfigInterface
            ->expects(self::once())
            ->method('getValue')
            ->with(
                $configPath,
                ScopeInterface::SCOPE_STORE,
                Store::DEFAULT_STORE_ID
            )->willReturn(
                empty($returnValue) ? '' : $returnValue
            );

        return $scopeConfigInterface;
    }

    public function setDataProvider(): array
    {
        return [
            'getApiUrlValue' => [
                'api_key',
            ],
            'getApiUrlLocalizedException' => [
                ''
            ]
        ];
    }
}
