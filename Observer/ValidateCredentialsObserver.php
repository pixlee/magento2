<?php
/**
 * Copyright © Pixlee TurnTo, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Observer;

use Exception;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Pixlee\Pixlee\Model\Logger\PixleeLogger;
use Pixlee\Pixlee\Model\Config\Api;
use Pixlee\Pixlee\Api\PixleeServiceInterface;

class ValidateCredentialsObserver implements ObserverInterface
{
    /**
     * @var Api
     */
    protected Api $apiConfig;
    /**
     * @var PixleeServiceInterface
     */
    protected PixleeServiceInterface $pixleeService;
    /**
     * @var PixleeLogger
     */
    protected PixleeLogger $logger;

    /**
     * @param Api $apiConfig
     * @param PixleeServiceInterface $pixleeService
     * @param PixleeLogger $logger
     */
    public function __construct(
        Api $apiConfig,
        PixleeServiceInterface $pixleeService,
        PixleeLogger $logger
    ) {
        $this->apiConfig = $apiConfig;
        $this->pixleeService = $pixleeService;
        $this->logger = $logger;
    }

    /**
     * @param EventObserver $observer
     * @return void
     * @throws Exception
     */
    public function execute(EventObserver $observer)
    {
        $websiteId = $observer->getEvent()->getData('website');
        $this->validateCredentials($websiteId);
    }

    /**
     * @param int|string $websiteId
     * @return void
     * @throws Exception
     */
    public function validateCredentials($websiteId)
    {
        // Backend models are not available for group of items.
        if ($this->apiConfig->isActive($websiteId)) {
            $this->logger->addInfo('Validating Credentials');
            $validated = $this->pixleeService->validateCredentials($websiteId);
            if ($validated === false) {
                $this->apiConfig->deleteActive($websiteId);
                $this->apiConfig->deleteApiKey($websiteId);
                $this->apiConfig->deleteSecretKey($websiteId);

                throw new Exception('Invalid API key or secret.');
            }
        }
    }
}
