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
        $this->_logger->addInfo('CREATE PRODUCT EXECUTE');
        $product = $observer->getEvent()->getProduct();
        $this->_logger->addInfo(json_encode($product));
        $productData = array('product' => $this->_pixleeData->_extractProduct($product));
        $this->_logger->addInfo(json_encode($productData));
        $payload = $this->_pixleeData->_preparePayload($productData);
        $this->_logger->addInfo(json_encode($payload));
        //$this->_pixleeData->_sendPayload('addToCart', $payload);

        $this->_pixleeData->exportProductToPixlee($product);
        //$this->_logger->addInfo("[Pixlee] :: addToCart ".json_encode($payload));
        /*
        try{
            $this->_logger->addInfo('Trying to export Product');
            $pixleeData->exportProductToPixlee($product);
        } catch (Exception $e) {
            //Mage::getSingleton("adminhtml/session")->addWarning("Pixlee Magento - You may not have the right API credentials. Please check the plugin configuration.");
            //Mage::log("PIXLEE ERROR: " . $e->getMessage());
            $this->_logger->addInfo('FUCK');
            $this->_logger->addInfo($e->getMessage());
        }
        */
    }
}
