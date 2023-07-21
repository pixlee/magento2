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
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Store\Model\ScopeInterface;
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
     * @var SerializerInterface
     */
    protected $serializer;
    /**
     * @var AnalyticsServiceInterface
     */
    protected $analytics;

    /**
     * @param Collection $orderCollection
     * @param PixleeLogger $logger
     * @param StoreManagerInterface $storeManager
     * @param SerializerInterface $serializer
     * @param Cart $pixleeCart
     * @param Api $apiConfig
     * @param AnalyticsServiceInterface $analytics
     */
    public function __construct(
        Collection $orderCollection,
        PixleeLogger $logger,
        StoreManagerInterface $storeManager,
        SerializerInterface $serializer,
        Cart $pixleeCart,
        Api $apiConfig,
        AnalyticsServiceInterface $analytics
    ) {
        $this->orderCollection = $orderCollection;
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
                $orderIds = $observer->getEvent()->getOrderIds();
                if (!$orderIds || !is_array($orderIds)) {
                    return;
                }


                $this->orderCollection->addFieldToFilter('entity_id', ['in' => $orderIds]);
                foreach ($this->orderCollection as $order) {
                    $cartData = $this->pixleeCart->extractCart($order);
                    $payload = $this->pixleeCart->preparePayload($store, $cartData);
                    $this->analytics->sendEvent('checkoutSuccess', $payload);
                    $this->logger->addInfo('CheckoutSuccess ' . $this->serializer->serialize($payload));
                }
            }
        } catch (Exception $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);
        }
    }
}
