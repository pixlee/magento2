<?php

namespace Pixlee\Pixlee\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class CheckoutSuccessObserver implements ObserverInterface
{
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Collection
     */
    protected $_collection;

    public function __construct(
        \Magento\Sales\Model\ResourceModel\Order\Collection $collection,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Pixlee\Pixlee\Helper\Data $pixleeData,
        \Pixlee\Pixlee\Helper\Logger\PixleeLogger $logger,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->_collection = $collection;
        $this->_checkoutSession = $checkoutSession;
        $this->_pixleeData  = $pixleeData;
        $this->_logger      = $logger;
        $this->_scopeConfig = $scopeConfig;
        $this->_storeManager = $storeManager;
    }

    public function execute(EventObserver $observer)
    {
        $websiteId = $this->_storeManager->getWebsite()->getWebsiteId();
        $this->_pixleeData->initializePixleeAPI($websiteId);
        $pixleeEnabled = $this->_pixleeData->isActive();

        if ($pixleeEnabled) {
            $this->_logger->addInfo("Start of Conversion");

            $orderIds = $observer->getEvent()->getOrderIds();
            if (!$orderIds || !is_array($orderIds)) {
                return $this;
            }

            $storeId = $this->_storeManager->getStore()->getStoreId();
            $this->_collection->addFieldToFilter('entity_id', ['in' => $orderIds]);
            foreach ($this->_collection as $order) {
                $cartData = $this->_pixleeData->_extractCart($order);
                $payload = $this->_pixleeData->_preparePayload($cartData, $storeId);
                $this->_pixleeData->_sendPayload('checkoutSuccess', $payload);
            }
            $this->_logger->addInfo("CheckoutSuccess ".json_encode($payload));
        }
    }
}
