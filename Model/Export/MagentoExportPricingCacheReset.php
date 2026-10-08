<?php
/**
 * Copyright © Emplifi, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Model\Export;

use Magento\ConfigurableProduct\Pricing\Price\ConfigurableOptionsProviderInterface;
use Magento\ConfigurableProduct\Pricing\Price\LowestPriceOptionsProvider;
use Magento\ConfigurableProduct\Pricing\Price\LowestPriceOptionsProviderInterface;
use Magento\Framework\ObjectManager\ResetAfterRequestInterface;
use ReflectionProperty;

/**
 * Magento keeps configurable child products in a request-scoped singleton cache.
 * Long-running cron exports for multiple websites must clear that cache between websites.
 */
class MagentoExportPricingCacheReset implements ExportPricingCacheResetInterface
{
    /**
     * @var LowestPriceOptionsProviderInterface
     */
    private $lowestPriceOptionsProvider;

    /**
     * @var ConfigurableOptionsProviderInterface
     */
    private $configurableOptionsProvider;

    /**
     * @param LowestPriceOptionsProviderInterface $lowestPriceOptionsProvider
     * @param ConfigurableOptionsProviderInterface $configurableOptionsProvider
     */
    public function __construct(
        LowestPriceOptionsProviderInterface $lowestPriceOptionsProvider,
        ConfigurableOptionsProviderInterface $configurableOptionsProvider
    ) {
        $this->lowestPriceOptionsProvider = $lowestPriceOptionsProvider;
        $this->configurableOptionsProvider = $configurableOptionsProvider;
    }

    /**
     * @inheritdoc
     */
    public function reset(): void
    {
        if ($this->lowestPriceOptionsProvider instanceof LowestPriceOptionsProvider) {
            $linkedProductMap = new ReflectionProperty(
                LowestPriceOptionsProvider::class,
                'linkedProductMap'
            );
            $linkedProductMap->setValue($this->lowestPriceOptionsProvider, []);
        }

        if ($this->configurableOptionsProvider instanceof ResetAfterRequestInterface) {
            $this->configurableOptionsProvider->_resetState();
        }
    }
}
