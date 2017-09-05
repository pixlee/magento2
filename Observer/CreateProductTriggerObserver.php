<?php

namespace Pixlee\Pixlee\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class CreateProductTriggerObserver implements ObserverInterface
{
    // A simple Trait to reuse Sentry Handler instantiation
    use \Pixlee\Pixlee\Helper\Ravenized;

    public function __construct(
        \Pixlee\Pixlee\Helper\Data $pixleeData,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->_pixleeData  = $pixleeData;
        $this->_logger      = $logger;
        // Use the Ravenized trait to instantiate a Sentry Handler
        $this->ravenize();
    }

    public function execute(EventObserver $observer)
    {
        $product = $observer->getEvent()->getProduct();
        if ($product->getStatus() == 1) {
            // Both for consistency with the rest of the Pixlee Observers, and because
            // it seems something's catching any exceptions coming back from the
            // exportProductToPixlee function before bubbling up to me, gonna just
            // leave this call as-is, without wrapping in a try/catch
            $this->_pixleeData->exportProductToPixlee($product);
            $this->_logger->addInfo("[Pixlee] :: createProduct ".json_encode($product));
        }
    }
}
