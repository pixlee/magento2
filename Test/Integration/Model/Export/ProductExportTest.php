<?php
/**
 * Copyright © Emplifi, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Test\Integration\Model\Export;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Indexer\Product\Price\Processor as PriceIndexerProcessor;
use Magento\Catalog\Model\Product\Visibility;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Pixlee\Pixlee\Api\PixleeServiceInterface;
use Pixlee\Pixlee\Model\Export\Product as ProductExport;
use Pixlee\Pixlee\Model\Pixlee;
use Pixlee\Pixlee\Test\Integration\Model\Export\PixleeServiceSpy;

/**
 * Integration coverage for the product export against a real Magento product.
 *
 * The real {@see PixleeServiceInterface} (Distillery) is replaced with an
 * in-memory spy so that we can inspect the payloads the export *would* send
 * to Emplifi without performing any network I/O.
 */
class ProductExportTest extends TestCase
{
    private const DEFAULT_WEBSITE_ID = 1;

    /**
     * The collection used by the export must surface enabled, catalog-visible
     * products assigned to the website.
     *
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     */
    public function testProductCollectionContainsEnabledVisibleProduct(): void
    {
        $this->reindexProductPrice();

        /** @var ProductExport $export */
        $export = Bootstrap::getObjectManager()->create(ProductExport::class);

        $this->assertContains(
            'simple',
            $this->collectSkusFromExportCollection($export->getProductCollection(self::DEFAULT_WEBSITE_ID))
        );
    }

    /**
     * Collection items must carry the EAV attributes the export reads. Without
     * addAttributeToSelect() and store scope, getName() is null on iteration.
     *
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     */
    public function testCollectionItemLoadsNameAndPriceFromProductCollection(): void
    {
        $this->reindexProductPrice();

        /** @var ProductExport $export */
        $export = Bootstrap::getObjectManager()->create(ProductExport::class);

        $collection = $export->getProductCollection(self::DEFAULT_WEBSITE_ID);
        $product = null;
        foreach ($collection as $item) {
            if ($item->getSku() === 'simple') {
                $product = $item;
                break;
            }
        }

        $this->assertNotNull($product, 'Fixture product "simple" must be in the export collection.');
        $this->assertSame('Simple Product', $product->getName());
        $this->assertNotNull($product->getFinalPrice());
        $this->assertGreaterThan(0, (float) $product->getFinalPrice());
    }

    /**
     * Disabled and not-visible products must not appear in the export collection.
     *
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     * @magentoDataFixture Magento/Catalog/_files/simple_product_disabled.php
     * @magentoDataFixture Magento/Catalog/_files/simple_products_not_visible_individually.php
     */
    public function testProductCollectionExcludesDisabledAndNotVisibleProducts(): void
    {
        $this->reindexProductPrice(['simple', 'product_disabled', 'simple_not_visible_1']);

        /** @var ProductExport $export */
        $export = Bootstrap::getObjectManager()->create(ProductExport::class);

        $skus = $this->collectSkusFromExportCollection(
            $export->getProductCollection(self::DEFAULT_WEBSITE_ID)
        );

        $this->assertContains('simple', $skus);
        $this->assertNotContains('product_disabled', $skus);
        $this->assertNotContains('simple_not_visible_1', $skus);
    }

    /**
     * A simple product reports its own stock quantity and an empty variants
     * dictionary.
     *
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     */
    public function testSimpleProductStockAndVariants(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        /** @var ProductRepositoryInterface $repository */
        $repository = $objectManager->get(ProductRepositoryInterface::class);
        $product = $repository->get('simple');

        /** @var ProductExport $export */
        $export = $objectManager->create(ProductExport::class);

        $this->assertSame(100.0, (float) $export->getAggregateStock($product));
        $this->assertSame('{}', $export->getVariantsDict($product));
    }

    /**
     * The extra-fields blob is valid JSON carrying the platform metadata that
     * Emplifi keys off of.
     *
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     */
    public function testExtraFieldsContainPlatformMetadata(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        /** @var ProductRepositoryInterface $repository */
        $repository = $objectManager->get(ProductRepositoryInterface::class);
        $product = $repository->get('simple');

        /** @var ProductExport $export */
        $export = $objectManager->create(ProductExport::class);

        $extraFields = json_decode($export->getExtraFields($product, []), true);

        $this->assertIsArray($extraFields);
        $this->assertSame(Pixlee::PLATFORM, $extraFields['ecommerce_platform']);
        $this->assertArrayHasKey('ecommerce_platform_version', $extraFields);
        $this->assertArrayHasKey('version_hash', $extraFields);
        $this->assertArrayHasKey('product_photos', $extraFields);
        $this->assertArrayHasKey('categories', $extraFields);
    }

