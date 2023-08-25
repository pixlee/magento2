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
use Pixlee\Pixlee\Model\Config\Api;
use Pixlee\Pixlee\Api\PixleeServiceInterface;

class ValidateCredentialsObserver implements ObserverInterface
{
    /**
     * @var Api
     */
    protected $apiConfig;
    /**
     * @var PixleeServiceInterface
     */
    protected $pixleeService;

    /**
     * @param Api $apiConfig
     * @param PixleeServiceInterface $pixleeService
     */
    public function __construct(
        Api $apiConfig,
        PixleeServiceInterface $pixleeService
    ) {
        $this->apiConfig = $apiConfig;
        $this->pixleeService = $pixleeService;
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
     * @param string $websiteId
     * @return void
     * @throws Exception
     */
    public function validateCredentials($websiteId)
    {
        // Backend models are not available for group of items.
        $scope = $this->apiConfig->getScope($websiteId);
        if ($this->apiConfig->isActive($scope['scopeType'], $scope['scopeCode'])) {
            $this->pixleeService->setScope($scope['scopeType'], $scope['scopeCode']);
            $validated = $this->pixleeService->validateCredentials();
            if ($validated === false) {
                $this->apiConfig->deleteActive($scope['scopeType'], $scope['scopeCode']);
                $this->apiConfig->deleteApiKey($scope['scopeType'], $scope['scopeCode']);
                $this->apiConfig->deleteSecretKey($scope['scopeType'], $scope['scopeCode']);

                throw new Exception('Invalid API key or secret.');
            }
        }
    }
}
