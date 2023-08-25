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
use Magento\Store\Model\ScopeInterface;
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
            $store = $this->storeManager->getStore();

            if ($this->apiConfig->isActive(ScopeInterface::SCOPE_STORES, $store->getId())) {
                $product = $observer->getEvent()->getData('product');
                $productData = $this->pixleeCart->extractProduct($product);
                $payload = $this->pixleeCart->preparePayload($store, $productData);
                $this->analytics->sendEvent('addToCart', $payload);
                $this->logger->addInfo('AddToCart ' . $this->serializer->serialize($payload));
            }
        } catch (Exception $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);
        }
    }
}