    /**
     * End-to-end: with the account active, exporting a single product hands the
     * Pixlee service a fully formed payload.
     *
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     * @magentoConfigFixture base_website pixlee_pixlee/existing_customers/account_settings/active 1
     */
    public function testExportProductToPixleeBuildsPayload(): void
    {
        $objectManager = Bootstrap::getObjectManager();

        $spy = new PixleeServiceSpy();
        /** @var ProductExport $export */
        $export = $objectManager->create(ProductExport::class, ['pixleeService' => $spy]);

        /** @var ProductRepositoryInterface $repository */
        $repository = $objectManager->get(ProductRepositoryInterface::class);
        $product = $repository->get('simple');

        $store = $objectManager->get(\Magento\Store\Model\StoreManagerInterface::class)
            ->getWebsite(self::DEFAULT_WEBSITE_ID)
            ->getDefaultStore();

        $export->exportProductToPixlee($product, [], self::DEFAULT_WEBSITE_ID, $store);

        $this->assertCount(1, $spy->createdProducts, 'Exactly one product should be exported.');

        $payload = $spy->createdProducts[0];
        $this->assertSame('simple', $payload['sku']);
        $this->assertSame('Simple Product', $payload['name']);
        $this->assertSame((int) $product->getId(), $payload['product_id']);
        $this->assertSame(100.0, (float) $payload['stock']);
        $this->assertSame('{}', $payload['variants']);
        $this->assertNotEmpty($payload['product_url']);
        $this->assertArrayHasKey('currency_code', $payload);
        $this->assertIsArray($payload['regional_info']);
        $this->assertIsString($payload['extra_fields']);
    }

    /**
     * Definitive regression test for the reported export failure.
     *
     * Unlike {@see testExportProductToPixleeBuildsPayload()} — which loads a
     * fully-hydrated product from the repository — this drives the real
     * exportProducts() entry point. That path builds its own collection via
     * getProductCollection() and iterates it; if the collection does not load
     * the product's EAV attributes, getName() returns null and the product is
     * silently skipped, so nothing reaches the Pixlee service.
     *
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     * @magentoConfigFixture base_website pixlee_pixlee/existing_customers/account_settings/active 1
     */
    public function testExportProductsExportsCollectionProductsWithCompleteData(): void
    {
        $this->reindexProductPrice();

        $objectManager = Bootstrap::getObjectManager();

        $spy = new PixleeServiceSpy();
        /** @var ProductExport $export */
        $export = $objectManager->create(ProductExport::class, ['pixleeService' => $spy]);

        $export->exportProducts(self::DEFAULT_WEBSITE_ID);

        // The run must be bracketed by started/finished notifications.
        $this->assertNotEmpty($spy->exportStatuses, 'Export should notify start/finish.');
        $this->assertSame('started', $spy->exportStatuses[0]['status']);
        $this->assertSame(
            'finished',
            $spy->exportStatuses[array_key_last($spy->exportStatuses)]['status']
        );

        $payload = $this->findPayloadBySku($spy->createdProducts, 'simple');
        $this->assertNotNull(
            $payload,
            'The "simple" product was not exported. exportProducts() skipped it — most '
            . 'likely getProductCollection() did not load the product name attribute, '
            . 'so getName() was null and exportProductToPixlee() returned early.'
        );

        // Every expected field must be present and populated.
        $this->assertSame('Simple Product', $payload['name']);
        $this->assertNotEmpty($payload['name']);
        $this->assertSame('simple', $payload['sku']);
        $this->assertGreaterThan(0, $payload['product_id']);
        $this->assertNotEmpty($payload['product_url']);
        $this->assertNotNull($payload['price']);
        $this->assertSame(100.0, (float) $payload['stock']);
        $this->assertSame('{}', $payload['variants']);
        $this->assertNotEmpty($payload['currency_code']);
        $this->assertIsArray($payload['regional_info']);
        $this->assertIsString($payload['extra_fields']);
    }

    /**
     * Products that are not visible in the catalog must be skipped without
     * reaching the Pixlee service.
     *
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     * @magentoConfigFixture base_website pixlee_pixlee/existing_customers/account_settings/active 1
     */
    public function testNotVisibleProductIsNotExported(): void
    {
        $objectManager = Bootstrap::getObjectManager();

        /** @var ProductRepositoryInterface $repository */
        $repository = $objectManager->get(ProductRepositoryInterface::class);
        $product = $repository->get('simple');
        $product->setVisibility(Visibility::VISIBILITY_NOT_VISIBLE);

        $spy = new PixleeServiceSpy();
        /** @var ProductExport $export */
        $export = $objectManager->create(ProductExport::class, ['pixleeService' => $spy]);

        $store = $objectManager->get(\Magento\Store\Model\StoreManagerInterface::class)
            ->getWebsite(self::DEFAULT_WEBSITE_ID)
            ->getDefaultStore();

        $export->exportProductToPixlee($product, [], self::DEFAULT_WEBSITE_ID, $store);

        $this->assertCount(0, $spy->createdProducts);
    }

