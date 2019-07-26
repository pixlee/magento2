<?php

namespace Pixlee\Pixlee\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class AddToCartObserver implements ObserverInterface
{
    // A simple Trait to reuse Sentry Handler instantiation
    public function __construct(
        \Pixlee\Pixlee\Helper\Data $pixleeData,
        \Pixlee\Pixlee\Helper\Logger\PixleeLogger $logger,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
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
            $product = $observer->getEvent()->getProduct();
            $productData = $this->_pixleeData->_extractProduct($product);
            $storeId = $this->_storeManager->getStore()->getStoreId();
            $payload = $this->_pixleeData->_preparePayload($storeId, $productData);
            $this->_pixleeData->_sendPayload('addToCart', $payload);
            $this->_logger->addInfo("AddToCart ".json_encode($payload));
        }
    }
}
