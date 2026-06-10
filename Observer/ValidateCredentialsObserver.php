<?php
/**
 * Copyright © Emplifi, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
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
     * Validate credentials observer
     *
     * @param EventObserver $observer
     * @return void
     * @throws LocalizedException
     */
    public function execute(EventObserver $observer)
    {
        $websiteId = $observer->getEvent()->getData('website');
        $this->validateCredentials($websiteId);
    }

    /**
     * Validate credentials for a given website
     *
     * @param string $websiteId
     * @return void
     * @throws LocalizedException
     */
    public function validateCredentials($websiteId)
    {
        // Backend models are not available for group of items.
        $scope = $this->apiConfig->getScope($websiteId);
        if ($this->apiConfig->isActive($scope['scopeType'], $scope['scopeCode'])) {
            $this->pixleeService->setScope($scope['scopeType'], $scope['scopeCode']);
            $validated = $this->pixleeService->validateCredentials();
            if (!$validated) {
                $this->apiConfig->deleteActive($scope['scopeType'], $scope['scopeCode']);
                $this->apiConfig->deletePrivateApiKey($scope['scopeType'], $scope['scopeCode']);
                $this->apiConfig->deleteSecretKey($scope['scopeType'], $scope['scopeCode']);

                throw new LocalizedException(__('Invalid Private API Key or Secret Key.'));
            }
        }
    }
}
