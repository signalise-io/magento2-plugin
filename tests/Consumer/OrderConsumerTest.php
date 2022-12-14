<?php

/**
 * Copyright Elgentos BV. All rights reserved.
 * https://www.elgentos.nl/
 */

declare(strict_types=1);

namespace Signalise\Plugin\Test\Consumer;

use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\TestCase;
use Signalise\PhpClient\Client\ApiClient;
use Signalise\Plugin\Consumer\OrderConsumer;
use Signalise\Plugin\Logger\Logger;
use Signalise\Plugin\Model\Config\SignaliseConfig;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * @coversDefaultClass \Signalise\Plugin\Consumer\OrderConsumer
 */
class OrderConsumerTest extends TestCase
{
    private const STATUS_CREATED               = 201;
    private const STATUS_UNPROCESSABLE_CONTENT = 422;
    private const STATUS_BAD_REQUEST           = 400;
    private const SIGNALISE_API_URL            = 'https://signalise.io';

    /**
     * @covers ::__construct
     * @covers ::processMessage
     * @throws LocalizedException
     * @dataProvider setDataProvider
     */
    public function testProcessMessage(
        string $serializedData,
        array $responseMessage,
        int $statusCode,
        string $apiKey,
        string $connectId
    ): void {
        $subject = new OrderConsumer(
            $this->createSignaliseConfigMock(
                $statusCode,
                $apiKey,
                $connectId
            ),
            $this->createSignaliseApiClientMock(
                $statusCode,
                $apiKey,
                $connectId,
                $serializedData,
                $responseMessage
            ),
            $this->createMock(Logger::class),
            $this->createJsonMock($serializedData, $statusCode)
        );

        $subject->processMessage($serializedData);
    }

    private function createJsonMock(
        string $serializedData,
        int $statusCode
    ): Json {
        $json = $this->createMock(Json::class);

        $json->expects(self::STATUS_BAD_REQUEST === $statusCode ? self::never() : self::once())
            ->method('unserialize')
            ->willReturn(
                [
                    'records' => [],
                    'store_id' => 3
                ]
            );

        $json->expects(self::STATUS_BAD_REQUEST === $statusCode ? self::never() : self::once())
            ->method('serialize')
            ->willReturn($serializedData);

        return $json;
    }

    private function createSignaliseConfigMock(
        int $statusCode,
        string $apiKey,
        string $connectId
    ): SignaliseConfig {
        $config = $this->createMock(SignaliseConfig::class);

        $config->expects(self::once())
            ->method('isDevelopmentMode')
            ->willReturn($statusCode === self::STATUS_BAD_REQUEST);

        $config->expects($statusCode === self::STATUS_BAD_REQUEST ? self::never() : self::once())
            ->method('getApiKey')
            ->willReturn($apiKey);

        $config->expects($statusCode === self::STATUS_BAD_REQUEST ? self::never() : self::once())
            ->method('getConnectId')
            ->willReturn($connectId);

        $config->expects($statusCode === self::STATUS_BAD_REQUEST ? self::never() : self::once())
            ->method('getApiUrl')
            ->willReturn(self::SIGNALISE_API_URL);

        return $config;
    }

    private function createSignaliseApiClientMock(
        int $statusCode,
        string $apiKey,
        string $connectId,
        string $serializedData,
        array $responseMessage
    ): ApiClient {
        $apiClient = $this->createMock(ApiClient::class);

        if ($statusCode === self::STATUS_CREATED) {
            $apiClient->expects(self::once())
                ->method('postOrderHistory')
                ->with(
                    self::SIGNALISE_API_URL,
                    $apiKey,
                    $serializedData,
                    $connectId
                )->willReturn(
                    $responseMessage
                );
        }

        return $apiClient;
    }

    public function setDataProvider(): array
    {
        return [
            'invalid_development_mode' => [
                'data' => '',
                'responseMessage' => [],
                'statusCode' => self::STATUS_BAD_REQUEST,
                'apiKey' => '',
                'connectId' => ''
            ],
            'valid' => [
                'data' => '{
                    "records": [
                        {
                            "id": 16,
                            "total_products": 25,
                            "total_costs": 124.6500,
                            "valuta": "EUR",
                            "tax": 1.15,
                            "payment_method": "mollie_methods_ideal",
                            "payment_costs": 0.05,
                            "shipping_method": "Flat Rate - Fixed",
                            "shipping_costs": 5.0000,
                            "zip": "1000AA",
                            "street": "Dam",
                            "house_number": "1",
                            "city": "Amsterdam",
                            "country": "NL",
                            "status": "complete",
                            "date": "2021-02-11 18:24:45",
                            "tag": ""
                        }
                    ]
                }',
                'responseMessage' => [
                    'message' => "processed: 1 records"
                ],
                'statusCode' => self::STATUS_CREATED,
                'apiKey' => '43224352',
                'connectId' => '7e618144-3e5f-11ed-b878-0242ac120002'
            ],
            'invalid' => [
                'data' => 'unprocessable entry',
                'responseMessage' => [
                    'message' => "Error while uploading"
                ],
                'statusCode' => self::STATUS_UNPROCESSABLE_CONTENT,
                'apiKey' => '23526382',
                'connectId' => '928a61d6-3e5f-11ed-b878-0242ac120002'
            ]
        ];
    }
}
