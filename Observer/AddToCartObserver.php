<?php
/**
 * Copyright © Pixlee TurnTo, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Pixlee\Pixlee\Observer;

use Exception;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Pixlee\Pixlee\Model\Logger\PixleeLogger;
use Pixlee\Pixlee\Model\Cart;
use Pixlee\Pixlee\Model\Config\Api;
use Pixlee\Pixlee\Api\AnalyticsServiceInterface;

class AddToCartObserver implements ObserverInterface
{
    /**
     * @var Cart
     */
    protected $pixleeCart;
    /**
     * @var Api
     */
    protected $apiConfig;
    /**
     * @var PixleeLogger
     */
    protected $logger;
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var AnalyticsServiceInterface
     */
    protected $analytics;
    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @param PixleeLogger $logger
     * @param StoreManagerInterface $storeManager
     * @param SerializerInterface $serializer
     * @param Cart $pixleeCart
     * @param Api $apiConfig
     * @param AnalyticsServiceInterface $analytics
     */
    public function __construct(
        PixleeLogger $logger,
        StoreManagerInterface $storeManager,
        SerializerInterface $serializer,
        Cart $pixleeCart,
        Api $apiConfig,
        AnalyticsServiceInterface $analytics
    ) {
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->serializer = $serializer;
        $this->pixleeCart = $pixleeCart;
        $this->apiConfig = $apiConfig;
        $this->analytics = $analytics;
    }

    /**
     * @param EventObserver $observer
     * @return void
     */
    public function execute(EventObserver $observer)
    {
        try {
            $websiteId = $this->storeManager->getWebsite()->getWebsiteId();

            if ($this->apiConfig->isActive($websiteId)) {
                $product = $observer->getEvent()->getProduct();
                $productData = $this->pixleeCart->extractProduct($product);
                $storeId = $this->storeManager->getStore()->getStoreId();
                $payload = $this->pixleeCart->preparePayload($storeId, $productData);
                $this->analytics->sendEvent('addToCart', $payload);
                $this->logger->addInfo('AddToCart ' . $this->serializer->serialize($payload));
            }
        } catch (Exception $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);
        }
    }
}
