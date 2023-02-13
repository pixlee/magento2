<?php
/**
 * Copyright © Pixlee TurnTo, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Pixlee\Pixlee\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
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
    protected Cart $pixleeCart;
    /**
     * @var Api
     */
    protected Api $apiConfig;
    /**
     * @var PixleeLogger
     */
    protected PixleeLogger $logger;
    /**
     * @var StoreManagerInterface
     */
    protected StoreManagerInterface $storeManager;
    /**
     * @var AnalyticsServiceInterface
     */
    protected AnalyticsServiceInterface $analytics;

    /**
     * @param PixleeLogger $logger
     * @param StoreManagerInterface $storeManager
     * @param Cart $pixleeCart
     * @param Api $apiConfig
     * @param AnalyticsServiceInterface $analytics
     */
    public function __construct(
        PixleeLogger $logger,
        StoreManagerInterface $storeManager,
        Cart $pixleeCart,
        Api $apiConfig,
        AnalyticsServiceInterface $analytics
    ) {
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->pixleeCart = $pixleeCart;
        $this->apiConfig = $apiConfig;
        $this->analytics = $analytics;
    }

    /**
     * @param EventObserver $observer
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute(EventObserver $observer)
    {
        $websiteId = $this->storeManager->getWebsite()->getWebsiteId();

        if ($this->apiConfig->isActive($websiteId)) {
            $product = $observer->getEvent()->getProduct();
            $productData = $this->pixleeCart->extractProduct($product);
            $storeId = $this->storeManager->getStore()->getStoreId();
            $payload = $this->pixleeCart->preparePayload($storeId, $productData);
            $this->analytics->sendEvent('addToCart', $payload);
            $this->logger->addInfo('AddToCart ' . json_encode($payload));
        }
    }
}
