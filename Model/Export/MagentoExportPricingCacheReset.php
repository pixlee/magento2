<?php
/**
 * Copyright © Emplifi, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Model\Export;

use Magento\ConfigurableProduct\Pricing\Price\ConfigurableOptionsProvider;
use Magento\ConfigurableProduct\Pricing\Price\ConfigurableOptionsProviderInterface;
use Magento\ConfigurableProduct\Pricing\Price\LowestPriceOptionsProvider;
use Magento\ConfigurableProduct\Pricing\Price\LowestPriceOptionsProviderInterface;
use Magento\Framework\ObjectManager\ResetAfterRequestInterface;
use ReflectionProperty;

/**
 * Magento keeps configurable child products in request-scoped singleton caches that never expire.
 * Long-running exports must clear them periodically or memory grows with every configurable product.
 *
 * Core exposes no public reset for LowestPriceOptionsProvider, and ResetAfterRequestInterface only
 * exists on Magento 2.4.7+, so private properties are cleared via reflection when necessary.
 */
class MagentoExportPricingCacheReset implements ExportPricingCacheResetInterface
{
    /**
     * @var LowestPriceOptionsProviderInterface
     */
    protected $lowestPriceOptionsProvider;

    /**
     * @var ConfigurableOptionsProviderInterface
     */
    protected $configurableOptionsProvider;

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
        $this->resetProvider(
            $this->lowestPriceOptionsProvider,
            LowestPriceOptionsProvider::class,
            'linkedProductMap',
            []
        );
        $this->resetProvider(
            $this->configurableOptionsProvider,
            ConfigurableOptionsProvider::class,
            'products',
            null
        );
    }

    /**
     * Reset a core provider through its public reset hook, or by clearing its private cache property.
     *
     * Custom implementations and core versions without the expected property are left untouched.
     *
     * @param object $provider
     * @param string $coreClass
     * @param string $property
     * @param array|null $emptyValue
     * @return void
     */
    protected function resetProvider($provider, string $coreClass, string $property, ?array $emptyValue): void
    {
        if ($provider instanceof ResetAfterRequestInterface) {
            $provider->_resetState();
            return;
        }

        if (!$provider instanceof $coreClass || !property_exists($coreClass, $property)) {
            return;
        }

        $reflection = new ReflectionProperty($coreClass, $property);
        if (PHP_VERSION_ID < 80100) {
            $reflection->setAccessible(true);
        }
        $reflection->setValue($provider, $emptyValue);
    }
}
