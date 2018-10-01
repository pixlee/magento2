<?php

namespace Pixlee\Pixlee\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;

class AddProductToCartObserver implements ObserverInterface
{
	protected $_storeManager; 
    public function __construct(
	\Pixlee\Pixlee\Helper\PostProductInfoToAPI $pixleeData,
	\Magento\Store\Model\StoreManagerInterface $storeManager,  
	ManagerInterface $messageManager,
	\Psr\Log\LoggerInterface $logger)
	{
		$this->_pixleeData  = $pixleeData;
		$this->messageManager = $messageManager;
        $this->_logger      = $logger;
		$this->_storeManager = $storeManager; 
    }

    public function execute(EventObserver $observer)
    {   
		$this->_logger->addInfo("[Pixlee] :: product added to cart");
		$websiteId = $this->_storeManager->getStore()->getWebsiteId(); #fetching the website Id which in turn used for fetching secret key		
        $product = $observer->getEvent()->getData('product'); #getting the product info from the dispatched event
		$productId = $product->getId();
		$productPrice = $product->getPrice();
		$productQuantity = $product->getQty();
		$this->_pixleeData->PostCartAddedProductInfo($productId, $productQuantity, $productPrice, $websiteId);
		$this->_logger->addInfo("Product id is ". $product->getId(). ", the price is ".$product->getPrice()." and the quantity is ". $product->getQty()." "); #adding product info to log
    }
}
