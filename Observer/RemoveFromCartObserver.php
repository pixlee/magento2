<?php

namespace Pixlee\Pixlee\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class RemoveFromCartObserver implements ObserverInterface
{
    // A simple Trait to reuse Sentry Handler instantiation
    use \Pixlee\Pixlee\Helper\Ravenized;

    public function __construct(
        \Pixlee\Pixlee\Helper\Data $pixleeData,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->_pixleeData  = $pixleeData;
        $this->_logger      = $logger;
        // Use the Ravenized trait to instantiate a Sentry Handler
        $this->ravenize();
    }

    public function execute(EventObserver $observer)
    {
        $product = $observer->getEvent()->getQuoteItem()->getProduct();
        $productData = $this->_pixleeData->_extractProduct($product);
        $payload = $this->_pixleeData->_preparePayload($productData);
        $this->_pixleeData->_sendPayload('removeFromCart', $payload);

        $this->_logger->addInfo("[Pixlee] :: removeFromCart ".json_encode($payload));
    }
}
