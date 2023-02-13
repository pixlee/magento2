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
    protected ScopeConfigInterface $scopeConfig;
    /**
     * @var WriterInterface
     */
    protected WriterInterface $configWriter;

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
     * @param int|null|string $scopeId
     * @param null|string $scopeType
     * @return bool
     */
    public function isActive($scopeId, $scopeType = ScopeInterface::SCOPE_WEBSITES)
    {
        return $this->scopeConfig->isSetFlag(
            self::PIXLEE_ACTIVE,
            $scopeType,
            $scopeId
        );
    }

    /**
     * @param int|null|string $scopeId
     * @param null|string $scopeType
     * @return void
     */
    public function deleteActive($scopeId, $scopeType = ScopeInterface::SCOPE_WEBSITES)
    {
        $this->configWriter->delete(
            self::PIXLEE_ACTIVE,
            $scopeType,
            $scopeId
        );
    }

    /**
     * @param int|null|string $scopeId
     * @param null|string $scopeType
     * @return mixed
     */
    public function getApiKey($scopeId, $scopeType = ScopeInterface::SCOPE_WEBSITES)
    {
        return $this->scopeConfig->getValue(
            self::PIXLEE_API_KEY,
            $scopeType,
            $scopeId
        );
    }

    /**
     * @param int|null|string $scopeId
     * @param null|string $scopeType
     * @return void
     */
    public function deleteApiKey($scopeId, $scopeType = ScopeInterface::SCOPE_WEBSITES)
    {
        $this->configWriter->delete(
            self::PIXLEE_API_KEY,
            $scopeType,
            $scopeId
        );
    }

    /**
     * @param int|null|string $scopeId
     * @param null|string $scopeType
     * @return mixed
     */
    public function getSecretKey($scopeId, $scopeType = ScopeInterface::SCOPE_WEBSITES)
    {
        return $this->scopeConfig->getValue(
            self::PIXLEE_SECRET_KEY,
            $scopeType,
            $scopeId
        );
    }

    /**
     * @param int|null|string $scopeId
     * @param null|string $scopeType
     * @return void
     */
    public function deleteSecretKey($scopeId = null, $scopeType = ScopeInterface::SCOPE_WEBSITES)
    {
        $this->configWriter->delete(
            self::PIXLEE_SECRET_KEY,
            $scopeType,
            $scopeId
        );
    }
}
