<?php
/**
 * Copyright © Emplifi, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Test\Unit\Service;

use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Pixlee\Pixlee\Model\Config\Api;
use Pixlee\Pixlee\Model\Logger\PixleeLogger;
use Pixlee\Pixlee\Model\Pixlee;
use Pixlee\Pixlee\Service\Distillery;
use ReflectionException;
use ReflectionMethod;

class DistilleryTest extends TestCase
{
    private const PRIVATE_KEY = 'test-private-key';
    private const SECRET_KEY = 'test-secret-key';

    /** @var Curl&MockObject */
    private $curl;

    /** @var Api&MockObject */
    private $apiConfig;

    /** @var PixleeLogger&MockObject */
    private $logger;

    /** @var Json */
    private $serializer;

    protected function setUp(): void
    {
        $this->curl = $this->createMock(Curl::class);
        $this->apiConfig = $this->createMock(Api::class);
        $this->logger = $this->createMock(PixleeLogger::class);
        $this->serializer = new Json();

        $this->apiConfig->method('getPrivateApiKey')->willReturn(self::PRIVATE_KEY);
        $this->apiConfig->method('getSecretKey')->willReturn(self::SECRET_KEY);
    }

    public function testGenerateSignatureProducesExpectedHmac(): void
    {
        $payload = '{"title":"Test Product"}';
        $expected = base64_encode(hash_hmac('sha256', $payload, self::SECRET_KEY, true));

        $this->assertSame($expected, $this->invokeProtected('generateSignature', [$payload]));
    }

    public function testGetRequiredQueryStringIncludesPrivateApiKey(): void
    {
        $subject = $this->createSubject();
        $subject->setScope(ScopeInterface::SCOPE_WEBSITES, 1);

        $this->assertSame('?api_key=' . self::PRIVATE_KEY, $this->invokeProtected('getRequiredQueryString', []));
    }

    public function testPostSetsSignatureHeadersAndReturnsBodyOnSuccess(): void
    {
        $payload = '{"album_type":"product"}';
        $responseBody = '{"status":200}';

        $this->curl->expects($this->once())
            ->method('setHeaders')
            ->with($this->callback(function (array $headers) use ($payload): bool {
                $expectedSignature = base64_encode(hash_hmac('sha256', $payload, self::SECRET_KEY, true));

                return ($headers['Content-Type'] ?? null) === 'application/json'
                    && ($headers['X-Alt-Referer'] ?? null) === 'magento2.pixlee.com'
                    && ($headers['Signature'] ?? null) === $expectedSignature
                    && ($headers['Signature-Algorithm'] ?? null) === Distillery::SIGNATURE_ALGORITHM;
            }));

        $this->curl->expects($this->once())->method('addHeader')->with('Expect', '');
        $this->curl->expects($this->once())->method('post')->with(
            $this->stringContains('https://distillery.pixlee.co/api/v2/albums?api_key=' . self::PRIVATE_KEY),
            $payload
        );
        $this->curl->method('getStatus')->willReturn(200);
        $this->curl->method('getBody')->willReturn($responseBody);

        $subject = $this->createSubject();
        $subject->setScope(ScopeInterface::SCOPE_WEBSITES, 1);

        $this->assertSame($responseBody, $subject->post('v2/albums', $payload));
    }

    public function testPostReturnsFalseOnNonSuccessStatus(): void
    {
        $this->curl->method('getStatus')->willReturn(401);
        $this->curl->method('getBody')->willReturn('unauthorized');
        $this->logger->expects($this->once())->method('error')->with(
            'Invalid HTTP response from Pixlee API',
            [
                'status' => 401,
                'body' => 'unauthorized',
                'path' => 'v2/albums',
            ]
        );

        $subject = $this->createSubject();
        $subject->setScope(ScopeInterface::SCOPE_WEBSITES, 1);

        $this->assertFalse($subject->post('v2/albums', '{}'));
    }

    public function testGetAppendsOptionsToQueryString(): void
    {
        $responseBody = '{"albums":[]}';

        $this->curl->expects($this->once())->method('get')->with(
            $this->stringContains('v2/albums?api_key=' . self::PRIVATE_KEY . '&page=1&per_page=1')
        );
        $this->curl->method('getStatus')->willReturn(200);
        $this->curl->method('getBody')->willReturn($responseBody);

        $subject = $this->createSubject();
        $subject->setScope(ScopeInterface::SCOPE_WEBSITES, 1);

        $this->assertSame($responseBody, $subject->get('v2/albums', ['page' => '1', 'per_page' => '1']));
    }

    public function testCreateProductMapsExportPayloadToDistillerySchema(): void
    {
        $productInfo = [
            'name' => 'Simple Product',
            'sku' => 'simple',
            'product_url' => 'https://example.com/simple',
            'product_image' => 'https://example.com/media/simple.jpg',
            'product_id' => 10,
            'currency_code' => 'USD',
            'price' => 9.99,
            'regional_info' => '{}',
            'stock' => 5,
            'variants' => '[]',
            'extra_fields' => '{}',
        ];

        $this->curl->method('getStatus')->willReturn(200);
        $this->curl->method('getBody')->willReturn('{"album_id":99}');

        $this->curl->expects($this->once())
            ->method('post')
            ->with(
                $this->anything(),
                $this->callback(function (string $payload) use ($productInfo): bool {
                    $decoded = json_decode($payload, true);

                    return ($decoded['title'] ?? null) === $productInfo['name']
                        && ($decoded['album_type'] ?? null) === 'product'
                        && ($decoded['product']['sku'] ?? null) === $productInfo['sku']
                        && ($decoded['product']['buy_now_link_url'] ?? null) === $productInfo['product_url']
                        && ($decoded['product']['native_product_id'] ?? null) === $productInfo['product_id'];
                })
            );

        $subject = $this->createSubject();
        $subject->setScope(ScopeInterface::SCOPE_WEBSITES, 1);

        $result = $subject->createProduct($productInfo);

        $this->assertSame(['album_id' => 99], $result);
    }

    public function testNotifyExportStatusPostsExpectedPayload(): void
    {
        $this->curl->method('getStatus')->willReturn(200);
        $this->curl->method('getBody')->willReturn('{"ok":true}');

        $this->curl->expects($this->once())
            ->method('post')
            ->with(
                $this->stringContains('v1/notifyExportStatus'),
                $this->callback(function (string $payload): bool {
                    $decoded = json_decode($payload, true);

                    return ($decoded['api_key'] ?? null) === self::PRIVATE_KEY
                        && ($decoded['status'] ?? null) === 'complete'
                        && ($decoded['job_id'] ?? null) === 'job-123'
                        && ($decoded['num_products'] ?? null) === 42
                        && ($decoded['platform'] ?? null) === Pixlee::PLATFORM;
                })
            );

        $subject = $this->createSubject();
        $subject->setScope(ScopeInterface::SCOPE_WEBSITES, 1);
        $subject->notifyExportStatus('complete', 'job-123', 42);
    }

    public function testCreateProductReturnsFalseWhenPostFails(): void
    {
        $this->curl->method('getStatus')->willReturn(401);
        $this->curl->method('getBody')->willReturn('unauthorized');
        $this->logger->expects($this->once())->method('error')->with(
            'Invalid HTTP response from Pixlee API',
            [
                'status' => 401,
                'body' => 'unauthorized',
                'path' => 'v2/albums',
            ]
        );

        $subject = $this->createSubject();
        $subject->setScope(ScopeInterface::SCOPE_WEBSITES, 1);

        $this->assertFalse($subject->createProduct([
            'name' => 'Simple Product',
            'sku' => 'simple',
            'product_url' => 'https://example.com/simple',
            'product_image' => '',
            'product_id' => 1,
            'currency_code' => 'USD',
            'price' => 9.99,
            'regional_info' => '{}',
            'stock' => 1,
            'variants' => '[]',
            'extra_fields' => '{}',
        ]));
    }

    public function testValidateCredentialsDelegatesToGetAlbums(): void
    {
        $responseBody = '{"albums":[]}';

        $this->curl->expects($this->once())->method('get')->with(
            $this->stringContains('v2/albums')
        );
        $this->curl->method('getStatus')->willReturn(200);
        $this->curl->method('getBody')->willReturn($responseBody);

        $subject = $this->createSubject();
        $subject->setScope(ScopeInterface::SCOPE_WEBSITES, 1);

        $this->assertSame($responseBody, $subject->validateCredentials());
    }

    private function createSubject(): Distillery
    {
        return new Distillery(
            $this->curl,
            $this->serializer,
            $this->apiConfig,
            $this->logger
        );
    }

    /**
     * @param array<int, mixed> $args
     * @return mixed
     * @throws ReflectionException
     */
    private function invokeProtected(string $method, array $args)
    {
        $subject = $this->createSubject();
        $subject->setScope(ScopeInterface::SCOPE_WEBSITES, 1);
        $ref = new ReflectionMethod(Distillery::class, $method);

        return $ref->invokeArgs($subject, $args);
    }
}
