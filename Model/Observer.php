<?php

namespace Pixlee\Pixlee\Model;

class Observer
{
    const ANALYTICS_BASE_URL = 'https://limitless-beyond-4328.herokuapp.com/events/';
    protected $_urls = array();

    public function __construct (
        \Magento\Framework\Pricing\Helper\Data $pricingHelper,
        \Magento\Checkout\Model\Cart $checkoutCart,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Directory\Model\Region $directoryRegion,
        \Pixlee\Pixlee\Helper\Data $pixleeData,
        \Psr\Log\LoggerInterface $logger
        ) {
        // Prepare URLs used to ping Pixlee analytics server
        $this->_urls['addToCart'] = self::ANALYTICS_BASE_URL . 'addToCart';
        $this->_urls['removeFromCart'] = self::ANALYTICS_BASE_URL . 'removeFromCart';
        $this->_urls['checkoutStart'] = self::ANALYTICS_BASE_URL . 'checkoutStart';
        $this->_urls['checkoutSuccess'] = self::ANALYTICS_BASE_URL . 'conversion';

        $this->_pricingHelper = $pricingHelper;
        $this->_checkoutCart = $checkoutCart;
        $this->_checkoutSession = $checkoutSession;
        $this->_directoryRegion = $directoryRegion;
        $this->_pixleeData  = $pixleeData;
        $this->_logger = $logger;
    }

    public function addToCart(\Magento\Framework\Event\Observer $observer) {
        $this->_logger->addInfo("[Pixlee] :: addToCart start");
        $product = $observer->getEvent()->getProduct();
        $productData = array('product' => $this->_extractProduct($product));
        $payload = $this->_preparePayload($productData);
        $this->_sendPayload('addToCart', $payload);

        $this->_logger->addInfo("[Pixlee] :: addToCart ".json_encode($payload));
    }

    public function removeFromCart(\Magento\Framework\Event\Observer $observer) {
        $product = $observer->getEvent()->getQuoteItem();
        $productData = array('product' => $this->_extractProduct($product));
        $payload = $this->_preparePayload($productData);
        $this->_sendPayload('removeFromCart', $payload);

        $this->_logger->addInfo("[Pixlee] :: removeFromCart ".json_encode($payload));
    }

    public function checkoutStart(\Magento\Framework\Event\Observer $observer) {
        $quote = $this->_checkoutCart->getQuote();
        $cartData = $this->_extractCart($quote);
        $payload = array('cart' => $cartData);
        $payload = $this->_preparePayload($payload);
        $this->_sendPayload('checkoutStart', $payload);

        $this->_logger->addInfo("[Pixlee] :: checkoutStart ".json_encode($payload));
    }

    public function checkoutSuccess(\Magento\Framework\Event\Observer $observer) {
        $order = new Magento\Sales\Model\Order();
        $incrementId = $this->_checkoutSession->getLastRealOrderId();
        $order->loadByIncrementId($incrementId);

        $cartData = $this->_extractCart($order);
        $customerData = $this->_extractCustomer($order);
        $payload = array('cart' => $cartData, 'customer' => $customerData);

        $payload = $this->_preparePayload($payload);
        $this->_sendPayload('checkoutSuccess', $payload);

        // $cartData = $this->_extractCart($quote);
        // $payload = array('cart' => $cartData);
        // $payload = $this->_preparePayload($payload);
        // $this->_sendPayload('checkoutStart', $payload);

        $this->_logger->addInfo("[Pixlee] :: checkoutSuccess ".json_encode($payload));
    }

    public function createProductTrigger(\Magento\Framework\Event\Observer $observer) {
        $this->_logger->addInfo("[Pixlee] :: createProductTrigger");
    }

    public function validateCredentials(\Magento\Framework\Event\Observer $observer) {
        $this->_logger->addInfo("[Pixlee] :: validateCredentials");
    }
}