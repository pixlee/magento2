<?php
/**
 * Copyright © Pixlee TurnTo, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Widget
{
    /**
     * Config paths
     */
    public const PIXLEE_ACCOUNT_ID = 'pixlee_pixlee/existing_customers/pdp_widget_settings/account_id';
    public const PIXLEE_PDP_WIDGET_ID = 'pixlee_pixlee/existing_customers/pdp_widget_settings/pdp_widget_id';
    public const PIXLEE_CDP_WIDGET_ID = 'pixlee_pixlee/existing_customers/pdp_widget_settings/cdp_widget_id';
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;


    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param int|null|string $scopeCode
     * @param null|string $scopeType
     * @return mixed
     */
    public function getAccountId($scopeCode = null, $scopeType = ScopeInterface::SCOPE_WEBSITES)
    {
        return $this->scopeConfig->getValue(
            self::PIXLEE_ACCOUNT_ID,
            $scopeType,
            $scopeCode
        );
    }

    /**
     * @param int|null|string $scopeCode
     * @param null|string $scopeType
     * @return mixed
     */
    public function getPDPWidgetId($scopeCode = null, $scopeType = ScopeInterface::SCOPE_WEBSITES)
    {
        return $this->scopeConfig->getValue(
            self::PIXLEE_PDP_WIDGET_ID,
            $scopeType,
            $scopeCode
        );
    }

    /**
     * @param int|null|string $scopeCode
     * @param null|string $scopeType
     * @return mixed
     */
    public function getCDPWidgetId($scopeCode = null, $scopeType = ScopeInterface::SCOPE_WEBSITES)
    {
        return $this->scopeConfig->getValue(
            self::PIXLEE_CDP_WIDGET_ID,
            $scopeType,
            $scopeCode
        );
    }
}