    /**
     * @magentoDataFixture Magento/ConfigurableProduct/_files/product_configurable.php
     * @magentoConfigFixture base_website pixlee_pixlee/existing_customers/account_settings/active 1
     */
    public function testExportConfigurableProductBuildsPayload(): void
    {
        $this->reindexProductPrice(['configurable', 'simple_10', 'simple_20']);

        $objectManager = Bootstrap::getObjectManager();

        $spy = new PixleeServiceSpy();
        /** @var ProductExport $export */
        $export = $objectManager->create(ProductExport::class, ['pixleeService' => $spy]);

        /** @var ProductRepositoryInterface $repository */
        $repository = $objectManager->get(ProductRepositoryInterface::class);
        $product = $repository->get('configurable');

        $store = $objectManager->get(\Magento\Store\Model\StoreManagerInterface::class)
            ->getWebsite(self::DEFAULT_WEBSITE_ID)
            ->getDefaultStore();

        $export->exportProductToPixlee($product, [], self::DEFAULT_WEBSITE_ID, $store);

        $this->assertCount(1, $spy->createdProducts);

        $payload = $spy->createdProducts[0];
        $this->assertSame('configurable', $payload['sku']);
        $this->assertSame('Configurable Product', $payload['name']);
        $this->assertNotSame('{}', $payload['variants']);

        $variants = json_decode($payload['variants'], true);
        $this->assertIsArray($variants);
        $this->assertNotEmpty($variants);
        $this->assertGreaterThan(0, (float) $payload['stock']);
    }

    /**
     * Category metadata is resolved from the categories map using the product's
     * assigned category IDs. Uses an in-memory assignment to avoid triggering the
     * catalog_product_save observer during fixture setup.
     *
     * @magentoDataFixture Magento/Catalog/_files/category.php
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     * @magentoConfigFixture base_website pixlee_pixlee/existing_customers/account_settings/active 1
     */
    public function testExportProductIncludesCategoriesFromCategoriesMap(): void
    {
        $objectManager = Bootstrap::getObjectManager();

        $spy = new PixleeServiceSpy();
        /** @var ProductExport $export */
        $export = $objectManager->create(ProductExport::class, ['pixleeService' => $spy]);

        /** @var ProductRepositoryInterface $repository */
        $repository = $objectManager->get(ProductRepositoryInterface::class);
        $product = $repository->get('simple');
        $product->setCategoryIds([333]);

        $categoriesMap = $export->getCategoriesMap();
        $store = $objectManager->get(\Magento\Store\Model\StoreManagerInterface::class)
            ->getWebsite(self::DEFAULT_WEBSITE_ID)
            ->getDefaultStore();

        $export->exportProductToPixlee($product, $categoriesMap, self::DEFAULT_WEBSITE_ID, $store);

        $this->assertCount(1, $spy->createdProducts);

        $extraFields = json_decode($spy->createdProducts[0]['extra_fields'], true);
        $this->assertIsArray($extraFields['categories']);
        $this->assertNotEmpty($extraFields['categories']);
        $this->assertSame(333, $extraFields['categories'][0]['category_id']);
        $this->assertArrayHasKey('category_name', $extraFields['categories'][0]);
        $this->assertArrayHasKey('category_url', $extraFields['categories'][0]);
    }

    /**
     * @param string[] $skus
     */
    private function reindexProductPrice(array $skus = ['simple']): void
    {
        $objectManager = Bootstrap::getObjectManager();
        /** @var ProductRepositoryInterface $repository */
        $repository = $objectManager->get(ProductRepositoryInterface::class);
        $productIds = [];

        foreach ($skus as $sku) {
            $productIds[] = (int) $repository->get($sku)->getId();
        }

        $objectManager->get(PriceIndexerProcessor::class)
            ->getIndexer()
            ->reindexList($productIds);
    }

    /**
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
     * @return string[]
     */
    private function collectSkusFromExportCollection($collection): array
    {
        $skus = [];
        foreach ($collection as $product) {
            $skus[] = (string) $product->getSku();
        }

        return $skus;
    }

    /**
     * @param array<int, array> $payloads
     * @return array|null
     */
    private function findPayloadBySku(array $payloads, string $sku): ?array
    {
        foreach ($payloads as $payload) {
            if (($payload['sku'] ?? null) === $sku) {
                return $payload;
            }
        }

        return null;
    }
}
