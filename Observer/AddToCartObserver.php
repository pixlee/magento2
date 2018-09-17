<?php

namespace Pixlee\Pixlee\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;

use Pixlee\Pixlee\Helper\Pixlee as PixleeAPI;

/*
 * Observe the checkout_cart_product_add_after event and log data
 * to the analytics end-point about the product being added to the cart.
 */
class AddToCartObserver implements ObserverInterface
{
    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    protected $_pixleeData;
    protected $_logger;

    public function __construct(
        \Pixlee\Pixlee\Helper\Data $pixleeData,
        ManagerInterface $messageManager,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->_pixleeData = $pixleeData;
        $this->messageManager = $messageManager;
        $this->_logger = $logger;
    }

    public function execute(EventObserver $observer)
    {   
        $this->_logger->addInfo("[Pixlee] :: add a product to the cart");

        # Get data from the event.
        $product = $observer->getEvent()->getData("product");
        $quote_item = $observer->getEvent()->getData("quote_item");

        $payload = [
            "product_id" => $product->getId(),
            "price" => $product->getPrice(),
            # The quantity returned by Item.getQty() is the total quantity,
            # not the quantity just added.
            "quantity" => $quote_item->getQty(),
            "currency" => $this->_pixleeData
                ->_storeManager
                ->getStore()
                ->getCurrentCurrency()
                ->getCode()
        ];

        # Log the event to the analytics end-point.
        $websiteId = $observer->getEvent()->getData('website');
        $this->_pixleeData->initializePixleeAPI($websiteId);
        $this->_pixleeData->_pixleeAPI->postAnalytics($payload);
    }
}
