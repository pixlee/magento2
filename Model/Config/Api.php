<?php
/**
 * Copyright © Pixlee TurnTo, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Store\Model\ScopeInterface;

class Api
{
    public const PIXLEE_ACTIVE = 'pixlee_pixlee/existing_customers/account_settings/active';
    public const PIXLEE_API_KEY = 'pixlee_pixlee/existing_customers/account_settings/api_key';
    public const PIXLEE_SECRET_KEY = 'pixlee_pixlee/existing_customers/account_settings/secret_key';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var WriterInterface
     */
    protected $configWriter;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param WriterInterface $writer
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        WriterInterface $writer
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->configWriter = $writer;
    }

    /**
     * @param null|string $scopeType
     * @param int|null|string $scopeCode
     * @return bool
     */
    public function isActive($scopeType, $scopeCode)
    {
        return $this->scopeConfig->isSetFlag(
            self::PIXLEE_ACTIVE,
            $scopeType,
            $scopeCode
        );
    }

    /**
     * @param int|null|string $scopeCode
     * @param null|string $scopeType
     * @return void
     */
    public function deleteActive($scopeType, $scopeCode)
    {
        $this->configWriter->delete(
            self::PIXLEE_ACTIVE,
            $scopeType,
            $scopeCode
        );
    }

    /**
     * @param int|null|string $scopeCode
     * @param null|string $scopeType
     * @return mixed
     */
    public function getApiKey($scopeType, $scopeCode)
    {
        return $this->scopeConfig->getValue(
            self::PIXLEE_API_KEY,
            $scopeType,
            $scopeCode
        );
    }

    /**
     * @param int|null|string $scopeCode
     * @param null|string $scopeType
     * @return void
     */
    public function deleteApiKey($scopeType, $scopeCode)
    {
        $this->configWriter->delete(
            self::PIXLEE_API_KEY,
            $scopeType,
            $scopeCode
        );
    }

    /**
     * @param int|null|string $scopeCode
     * @param null|string $scopeType
     * @return mixed
     */
    public function getSecretKey($scopeType, $scopeCode)
    {
        return $this->scopeConfig->getValue(
            self::PIXLEE_SECRET_KEY,
            $scopeType,
            $scopeCode
        );
    }

    /**
     * @param int|null|string $scopeCode
     * @param null|string $scopeType
     * @return void
     */
    public function deleteSecretKey($scopeType, $scopeCode)
    {
        $this->configWriter->delete(
            self::PIXLEE_SECRET_KEY,
            $scopeType,
            $scopeCode
        );
    }

    /**
     * @param $websiteId
     * @return array
     */
    public function getScope($websiteId)
    {
        $scope['scopeCode'] = null;
        $scope['scopeType'] = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
        if (!empty($websiteId)) {
            $scope['scopeCode'] = $websiteId;
            $scope['scopeType'] = ScopeInterface::SCOPE_WEBSITES;
        }

        return $scope;
    }
}
