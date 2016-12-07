<?php

namespace Pixlee\Pixlee\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class CheckoutSuccessObserver implements ObserverInterface
{
    // A simple Trait to reuse Sentry Handler instantiation
    use \Pixlee\Pixlee\Helper\Ravenized;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Collection
     */
    protected $_collection;

    public function __construct(
        \Magento\Sales\Model\ResourceModel\Order\Collection $collection,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Pixlee\Pixlee\Helper\Data $pixleeData,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->_collection = $collection;
        $this->_checkoutSession = $checkoutSession;
        $this->_pixleeData  = $pixleeData;
        $this->_logger      = $logger;
        // Use the Ravenized trait to instantiate a Sentry Handler
        $this->ravenize();
    }

    public function execute(EventObserver $observer)
    {
        $this->_logger->addInfo("[Pixlee] :: start of Conversion");

        $orderIds = $observer->getEvent()->getOrderIds();
        if (!$orderIds || !is_array($orderIds)) {
            return $this;
        }

        $this->_collection->addFieldToFilter('entity_id', ['in' => $orderIds]);
        foreach ($this->_collection as $order) {
            $cartData = $this->_pixleeData->_extractCart($order);
            $payload = $this->_pixleeData->_preparePayload($cartData);
            $this->_pixleeData->_sendPayload('checkoutSuccess', $payload);
        }
        $this->_logger->addInfo("[Pixlee] :: checkoutSuccess ".json_encode($payload));
    }
}
