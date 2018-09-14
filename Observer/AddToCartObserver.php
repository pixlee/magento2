<?php

namespace Pixlee\Pixlee\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;

class AddToCartObserver implements ObserverInterface
{
	/**
     * @var ManagerInterface
     */
    protected $messageManager;

    public function __construct(
        \Pixlee\Pixlee\Helper\Data $pixleeData,
        ManagerInterface $messageManager,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->_pixleeData  = $pixleeData;
        $this->messageManager = $messageManager;
        $this->_logger      = $logger;
    }

    public function execute(EventObserver $observer)
    {
        $this->_logger->addInfo("[Pixlee] :: AddToCart: pushing product data to analytics endpoint");
        $event = $observer->getEvent();
        $product = $event->getData('product');
        $websiteId = $observer->getEvent()->getData('website');
        $this->_pixleeData->initializePixleeAPI($websiteId);
        $this->_pixleeData->initializeAddToCartData($product);
    }
}
