<?php

/**
 * Copyright Elgentos BV. All rights reserved.
 * https://www.elgentos.nl/
 */

declare(strict_types=1);

namespace Signalise\Plugin\Test\Consumer;

use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\TestCase;
use Signalise\PhpClient\Client\ApiClient;
use Signalise\PhpClient\Exception\ResponseException;
use Signalise\Plugin\Consumer\OrderConsumer;
use Signalise\Plugin\Model\Config\SignaliseConfig;

/**
 * @coversDefaultClass \Signalise\Plugin\Consumer\OrderConsumer
 */
class OrderConsumerTest extends TestCase
{
    private const STATUS_CREATED               = 201;
    private const STATUS_UNPROCESSABLE_CONTENT = 422;
    private const STATUS_BAD_REQUEST           = 400;

    /**
     * @covers ::__construct
     * @covers ::processMessage
     * @throws LocalizedException|GuzzleException|ResponseException
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
            )
        );

        if ($statusCode === self::STATUS_UNPROCESSABLE_CONTENT) {
            $this->expectException(
                ResponseException::class
            );
        }

        $subject->processMessage($serializedData);
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

        if ($statusCode === self::STATUS_UNPROCESSABLE_CONTENT) {
            $apiClient->expects(self::once())
                ->method('postOrderHistory')
                ->with(
                    $apiKey,
                    $serializedData,
                    $connectId
                )->willThrowException(
                    $this->createMock(ResponseException::class)
                );
        }

        if ($statusCode === self::STATUS_CREATED) {
            $apiClient->expects(self::once())
                ->method('postOrderHistory')
                ->with(
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
            'developmentMode' => [
                'data' => '',
                'responseMessage' => [],
                'statusCode' => self::STATUS_BAD_REQUEST,
                'apiKey' => '',
                'connectId' => ''
            ],
            'successful' => [
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
            'failed' => [
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
