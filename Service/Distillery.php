<?php
/**
 * Copyright © Pixlee TurnTo, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Service;

use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\SerializerInterface;
use Pixlee\Pixlee\Api\PixleeServiceInterface;
use Pixlee\Pixlee\Model\Config\Api;
use Pixlee\Pixlee\Model\Logger\PixleeLogger;

class Distillery implements PixleeServiceInterface
{
    public const DISTILLERY_BASE_URL = 'https://distillery.pixlee.com/api/v1/';
    /**
     * @var Api
     */
    protected $apiConfig;
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
    protected $websiteId;

    /**
     * @param Curl $curl
     * @param SerializerInterface $serializer
     * @param Api $apiConfig
     * @param PixleeLogger $logger
     */
    public function __construct(
        Curl $curl,
        SerializerInterface $serializer,
        Api $apiConfig,
        PixleeLogger $logger
    ) {
        $this->apiConfig = $apiConfig;
        $this->serializer = $serializer;
        $this->curl = $curl;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function setWebsiteId($websiteId)
    {
        $this->websiteId = $websiteId;
    }

    /**
     * @inheritdoc
     */
    public function validateCredentials($websiteId)
    {
        $this->setWebsiteId($websiteId);

        return $this->getAlbums(['page' => '1', 'per_page' => '1']);
    }

    /**
     * @inheritdoc
     */
    public function getAlbums($options = null)
    {
        return $this->get('albums', $options);
    }

    /**
     * @inheritdoc
     */
    public function notifyExportStatus($status, $jobId, $numProducts, $websiteId)
    {
        $path = 'notifyExportStatus';
        $payload = [
            'api_key' => $this->apiConfig->getApiKey($websiteId),
            'status' => $status,
            'job_id' => $jobId,
            'num_products' => $numProducts,
            'platform' => 'magento_2'
        ];

        $this->post($path, $payload);
    }

    /**
     * @inheritdoc
     */
    public function createProduct(
        $product_name,
        $sku,
        $product_url,
        $product_image,
        $currencyCode,
        $price,
        $regionalInfo,
        $product_id = null,
        $aggregateStock = null,
        $variantsDict = null,
        $extraFields = null
    ) {
        $this->logger->addInfo("* In createProduct");
        $product = [
            'name' => $product_name,
            'sku' => $sku,
            'buy_now_link_url' => $product_url,
            'product_photo' => $product_image,
            'stock' => $aggregateStock,
            'native_product_id' => $product_id,
            'variants_json' => $variantsDict,
            'extra_fields' => $extraFields,
            'currency' => $currencyCode,
            'price' => $price,
            'regional_info' => $regionalInfo
        ];

        $payload = [
            'title' => $product_name,
            'album_type' => 'product',
            'live_update' => false,
            'num_photo' => 0,
            'num_inbox_photo' => 0,
            'product' => $product
        ];

        $response = $this->post('albums', $payload);

        return $this->serializer->serialize($response);
    }

    /**
     * @inheritdoc
     */
    public function get($path, $options = null)
    {
        $queryString = $this->getRequiredQueryString();

        if (!empty($options)) {
            $queryString = $queryString . '&' . http_build_query($options);
        }
        $requestUrl = self::DISTILLERY_BASE_URL . $path . $queryString;

        $headers = [
            'Content-Type' => 'application/json',
            'X-Alt-Referer' => 'magento2.pixlee.com'
        ];
        $this->curl->setHeaders($headers);
        $this->curl->get($requestUrl, []);
        if (!$this->isValidResponse($path)) {
            return false;
        }

        return $this->curl->getBody();
    }

    /**
     * @inheritdoc
     */
    public function post($path, $payload, $options = null)
    {
        $queryString = $this->getRequiredQueryString();

        if (!empty($options)) {
            $queryString = $queryString . "&" . http_build_query($options);
        }
        $requestUrl = self::DISTILLERY_BASE_URL . $path . $queryString;

        $headers = [
            "Content-Type" => "application/json",
            "X-Alt-Referer" => "magento2.pixlee.com",
            'Signature' => $this->generateSignature($payload),
        ];
        $this->curl->setHeaders($headers);
        $this->curl->post($requestUrl, $payload);
        if (!$this->isValidResponse($path)) {
            return false;
        }

        return $this->curl->getBody();
    }

    /**
     * @return string
     */
    protected function getRequiredQueryString()
    {
        return '?api_key=' . $this->apiConfig->getApiKey($this->websiteId);
    }

    /**
     * @param $path
     * @return bool
     */
    protected function isValidResponse($path)
    {
        $responseCode = $this->curl->getStatus();
        if (!$this->isBetween($responseCode, 200, 299)) {
            $this->logger->warning(
                "[Pixlee] :: HTTP $responseCode response from API path $path"
            );
            return false;
        }

        return true;
    }

    /**
     * @param string $data
     * @return string
     */
    protected function generateSignature($data)
    {
        return base64_encode(hash_hmac('sha1', $data, $this->apiConfig->getSecretKey($this->websiteId), true));
    }

    /**
     * @param $theNum
     * @param $low
     * @param $high
     * @return bool
     */
    protected function isBetween($theNum, $low, $high)
    {
        if ($theNum >= $low && $theNum <= $high) {
            return true;
        }

        return false;
    }
}
