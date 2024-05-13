<?php
/**
 * Copyright © Pixlee TurnTo, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Pixlee\Pixlee\Observer;

use Exception;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
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
     */
    public function execute(EventObserver $observer)
    {
        try {
            if ($this->apiConfig->isActive(ScopeInterface::SCOPE_STORES, $this->storeManager->getStore()->getCode())) {
                /** @var ProductInterface $product */
                $product = $observer->getEvent()->getData('product');
                $productData = $this->pixleeCart->extractProduct($product);
                $this->analytics->sendEvent('addToCart', $productData, $product->getStore());
            }
        } catch (Exception $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);
        }
    }
}
