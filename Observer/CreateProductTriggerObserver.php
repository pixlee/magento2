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

        // Both for consistency with the rest of the Pixlee Observers, and because
        // it seems something's catching any exceptions coming back from the
        // exportProductToPixlee function before bubbling up to me, gonna just
        // leave this call as-is, without wrapping in a try/catch
        $this->_pixleeData->exportProductToPixlee($product);
        $this->_logger->addInfo("[Pixlee] :: createProduct ".json_encode($product));
    }
}
