<?php

namespace Pixlee\Pixlee\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class AddToCartObserver implements ObserverInterface
{
    public function __construct(
        \Pixlee\Pixlee\Helper\Data $pixleeData,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->_pixleeData  = $pixleeData;
        $this->_logger      = $logger;
    }

    public function execute(EventObserver $observer)
    {
        $product = $observer->getEvent()->getProduct();
        $productData = $this->_pixleeData->_extractProduct($product);
        $payload = $this->_pixleeData->_preparePayload($productData);
        $this->_pixleeData->_sendPayload('addToCart', $payload);

        $this->_logger->addInfo("[Pixlee] :: addToCart ".json_encode($payload));
    }
}
