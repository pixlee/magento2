<?php
/**
 * Copyright © Pixlee TurnTo, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\ViewModel;

use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use Pixlee\Pixlee\Model\Config\Api;
use Pixlee\Pixlee\Model\Config\Widget;

class ProductWidget implements \Magento\Framework\View\Element\Block\ArgumentInterface
{
    /**
     * @var int
     */
    protected $websiteId;
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
     * @return int
     * @throws LocalizedException
     */
    public function getWebsiteId()
    {
        if (empty($this->websiteId)) {
            $this->websiteId = (int)$this->storeManager->getWebsite()->getId();
        }

        return $this->websiteId;
    }

    /**
     * @return bool
     * @throws LocalizedException
     */
    public function isActive()
    {
        return $this->apiConfig->isActive($this->getWebsiteId());
    }

    /**
     * @return mixed
     * @throws LocalizedException
     */
    public function getApiKey()
    {
        return $this->apiConfig->getApiKey($this->getWebsiteId());
    }

    /**
     * @return mixed
     * @throws LocalizedException
     */
    public function getAccountId()
    {
        return $this->widgetConfig->getAccountId($this->getWebsiteId());
    }

    /**
     * @return mixed
     * @throws LocalizedException
     */
    public function getCDPWidgetId()
    {
        return $this->widgetConfig->getCDPWidgetId($this->getWebsiteId());
    }

    /**
     * @return mixed
     * @throws LocalizedException
     */
    public function getPDPWidgetId()
    {
        return $this->widgetConfig->getPDPWidgetId($this->getWebsiteId());
    }
}
