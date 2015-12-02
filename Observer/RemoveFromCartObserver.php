<?php

namespace Pixlee\Pixlee\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class RemoveFromCartObserver implements ObserverInterface
{
    public function __construct(
        \Pixlee\Pixlee\Helper\data $pixleeData,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->_pixleeData  = $pixleeData;
        $this->_logger      = $logger;
    }

    public function execute(EventObserver $observer)
    {
        $product = $observer->getEvent()->getQuoteItem();
        $productData = array('product' => $this->_pixleeData->_extractProduct($product));
        $payload = $this->_pixleeData->_preparePayload($productData);
        $this->_pixleeData->_sendPayload('removeFromCart', $payload);

        $this->_logger->addInfo("[Pixlee] :: removeFromCart ".json_encode($payload));
    }
}