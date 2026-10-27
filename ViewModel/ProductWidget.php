<?php
/**
 * Copyright © Emplifi, Inc. All rights reserved.
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
     * Whether the extension is enabled for the current website
     *
     * @return bool
     */
    public function isActive()
    {
        $scope = $this->getScope();
        return $this->apiConfig->isActive($scope['scopeType'], $scope['scopeCode']);
    }

    /**
     * Get the public API key for the current website
     *
     * @return mixed
     */
    public function getApiKey()
    {
        $scope = $this->getScope();
        return $this->apiConfig->getApiKey($scope['scopeType'], $scope['scopeCode']);
    }

    /**
     * Get the numeric account ID, or null when it is not configured
     *
     * @return int|null
     */
    public function getAccountId()
    {
        $scope = $this->getScope();
        return $this->toNumericId($this->widgetConfig->getAccountId($scope['scopeType'], $scope['scopeCode']));
    }

    /**
     * Get the numeric CDP widget ID, or null when it is not configured
     *
     * @return int|null
     */
    public function getCDPWidgetId()
    {
        $scope = $this->getScope();
        return $this->toNumericId($this->widgetConfig->getCDPWidgetId($scope['scopeType'], $scope['scopeCode']));
    }

    /**
     * Get the numeric PDP widget ID, or null when it is not configured
     *
     * @return int|null
     */
    public function getPDPWidgetId()
    {
        $scope = $this->getScope();
        return $this->toNumericId($this->widgetConfig->getPDPWidgetId($scope['scopeType'], $scope['scopeCode']));
    }

    /**
     * Get the website scope used for configuration lookups
     *
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

    /**
     * Normalize an admin-entered ID so templates can output it as a JSON number
     *
     * @param mixed $value
     * @return int|null
     */
    protected function toNumericId($value)
    {
        $value = trim((string) $value);
        if ($value === '' || !ctype_digit($value)) {
            return null;
        }

        return (int) $value;
    }
}
