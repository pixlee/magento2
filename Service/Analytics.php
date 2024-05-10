<?php
/**
 * Copyright © Pixlee TurnTo, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Service;

use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\ScopeInterface;
use Pixlee\Pixlee\Api\AnalyticsServiceInterface;
use Pixlee\Pixlee\Model\Config\Api;
use Pixlee\Pixlee\Model\CookieManager;
use Pixlee\Pixlee\Model\Logger\PixleeLogger;
use Pixlee\Pixlee\Model\Pixlee;

class Analytics implements AnalyticsServiceInterface
{
    public const ANALYTICS_BASE_URL = 'https://inbound-analytics.pixlee.com/';
    /**
     * @var Curl
     */
    protected $curl;
    /**
     * @var PixleeLogger
     */
    protected $logger;
    /**
     * @var SerializerInterface
     */
    protected $serializer;
    /**
     * @var Api
     */
    protected $apiConfig;
    /**
     * @var CookieManager
     */
    protected $cookieManager;
    /**
     * @var Pixlee
     */
    protected $pixlee;
    /**
     * @var ProductMetadataInterface
     */
    protected $productMetadata;

    /**
     * @param Api $apiConfig
     * @param CookieManager $cookieManager
     * @param Curl $curl
     * @param PixleeLogger $logger
     * @param Pixlee $pixlee
     * @param ProductMetadataInterface $productMetadata
     * @param SerializerInterface $serializer
     */
    public function __construct(
        Api $apiConfig,
        CookieManager $cookieManager,
        Curl $curl,
        PixleeLogger $logger,
        Pixlee $pixlee,
        ProductMetadataInterface $productMetadata,
        SerializerInterface $serializer
    ) {
        $this->apiConfig = $apiConfig;
        $this->cookieManager = $cookieManager;
        $this->curl = $curl;
        $this->logger = $logger;
        $this->pixlee = $pixlee;
        $this->productMetadata = $productMetadata;
        $this->serializer = $serializer;
    }

    /**
     * @inheritdoc
     */
    public function sendEvent($event, $payload, $store)
    {
        $urls = [
            'addToCart' => 'addToCart',
            'checkoutSuccess' => 'conversion',
        ];
        if ($payload && isset($urls[$event])) {
            $payload = $this->preparePayload($store, $payload);
            $path = "events/{$urls[$event]}";
            $response = $this->post($path, $payload);

            if ($response) {
                $this->logger->addInfo("Pixlee Analytics: Event sent");
                return true;
            }
        }

        $this->logger->addInfo("Pixlee Analytics: Event not sent - " . $this->serializer->serialize($payload));
        return false;
    }

    /**
     * @param $store
     * @param $extraData
     * @return false|string
     */
    public function preparePayload($store, $extraData = [])
    {
        if ($payload = $this->getPixleeCookie()) {
            foreach ($extraData as $key => $value) {
                // Don't overwrite existing data.
                if (!isset($payload[$key])) {
                    $payload[$key] = $value;
                }
            }
            // Required key/value pairs not in the payload by default.
            $payload['API_KEY']= $this->apiConfig->getPrivateApiKey(ScopeInterface::SCOPE_STORES, $store->getCode());
            $payload['distinct_user_hash'] = $payload['CURRENT_PIXLEE_USER_ID'];
            $payload['ecommerce_platform'] = Pixlee::PLATFORM;
            $payload['ecommerce_platform_version'] = $this->productMetadata->getVersion();
            $payload['version_hash'] = $this->pixlee->getExtensionVersion();
            $payload['region_code'] = $store->getCode();
            $serializedPayload = $this->serializer->serialize($payload);

            $this->logger->addInfo("Sending payload: " . $serializedPayload);
            return $serializedPayload;
        }

        $this->logger->addInfo("Analytics event not sent because the cookie wasn't found");
        return false;
    }

    /**
     * @return array|bool
     */
    public function getPixleeCookie()
    {
        $cookie = $this->cookieManager->get();
        if (isset($cookie)) {
            return $this->serializer->unserialize($cookie);
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function post($path, $payload, $options = null)
    {
        $requestUrl = self::ANALYTICS_BASE_URL . $path;

        if (!empty($options)) {
            $requestUrl = $requestUrl . '?' . http_build_query($options);
        }

        $headers = [
            'Content-Type' => 'application/json'
        ];
        $this->curl->setHeaders($headers);
        $this->curl->post($requestUrl, $payload);
        $response = $this->curl->getBody();
        $responseCode = $this->curl->getStatus();
        if (!$this->isValidResponse($response, $responseCode)) {
            return false;
        }

        return $response;
    }

    /**
     * @param $response
     * @param $responseCode
     * @return bool
     */
    public function isValidResponse($response, $responseCode)
    {
        if (!$this->isBetween($responseCode, 200, 299)) {
            $this->logger->addInfo("Pixlee Analytics: HTTP $responseCode response");
            return false;
        }
        if (is_object($response) && $response->status === null) {
            $this->logger->addInfo("Pixlee Analytics: Invalid status returned");
            return false;
        }
        if (is_object($response) && !$this->isBetween($response->status, 200, 299)) {
            $errorMessage = implode(',', (array)$response->message);
            $this->logger->addInfo("Pixlee Analytics: $response->status - $errorMessage ");
            return false;
        }

        return true;
    }

    /**
     * @param $theNum
     * @param $low
     * @param $high
     * @return bool
     */
    public function isBetween($theNum, $low, $high)
    {
        if ($theNum >= $low && $theNum <= $high) {
            return true;
        }

        return false;
    }
}
