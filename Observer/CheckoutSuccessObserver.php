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
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Store\Model\StoreManagerInterface;
use Pixlee\Pixlee\Model\Logger\PixleeLogger;
use Pixlee\Pixlee\Model\Cart;
use Pixlee\Pixlee\Model\Config\Api;
use Pixlee\Pixlee\Api\AnalyticsServiceInterface;;

class CheckoutSuccessObserver implements ObserverInterface
{
    /**
     * @var Collection
     */
    protected Collection $orderCollection;
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
     * @param Collection $orderCollection
     * @param PixleeLogger $logger
     * @param StoreManagerInterface $storeManager
     * @param Cart $pixleeCart
     * @param Api $apiConfig
     * @param AnalyticsServiceInterface $analytics
     */
    public function __construct(
        Collection $orderCollection,
        PixleeLogger $logger,
        StoreManagerInterface $storeManager,
        Cart $pixleeCart,
        Api $apiConfig,
        AnalyticsServiceInterface $analytics
    ) {
        $this->orderCollection = $orderCollection;
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
            $orderIds = $observer->getEvent()->getOrderIds();
            if (!$orderIds || !is_array($orderIds)) {
                return;
            }

            $storeId = $this->storeManager->getStore()->getStoreId();
            $this->orderCollection->addFieldToFilter('entity_id', ['in' => $orderIds]);
            foreach ($this->orderCollection as $order) {
                $cartData = $this->pixleeCart->extractCart($order);
                $payload = $this->pixleeCart->preparePayload($storeId, $cartData);
                $this->analytics->sendEvent('checkoutSuccess', $payload);
                $this->logger->addInfo('CheckoutSuccess ' . json_encode($payload));
            }
        }
    }
}
