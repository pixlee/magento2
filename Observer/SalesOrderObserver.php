<?php

namespace Pixlee\Pixlee\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class SalesOrderObserver implements ObserverInterface
{
    public function __construct(
        \Magento\Sales\Model\ResourceModel\Order\Collection $collection,
        \Pixlee\Pixlee\Helper\Data $pixleeData,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->_collection = $collection;
        $this->_pixleeData  = $pixleeData;
        $this->_logger      = $logger;
    }

    public function execute(EventObserver $observer)
    {
        // TRIVIAL MUSINGS AHEAD:
        // This observer listens to 2 very different events: we get an array of order_ids
        // from a conversion event, and an order object from a cancel order event
        // I could have had this observer only listen to the cancel order event, and have
        // the execute() function in the checkout success event call that function in Helper/Data
        // after it parsed the cart items, but doing it this way it's more obvious in the
        // /etc/events.xml that stock/inventory is updated in 2 places

        $this->_logger->addInfo("[Pixlee] :: start of Stock update");

        // If we're here from a conversion event, e.g.
        //      multishipping_checkout_controller_success_action or
        //      checkout_onepage_controller_success_action
        // We can use $observer->getEvent()->getOrderIds();
        // to get an ARRAY OF ORDER IDS
        $orderIds = $observer->getEvent()->getOrderIds();

        // But if we're here from
        //      order_cancel_after
        // We need to instead use $observer->getEvent()->getOrder();
        // to instead get AN INDIVIDUAL ORDER
        $order = $observer->getEvent()->getOrder();

        // Coming from conversion
        if ($orderIds !== null) {
            $this->_collection->addFieldToFilter('entity_id', ['in' => $orderIds]);
            foreach ($this->_collection as $order) {
                /*
                $cartData = $this->_pixleeData->_extractCart($order);
                $payload = $this->_pixleeData->_preparePayload($cartData);
                $this->_pixleeData->_sendPayload('checkoutSuccess', $payload);
                */
                foreach ($order->getAllVisibleItems() as $item) {
                    $product = $item->getProduct();

                    $this->_logger->addDebug("Sales product class: " . get_class($product));
                    $this->_logger->addDebug("Sales product ID: {$product->getId()}");
                    $this->_logger->addDebug("Sales product SKU: {$product->getSku()}");
                    $this->_logger->addDebug("Sales product type: {$product->getTypeId()}");
                    $categoriesMap = $this->_pixleeData->getCategoriesMap();
                    $this->_pixleeData->exportProductToPixlee($product, $categoriesMap);
                }
            }
        // Coming from order cancelled
        } elseif ($order !== null) {
            foreach ($order->getAllVisibleItems() as $item) {
                $product = $item->getProduct();

                $this->_logger->addDebug("Sales product class: " . get_class($product));
                $this->_logger->addDebug("Sales product ID: {$product->getId()}");
                $this->_logger->addDebug("Sales product SKU: {$product->getSku()}");
                $this->_logger->addDebug("Sales product type: {$product->getTypeId()}");
                $categoriesMap = $this->_pixleeData->getCategoriesMap();
                $this->_pixleeData->exportProductToPixlee($product, $categoriesMap);
            }
        } else {
            return $this;
        }
    }
}
