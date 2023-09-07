<?php

namespace Pixlee\Pixlee\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;

class Product
{
    public const XML_PATH_PRODUCTS_EXPORT_ENABLED = 'pixlee_pixlee/products/export_enabled';
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
    public function isCronEnabled($scopeType, $scopeCode): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_PRODUCTS_EXPORT_ENABLED,
            $scopeType,
            $scopeCode
        );
    }
}
