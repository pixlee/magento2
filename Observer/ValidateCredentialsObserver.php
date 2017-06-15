<?php

namespace Pixlee\Pixlee\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;

class ValidateCredentialsObserver implements ObserverInterface
{
    // A simple Trait to reuse Sentry Handler instantiation
    use \Pixlee\Pixlee\Helper\Ravenized;

	/**
     * @var ManagerInterface
     */
    protected $messageManager;

	public function __construct(
        \Pixlee\Pixlee\Helper\Data $pixleeData,
        ManagerInterface $messageManager,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->_pixleeData  = $pixleeData;
        $this->messageManager = $messageManager;
        $this->_logger      = $logger;
        // Use the Ravenized trait to instantiate a Sentry Handler
        $this->ravenize();
    }

    public function execute(EventObserver $observer)
    {
        $this->_pixleeData->_validateCredentials();
    }
}
