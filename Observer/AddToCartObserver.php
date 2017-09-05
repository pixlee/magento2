<?php

namespace Pixlee\Pixlee\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class AddToCartObserver implements ObserverInterface
{
    // A simple Trait to reuse Sentry Handler instantiation
    use \Pixlee\Pixlee\Helper\Ravenized;

    public function __construct(
        \Pixlee\Pixlee\Helper\Data $pixleeData,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->_pixleeData  = $pixleeData;
        $this->_logger      = $logger;
        $this->_scopeConfig = $scopeConfig;
        // Use the Ravenized trait to instantiate a Sentry Handler
        $this->ravenize();
    }

    public function execute(EventObserver $observer)
    {   
        $pixleeEnabled = $this->_scopeConfig->getValue(
            'pixlee_pixlee/existing_customers/account_settings/active',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        if ($pixleeEnabled) {
            $product = $observer->getEvent()->getProduct();
            /*
            $productData = $this->_pixleeData->_extractProduct($product);
            */

            $productData = $this->_pixleeData->_extractProduct($product);
            $payload = $this->_pixleeData->_preparePayload($productData);
            $this->_pixleeData->_sendPayload('addToCart', $payload);

            $this->_logger->addInfo("[Pixlee] :: addToCart ".json_encode($payload));
        }

    }
}
