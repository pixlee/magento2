<?php
/**
 * Copyright © Pixlee TurnTo, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Service;

use Exception;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\SerializerInterface;
use Pixlee\Pixlee\Api\PixleeServiceInterface;
use Pixlee\Pixlee\Model\Config\Api;
use Pixlee\Pixlee\Model\Logger\PixleeLogger;
use Pixlee\Pixlee\Model\Pixlee;

class Distillery implements PixleeServiceInterface
{
    public const DISTILLERY_BASE_URL = 'https://distillery.pixlee.co/api/';
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
    /**
     * @var int|null|string
     */
    protected $scopeCode;
    /**
     * @var string
     */
    protected $scopeType;

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
    public function setScope($scopeType, $scopeCode)
    {
        $this->scopeType = $scopeType;
        $this->scopeCode = $scopeCode;
    }

    /**
     * @inheritdoc
     */
    public function validateCredentials()
    {
        return $this->getAlbums(['page' => '1', 'per_page' => '1']);
    }

    /**
     * @inheritdoc
     */
    public function getAlbums($options = null)
    {
        return $this->get('v2/albums', $options);
    }

    /**
     * @inheritdoc
     */
    public function notifyExportStatus($status, $jobId, $numProducts)
    {
        try {
            $path = 'v1/notifyExportStatus';
            $payload = [
                'api_key' => $this->apiConfig->getPrivateApiKey($this->scopeType, $this->scopeCode),
                'status' => $status,
                'job_id' => $jobId,
                'num_products' => $numProducts,
                'platform' => Pixlee::PLATFORM
            ];

            $this->post($path, json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        } catch (Exception $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);
        }
    }

    /**
     * @inheritdoc
     */
    public function createProduct(
        array $productInfo
    ) {
        $this->logger->addInfo("* In createProduct");
        $product = [
            'name' => $productInfo['name'],
            'sku' => $productInfo['sku'],
            'buy_now_link_url' => $productInfo['product_url'],
            'product_photo' => $productInfo['product_image'],
            'native_product_id' => $productInfo['product_id'],
            'currency' => $productInfo['currency_code'],
            'price' => $productInfo['price'],
            'regional_info' => $productInfo['regional_info'],
            'stock' => $productInfo['stock'],
            'variants_json' => $productInfo['variants'],
            'extra_fields' => $productInfo['extra_fields']
        ];


        $payload = [
            'title' => $productInfo['name'],
            'album_type' => 'product',
            'live_update' => false,
            'num_photos' => 0,
            'num_inbox_photos' => 0,
            'product' => $product
        ];

        $response = $this->post('v2/albums', json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return $this->serializer->unserialize($response);
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
        $this->curl->get($requestUrl);
        if (!$this->isValidResponse($path)) {
            return false;
        }

        return $this->curl->getBody();
    }

    /**
     * @inheritdoc
     */
    public function post(string $path, string $payload, $options = null)
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
        // Needed for 100-continue response
        $this->curl->addHeader('Expect', '');
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
        return '?api_key=' . $this->apiConfig->getPrivateApiKey($this->scopeType, $this->scopeCode);
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
        return base64_encode(hash_hmac('sha1', $data, $this->apiConfig->getSecretKey($this->scopeType, $this->scopeCode), true));
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
