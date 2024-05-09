<?php
/**
 * Copyright © Pixlee TurnTo, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Pixlee\Pixlee\Observer;

use Exception;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Pixlee\Pixlee\Model\Logger\PixleeLogger;
use Pixlee\Pixlee\Model\Cart;
use Pixlee\Pixlee\Model\Config\Api;
use Pixlee\Pixlee\Api\AnalyticsServiceInterface;

class CheckoutSuccessObserver implements ObserverInterface
{
    /**
     * @var Collection
     */
    protected $orderCollection;
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
     */
    public function execute(EventObserver $observer)
    {
        try {
            if ($this->apiConfig->isActive(ScopeInterface::SCOPE_STORES, $this->storeManager->getStore()->getCode())) {
                $orders = $this->getOrders($observer->getEvent());
                /** @var OrderInterface $order */
                foreach ($orders as $order) {
                    $conversionPayload = $this->pixleeCart->getConversionPayload($order);
                    $this->analytics->sendEvent('checkoutSuccess', $conversionPayload);
                }
            }
        } catch (Exception $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);
        }
    }

    /**
     * @param Event $event
     * @return array|Collection
     */
    protected function getOrders($event)
    {
        if ($order = $event->getOrder()) {
            return [$order];
        }

        return $this->orderCollection->addFieldToFilter('entity_id', ['in' => $event->getOrderIds()]);
    }
}
