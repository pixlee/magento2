<?php

namespace Pixlee\Pixlee\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class CreateProductTriggerObserver implements ObserverInterface
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
        $websiteIds = $observer->getEvent()->getProduct()->getWebsiteIds();
        foreach ($websiteIds as $websiteId) {
            $this->_pixleeData->initializePixleeAPI($websiteId);
            $pixleeEnabled = $this->_pixleeData->isActive();

            if ($pixleeEnabled && $product->getStatus() == 1) {
                $categoriesMap = $this->_pixleeData->getCategoriesMap();
                $this->_pixleeData->exportProductToPixlee($product, $categoriesMap, $websiteId);
            }
        }
    }
}
