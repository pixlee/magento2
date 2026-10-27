<?php
/**
 * Copyright © Emplifi, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Test\Unit\Model\Export;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\ConfigurableProduct\Pricing\Price\ConfigurableOptionsProvider;
use Magento\ConfigurableProduct\Pricing\Price\ConfigurableOptionsProviderInterface;
use Magento\ConfigurableProduct\Pricing\Price\LowestPriceOptionsProvider;
use Magento\ConfigurableProduct\Pricing\Price\LowestPriceOptionsProviderInterface;
use Pixlee\Pixlee\Model\Export\MagentoExportPricingCacheReset;
use Pixlee\Pixlee\Test\Unit\AbstractUnitTestCase;
use ReflectionClass;
use ReflectionProperty;

class MagentoExportPricingCacheResetTest extends AbstractUnitTestCase
{
    public function testResetClearsLowestPriceOptionsProviderLinkedProductMap(): void
    {
        $lowestPriceOptionsProvider = $this->instantiateWithoutConstructor(LowestPriceOptionsProvider::class);
        $this->writeProperty($lowestPriceOptionsProvider, LowestPriceOptionsProvider::class, 'linkedProductMap', [
            '1-42' => ['child-product'],
        ]);

        $subject = new MagentoExportPricingCacheReset(
            $lowestPriceOptionsProvider,
            $this->customConfigurableOptionsProvider()
        );
        $subject->reset();

        $this->assertSame(
            [],
            $this->readProperty($lowestPriceOptionsProvider, LowestPriceOptionsProvider::class, 'linkedProductMap')
        );
    }

    public function testResetClearsConfigurableOptionsProviderProducts(): void
    {
        $configurableOptionsProvider = $this->instantiateWithoutConstructor(ConfigurableOptionsProvider::class);
        $this->writeProperty($configurableOptionsProvider, ConfigurableOptionsProvider::class, 'products', [
            42 => ['child-product'],
        ]);

        $subject = new MagentoExportPricingCacheReset(
            $this->customLowestPriceOptionsProvider(),
            $configurableOptionsProvider
        );
        $subject->reset();

        $this->assertNull(
            $this->readProperty($configurableOptionsProvider, ConfigurableOptionsProvider::class, 'products')
        );
    }

    public function testResetIsSafeForCustomProviderImplementations(): void
    {
        $subject = new MagentoExportPricingCacheReset(
            $this->customLowestPriceOptionsProvider(),
            $this->customConfigurableOptionsProvider()
        );
        $subject->reset();

        $this->addToAssertionCount(1);
    }

    /**
     * Instantiate a core class without its constructor, whose signature differs across Magento versions.
     *
     * @param string $class
     * @return object
     */
    private function instantiateWithoutConstructor(string $class)
    {
        return (new ReflectionClass($class))->newInstanceWithoutConstructor();
    }

    /**
     * Write a private property.
     *
     * @param object $object
     * @param string $class
     * @param string $property
     * @param mixed $value
     * @return void
     */
    private function writeProperty($object, string $class, string $property, $value): void
    {
        $this->accessibleProperty($class, $property)->setValue($object, $value);
    }

    /**
     * Read a private property.
     *
     * @param object $object
     * @param string $class
     * @param string $property
     * @return mixed
     */
    private function readProperty($object, string $class, string $property)
    {
        return $this->accessibleProperty($class, $property)->getValue($object);
    }

    /**
     * Build a reflection property usable on every supported PHP version.
     *
     * @param string $class
     * @param string $property
     * @return ReflectionProperty
     */
    private function accessibleProperty(string $class, string $property): ReflectionProperty
    {
        $reflection = new ReflectionProperty($class, $property);
        if (PHP_VERSION_ID < 80100) {
            $reflection->setAccessible(true);
        }

        return $reflection;
    }

    /**
     * Lowest price provider that is not the core implementation.
     *
     * @return LowestPriceOptionsProviderInterface
     */
    private function customLowestPriceOptionsProvider(): LowestPriceOptionsProviderInterface
    {
        return new class() implements LowestPriceOptionsProviderInterface {
            /**
             * @inheritdoc
             */
            public function getProducts(ProductInterface $product)
            {
                return [];
            }
        };
    }

    /**
     * Configurable options provider that is not the core implementation.
     *
     * @return ConfigurableOptionsProviderInterface
     */
    private function customConfigurableOptionsProvider(): ConfigurableOptionsProviderInterface
    {
        return new class() implements ConfigurableOptionsProviderInterface {
            /**
             * @inheritdoc
             */
            public function getProducts(ProductInterface $product)
            {
                return [];
            }
        };
    }
}
