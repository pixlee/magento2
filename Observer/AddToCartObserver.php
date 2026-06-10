<?php
/**
 * Copyright © Emplifi, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Observer;

use Exception;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote\Item as QuoteItem;
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
     * @inheritdoc
     */
    public function execute(EventObserver $observer)
    {
        try {
            if (!$this->apiConfig->isActive(ScopeInterface::SCOPE_STORES, $this->storeManager->getStore()->getCode())) {
                return;
            }

            /** @var QuoteItem|null $quoteItem */
            $quoteItem = $observer->getEvent()->getData('quote_item');
            if (!$quoteItem instanceof QuoteItem) {
                return;
            }

            $store = $quoteItem->getQuote()->getStore();
            $itemData = $this->pixleeCart->extractQuoteItem($quoteItem, $store->getCurrentCurrency());
            $this->analytics->sendEvent('addToCart', $itemData, $store);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);
        }
    }
}
