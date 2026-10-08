<?php
/**
 * Copyright © Emplifi, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Test\Unit\Model\Export;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\LinkedProductSelectBuilderInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\ConfigurableProduct\Pricing\Price\ConfigurableOptionsProvider;
use Magento\ConfigurableProduct\Pricing\Price\LowestPriceOptionsProvider;
use Magento\ConfigurableProduct\Pricing\Price\LowestPriceOptionsProviderInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Pixlee\Pixlee\Model\Export\MagentoExportPricingCacheReset;
use Pixlee\Pixlee\Test\Unit\AbstractUnitTestCase;
use ReflectionProperty;

class MagentoExportPricingCacheResetTest extends AbstractUnitTestCase
{
    public function testResetClearsLowestPriceOptionsProviderLinkedProductMap(): void
    {
        $connection = $this->createMock(AdapterInterface::class);
        $resourceConnection = $this->createPartialMock(ResourceConnection::class, ['getConnection']);
        $resourceConnection->method('getConnection')->willReturn($connection);

        $linkedProductSelectBuilder = $this->createPartialMock(
            LinkedProductSelectBuilderInterface::class,
            ['build']
        );
        $linkedProductSelectBuilder->method('build')->willReturn([]);

        $productCollection = $this->createPartialMock(
            Collection::class,
            ['addAttributeToSelect', 'addIdFilter', 'getItems']
        );
        $productCollection->method('addAttributeToSelect')->willReturnSelf();
        $productCollection->method('addIdFilter')->willReturnSelf();
        $productCollection->method('getItems')->willReturn(['child-product']);

        $collectionFactory = $this->createPartialMock(CollectionFactory::class, ['create']);
        $collectionFactory->method('create')->willReturn($productCollection);

        $storeManager = $this->createPassiveDouble(StoreManagerInterface::class);
        $store = $this->createConfiguredPassiveDouble(Store::class, ['getId' => 1]);
        $storeManager->method('getStore')->willReturn($store);

        $lowestPriceOptionsProvider = new LowestPriceOptionsProvider(
            $resourceConnection,
            $linkedProductSelectBuilder,
            $collectionFactory,
            $storeManager
        );

        $product = $this->createConfiguredPassiveDouble(Product::class, [
            'getId' => 42,
            'getStoreId' => 1,
        ]);
        $lowestPriceOptionsProvider->getProducts($product);

        $mapProperty = new ReflectionProperty(LowestPriceOptionsProvider::class, 'linkedProductMap');
        $this->assertNotEmpty($mapProperty->getValue($lowestPriceOptionsProvider));

        $configurableOptionsProvider = $this->createMock(ConfigurableOptionsProvider::class);
        $configurableOptionsProvider->expects($this->once())->method('_resetState');

        $subject = new MagentoExportPricingCacheReset(
            $lowestPriceOptionsProvider,
            $configurableOptionsProvider
        );
        $subject->reset();

        $this->assertSame([], $mapProperty->getValue($lowestPriceOptionsProvider));
    }

    public function testResetStillResetsConfigurableOptionsWhenLowestPriceProviderIsCustom(): void
    {
        $customLowestPriceProvider = new class implements LowestPriceOptionsProviderInterface {
            public function getProducts(ProductInterface $product): array
            {
                return [];
            }
        };

        $configurableOptionsProvider = $this->createMock(ConfigurableOptionsProvider::class);
        $configurableOptionsProvider->expects($this->once())->method('_resetState');

        $subject = new MagentoExportPricingCacheReset(
            $customLowestPriceProvider,
            $configurableOptionsProvider
        );
        $subject->reset();
    }
}
