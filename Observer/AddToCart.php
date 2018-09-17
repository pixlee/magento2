<?php

namespace Pixlee\Pixlee\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\StoreManagerInterface;

class AddToCart implements ObserverInterface
{
    /**
     * @var StoreManagerInterface
     */
    private $storeConfig;

    public function __construct(
        \Pixlee\Pixlee\Helper\Data $pixleeData,
        StoreManagerInterface $storeConfig,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->_pixleeData  = $pixleeData;
        $this->storeConfig  = $storeConfig;
        $this->_logger      = $logger;
    }

    public function execute(EventObserver $observer)
    {
        $this->_logger->addInfo("[Pixlee] :: sending item data to API");
        $websiteId = $observer->getEvent()->getData('website');
        $this->_pixleeData->initializePixleeAPI($websiteId);

        $item = $observer->getEvent()->getData('quote_item');
        $item = ($item->getParentItem() ? $item->getParentItem() : $item);

        $productId = $item->getProduct()->getId();
        $price = $item->getOriginalPrice();
        $qty = $item->getQtyOrdered();
        $currency = $this->storeConfig->getStore()->getCurrentCurrencyCode();

        $data = array(
            'product_id' => $productId,
            'price' => $price,
            'quantity' => $qty,
            'currency' => $currency
        );

        if(defined("JSON_UNESCAPED_SLASHES")){
            $payload = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } else {
            $payload = str_replace('\\/', '/', json_encode($data));
        }

        $this->_pixleeData->postToAPI("/analytics?api_key=" . $this->apiKey, $payload);
    }
}
