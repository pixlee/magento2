<?php
/**
 * Copyright © Pixlee TurnTo, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Service;

use Magento\Framework\HTTP\Client\Curl;
use Pixlee\Pixlee\Api\AnalyticsServiceInterface;
use Pixlee\Pixlee\Model\Logger\PixleeLogger;

class Analytics implements AnalyticsServiceInterface
{
    public const ANALYTICS_BASE_URL = 'https://inbound-analytics.pixlee.com/';
    /**
     * @var Curl
     */
    protected Curl $curl;
    /**
     * @var PixleeLogger
     */
    protected PixleeLogger $logger;

    /**
     * @param Curl $curl
     * @param PixleeLogger $logger
     */
    public function __construct(
        Curl $curl,
        PixleeLogger $logger
    ) {
        $this->curl = $curl;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function sendEvent($event, $payload)
    {
        $urls = [
            'addToCart' => 'addToCart',
            'checkoutStart' => 'checkoutStart',
            'checkoutSuccess' => 'conversion',
            'removeFromCart' => 'removeFromCart'
        ];
        if ($payload && isset($urls[$event])) {
            $path = "events/{$urls[$event]}";
            $response = $this->post($path, $payload);

            if ($response) {
                $this->logger->addInfo("Pixlee Analytics: Event sent");
                return true;
            }
        }

        $this->logger->addInfo("Pixlee Analytics: Event not sent - ".json_encode($payload));
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
    protected function isValidResponse($response, $responseCode)
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
    protected function isBetween($theNum, $low, $high)
    {
        if ($theNum >= $low && $theNum <= $high) {
            return true;
        }

        return false;
    }
}
