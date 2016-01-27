<?php

namespace Pixlee\Pixlee\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class CheckoutStartObserver implements ObserverInterface
{
    public function __construct(
        \Pixlee\Pixlee\Helper\Data $pixleeData,
        \Psr\Log\LoggerInterface $logger
    ) {
        // $this->_checkoutCart = $checkoutCart;
        $this->_pixleeData  = $pixleeData;
        $this->_logger      = $logger;
    }

    public function execute(EventObserver $observer)
    {
        $this->_logger->addInfo("[Pixlee] :: start of checkoutStart");

        $quote = $this->_checkoutCart->getQuote();
        $this->_logger->addInfo($quote);
        $cartData = $this->_pixleeData->_extractCart($quote);
        $payload = array('cart' => $cartData);
        $payload = $this->_pixleeData->_preparePayload($payload);
        $this->_pixleeData->_sendPayload('checkoutStart', $payload);

        $this->_logger->addInfo("[Pixlee] :: checkoutStart ".json_encode($payload));
    }
}