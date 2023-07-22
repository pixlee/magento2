<?php
/**
 * Copyright © Pixlee TurnTo, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\ViewModel;

use Exception;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;
use Pixlee\Pixlee\Model\Config\Api;
use Pixlee\Pixlee\Model\Config\Widget;

class ProductWidget implements ArgumentInterface
{
    /**
     * @var array
     */
    protected $scope;
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var Api
     */
    protected $apiConfig;
    /**
     * @var Widget
     */
    protected $widgetConfig;

    /**
     * @param Api $apiConfig
     * @param Widget $widgetConfig
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Api $apiConfig,
        Widget $widgetConfig,
        StoreManagerInterface $storeManager
    ) {
        $this->storeManager = $storeManager;
        $this->apiConfig = $apiConfig;
        $this->widgetConfig = $widgetConfig;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        $scope = $this->getScope();
        return $this->apiConfig->isActive($scope['scopeType'], $scope['scopeCode']);
    }

    /**
     * @return mixed
     */
    public function getApiKey()
    {
        $scope = $this->getScope();
        return $this->apiConfig->getApiKey($scope['scopeType'], $scope['scopeCode']);
    }

    /**
     * @return mixed
     */
    public function getAccountId()
    {
        $scope = $this->getScope();
        return $this->widgetConfig->getAccountId($scope['scopeType'], $scope['scopeCode']);
    }

    /**
     * @return mixed
     */
    public function getCDPWidgetId()
    {
        $scope = $this->getScope();
        return $this->widgetConfig->getCDPWidgetId($scope['scopeType'], $scope['scopeCode']);
    }

    /**
     * @return mixed
     */
    public function getPDPWidgetId()
    {
        $scope = $this->getScope();
        return $this->widgetConfig->getPDPWidgetId($scope['scopeType'], $scope['scopeCode']);
    }

    /**
     * @return array
     */
    protected function getScope()
    {
        if (empty($this->scope)) {
            try {
                $websiteId = $this->storeManager->getWebsite()->getId();
            } catch (Exception $e) {
                $websiteId = $this->storeManager->getDefaultStoreView()->getWebsiteId();
            }
            $this->scope = $this->apiConfig->getScope($websiteId);
        }

        return $this->scope;
    }
}
