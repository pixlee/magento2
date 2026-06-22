<?php
/**
 * Copyright © Emplifi, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Test\Unit\Service;

use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\Store;
use PHPUnit\Framework\MockObject\MockObject;
use Pixlee\Pixlee\Model\Config\Api;
use Pixlee\Pixlee\Model\CookieManager;
use Pixlee\Pixlee\Model\Logger\PixleeLogger;
use Pixlee\Pixlee\Model\Pixlee;
use Pixlee\Pixlee\Service\Analytics;
use Pixlee\Pixlee\Test\Unit\AbstractUnitTestCase;

class AnalyticsTest extends AbstractUnitTestCase
{
    private const API_KEY = 'public-api-key';

    /** @var Curl&MockObject */
    private $curl;

    /** @var Api&MockObject */
    private $apiConfig;

    /** @var CookieManager&MockObject */
    private $cookieManager;

    /** @var PixleeLogger&MockObject */
    private $logger;

    /** @var Pixlee&MockObject */
    private $pixlee;

    /** @var ProductMetadataInterface&MockObject */
    private $productMetadata;

    /** @var Json */
    private $serializer;

    protected function setUp(): void
    {
        $this->curl = $this->createPassiveDouble(Curl::class);
        $this->apiConfig = $this->createPassiveDouble(Api::class);
        $this->cookieManager = $this->createPassiveDouble(CookieManager::class);
        $this->logger = $this->createPassiveDouble(PixleeLogger::class);
        $this->pixlee = $this->createPassiveDouble(Pixlee::class);
        $this->productMetadata = $this->createPassiveDouble(ProductMetadataInterface::class);
        $this->serializer = new Json();

        $this->apiConfig->method('getApiKey')->willReturn(self::API_KEY);
        $this->pixlee->method('getExtensionVersion')->willReturn('3.0.2');
        $this->productMetadata->method('getVersion')->willReturn('2.4.9');
    }

    public function testPreparePayloadReturnsFalseWhenCookieMissing(): void
    {
        $this->cookieManager->method('get')->willReturn(null);

        $subject = $this->createSubject();
        $store = $this->createStoreMock('default');

        $this->assertFalse($subject->preparePayload($store, ['sku' => 'simple']));
    }

    public function testPreparePayloadMergesExtraDataWithoutOverwritingCookieKeys(): void
    {
        $cookiePayload = [
            'CURRENT_PIXLEE_USER_ID' => 'user-123',
            'existing_key' => 'from_cookie',
        ];
        $this->cookieManager->method('get')->willReturn($this->serializer->serialize($cookiePayload));

        $subject = $this->createSubject();
        $store = $this->createStoreMock('default');

        $serialized = $subject->preparePayload($store, [
            'existing_key' => 'should_not_overwrite',
            'sku' => 'simple',
        ]);
        $payload = $this->serializer->unserialize($serialized);

        $this->assertSame('from_cookie', $payload['existing_key']);
        $this->assertSame('simple', $payload['sku']);
        $this->assertSame(self::API_KEY, $payload['API_KEY']);
        $this->assertSame('user-123', $payload['distinct_user_hash']);
        $this->assertSame(Pixlee::PLATFORM, $payload['ecommerce_platform']);
        $this->assertSame('2.4.9', $payload['ecommerce_platform_version']);
        $this->assertSame('3.0.2', $payload['version_hash']);
        $this->assertSame('default', $payload['region_code']);
    }

    public function testSendEventRoutesAddToCartToCorrectPath(): void
    {
        $cookiePayload = ['CURRENT_PIXLEE_USER_ID' => 'user-123'];
        $this->cookieManager->method('get')->willReturn($this->serializer->serialize($cookiePayload));

        $this->curl = $this->createMock(Curl::class);
        $this->curl->expects($this->once())->method('post')->with(
            $this->stringContains(Analytics::ANALYTICS_BASE_URL . 'events/addToCart'),
            $this->callback(function ($body) {
                return is_string($body);
            })
        );
        $this->curl->method('getStatus')->willReturn(200);
        $this->curl->method('getBody')->willReturn(AnalyticsResponseFixtures::JSON_OK_STATUS_BODY);

        $subject = $this->createSubject();
        $store = $this->createStoreMock('default');

        $this->assertTrue($subject->sendEvent('addToCart', ['sku' => 'simple'], $store));
    }

    public function testSendEventRoutesCheckoutSuccessToConversionPath(): void
    {
        $cookiePayload = ['CURRENT_PIXLEE_USER_ID' => 'user-123'];
        $this->cookieManager->method('get')->willReturn($this->serializer->serialize($cookiePayload));

        $this->curl = $this->createMock(Curl::class);
        $this->curl->expects($this->once())->method('post')->with(
            $this->stringContains(Analytics::ANALYTICS_BASE_URL . 'events/conversion'),
            $this->callback(function ($body) {
                return is_string($body);
            })
        );
        $this->curl->method('getStatus')->willReturn(200);
        $this->curl->method('getBody')->willReturn(AnalyticsResponseFixtures::JSON_OK_STATUS_BODY);

        $subject = $this->createSubject();
        $store = $this->createStoreMock('default');

        $this->assertTrue($subject->sendEvent('checkoutSuccess', ['order_id' => 1], $store));
    }

    public function testSendEventReturnsFalseForUnknownEvent(): void
    {
        $subject = $this->createSubject();
        $store = $this->createStoreMock('default');

        $this->assertFalse($subject->sendEvent('unknownEvent', ['sku' => 'simple'], $store));
    }

    public function testSendEventReturnsFalseWhenPayloadPreparationFails(): void
    {
        $this->cookieManager->method('get')->willReturn(null);

        $subject = $this->createSubject();
        $store = $this->createStoreMock('default');

        $this->assertFalse($subject->sendEvent('addToCart', ['sku' => 'simple'], $store));
    }

    public function testPostReturnsFalseWhenHttpStatusFails(): void
    {
        $this->curl->method('getStatus')->willReturn(AnalyticsResponseFixtures::HTTP_SERVER_ERROR);
        $this->curl->method('getBody')->willReturn('error');
        $this->logger = $this->createMock(PixleeLogger::class);
        $this->logger->expects($this->once())->method('error')->with(
            'Invalid HTTP response from Pixlee API',
            [
                'status' => AnalyticsResponseFixtures::HTTP_SERVER_ERROR,
                'body' => 'error',
                'path' => Analytics::ANALYTICS_BASE_URL . 'events/addToCart',
            ]
        );

        $subject = $this->createSubject();

        $this->assertFalse($subject->post('events/addToCart', '{}'));
    }

    public function testPostReturnsBodyOnEmptySuccessResponse(): void
    {
        $this->curl->method('getStatus')->willReturn(AnalyticsResponseFixtures::HTTP_OK);
        $this->curl->method('getBody')->willReturn(AnalyticsResponseFixtures::EMPTY_SUCCESS_BODY);

        $subject = $this->createSubject();

        $this->assertSame(AnalyticsResponseFixtures::EMPTY_SUCCESS_BODY, $subject->post('events/addToCart', '{}'));
    }

    public function testPostReturnsBodyOnOkStatusResponse(): void
    {
        $this->curl->method('getStatus')->willReturn(AnalyticsResponseFixtures::HTTP_OK);
        $this->curl->method('getBody')->willReturn(AnalyticsResponseFixtures::JSON_OK_STATUS_BODY);

        $subject = $this->createSubject();

        $this->assertSame(
            AnalyticsResponseFixtures::JSON_OK_STATUS_BODY,
            $subject->post('events/addToCart', '{}')
        );
    }

    public function testPreparePayloadReturnsFalseWhenPixleeUserIdMissing(): void
    {
        $this->cookieManager->method('get')->willReturn($this->serializer->serialize(['session' => 'only']));

        $subject = $this->createSubject();
        $store = $this->createStoreMock('default');

        $this->assertFalse($subject->preparePayload($store, ['sku' => 'simple']));
    }

    /**
     * @return Store&MockObject
     */
    private function createStoreMock(string $code): Store
    {
        return $this->createConfiguredPassiveDouble(Store::class, [
            'getCode' => $code,
        ]);
    }

    private function createSubject(): Analytics
    {
        return new Analytics(
            $this->apiConfig,
            $this->cookieManager,
            $this->curl,
            $this->logger,
            $this->pixlee,
            $this->productMetadata,
            $this->serializer
        );
    }
}
