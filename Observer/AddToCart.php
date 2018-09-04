<?php

namespace Pixlee\Pixlee\Observer;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;

class AddToCart implements ObserverInterface
{
    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    public function __construct(
        CheckoutSession $checkoutSession,
        \Pixlee\Pixlee\Helper\Data $pixleeData,
        ManagerInterface $messageManager,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->_pixleeData     = $pixleeData;
        $this->messageManager  = $messageManager;
        $this->_logger         = $logger;
        $this->checkoutSession = $checkoutSession;
    }

    public function execute(EventObserver $observer)
    {   
        $product = $observer->getEvent()->getProduct();
        $websiteId = $observer->getEvent()->getData('website');
        $id = $product->getId();
        $price = number_format($product->getPrice(),2);
        $request = $observer->getEvent()->getRequest()->getParams();
        $quote = $this->checkoutSession->getQuote()->getQuoteCurrencyCode();

        $this->_logger->debug("[Pixlee] :: start of AddtoCart Observer");
        $this->_logger->debug("{$id}");
        $this->_logger->debug("{$price}");
        $this->_logger->debug("{$request['qty']}");
        $this->_logger->debug("{$quote}");      
        
        
        $this->_pixleeData->initializePixleeAPI($websiteId)->addToCartData($id,$price,$request['qty'],$quote);
    }
}