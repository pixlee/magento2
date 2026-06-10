<?php
/**
 * Copyright © Emplifi, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Test\Unit\Model\Export;

use Magento\Catalog\Model\Product as ProductModel;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Media\Config as MediaConfig;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Directory\Model\Currency;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;
use Magento\UrlRewrite\Model\UrlFinderInterface;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Pixlee\Pixlee\Api\PixleeServiceInterface;
use Pixlee\Pixlee\Model\Config\Api;
use Pixlee\Pixlee\Model\Export\Product;
use Pixlee\Pixlee\Model\Logger\PixleeLogger;
use Pixlee\Pixlee\Model\Pixlee;

/**
 * Unit tests for the product export model.
 *
 * These tests pin down the expected behavior of each piece of the export
 * pipeline so that, once the export is fixed, we can confirm it produces the
 * payloads Emplifi expects.
 */
class ProductTest extends TestCase
{
    private const WEBSITE_ID = 1;

    /** @var MediaConfig&MockObject */
    private $mediaConfig;
    /** @var Configurable&MockObject */
    private $configurableProduct;
    /** @var ProductResource\CollectionFactory&MockObject */
    private $productCollectionFactory;
    /** @var CategoryCollectionFactory&MockObject */
    private $categoryCollectionFactory;
    /** @var ProductFactory&MockObject */
    private $productFactory;
    /** @var ProductResource&MockObject */
    private $productResource;
    /** @var ProductRepository&MockObject */
    private $productRepository;
    /** @var StockRegistryInterface&MockObject */
    private $stockRegistry;
    /** @var StoreManagerInterface&MockObject */
    private $storeManager;
    /** @var Json */
    private $serializer;
    /** @var UrlFinderInterface&MockObject */
    private $urlFinder;
    /** @var Api&MockObject */
    private $apiConfig;
    /** @var PixleeServiceInterface&MockObject */
    private $pixleeService;
    /** @var PixleeLogger&MockObject */
    private $logger;
    /** @var ProductMetadataInterface&MockObject */
    private $productMetadata;
    /** @var Pixlee&MockObject */
    private $pixlee;

    protected function setUp(): void
    {
        $this->mediaConfig = $this->createMock(MediaConfig::class);
        $this->configurableProduct = $this->createMock(Configurable::class);
        $this->productCollectionFactory = $this->getMockBuilder(ProductResource\CollectionFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $this->categoryCollectionFactory = $this->getMockBuilder(CategoryCollectionFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $this->productFactory = $this->getMockBuilder(ProductFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $this->productResource = $this->createMock(ProductResource::class);
        $this->productRepository = $this->createMock(ProductRepository::class);
        $this->stockRegistry = $this->createMock(StockRegistryInterface::class);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        // Use a real serializer, so payload assertions are concrete.
        $this->serializer = new Json();
        $this->urlFinder = $this->createMock(UrlFinderInterface::class);
        $this->apiConfig = $this->createMock(Api::class);
        $this->pixleeService = $this->createMock(PixleeServiceInterface::class);
        $this->logger = $this->createMock(PixleeLogger::class);
        $this->productMetadata = $this->createMock(ProductMetadataInterface::class);
        $this->pixlee = $this->createMock(Pixlee::class);
    }

    private function createSubject(): Product
    {
        return new Product(
            $this->mediaConfig,
            $this->configurableProduct,
            $this->productCollectionFactory,
            $this->categoryCollectionFactory,
            $this->productFactory,
            $this->productResource,
            $this->productRepository,
            $this->stockRegistry,
            $this->storeManager,
            $this->serializer,
            $this->urlFinder,
            $this->apiConfig,
            $this->pixleeService,
            $this->logger,
            $this->productMetadata,
            $this->pixlee
        );
    }

    // ----------------------------------------------------------------------
    // exportProducts()  (top-level paginated entry point)
    // ----------------------------------------------------------------------

    public function testExportProductsDoesNothingWhenAccountInactive(): void
    {
        $this->apiConfig->method('isActive')
            ->with(ScopeInterface::SCOPE_WEBSITES, self::WEBSITE_ID)
            ->willReturn(false);

        $this->pixleeService->expects($this->never())->method('notifyExportStatus');

        // getProductCollection must not even be reached.
        $this->productCollectionFactory->expects($this->never())->method('create');

        $this->createSubject()->exportProducts(self::WEBSITE_ID);
    }

    public function testExportProductsIteratesEveryPageAndNotifiesStartAndFinish(): void
    {
        $this->apiConfig->method('isActive')->willReturn(true);

        // Two products per page across two pages -> four exports.
        $page = $this->countableIterable([
            $this->fakeChild(1, 'a'),
            $this->fakeChild(2, 'b'),
        ]);
        $collection = $this->paginatedCollection($page, $lastPage = 2, $size = 4);

        $store = $this->createMock(Store::class);
        $website = $this->createMock(Website::class);
        $website->method('getDefaultStore')->willReturn($store);
        $this->storeManager->method('getWebsite')->with(self::WEBSITE_ID)->willReturn($website);

        // Partial mock: real exportProducts(), but stub the heavy collaborators.
        $export = $this->getMockBuilder(Product::class)
            ->setConstructorArgs($this->constructorArgs())
            ->onlyMethods(['getProductCollection', 'getCategoriesMap', 'exportProductToPixlee'])
            ->getMock();
        $export->method('getProductCollection')->with(self::WEBSITE_ID)->willReturn($collection);
        $export->method('getCategoriesMap')->willReturn([]);
        $export->expects($this->exactly(4))->method('exportProductToPixlee');

        $statuses = [];
        $this->pixleeService->method('notifyExportStatus')
            ->willReturnCallback(function ($status, $jobId, $num) use (&$statuses) {
                $statuses[] = [$status, $num];
            });

        $export->exportProducts(self::WEBSITE_ID);

        $this->assertSame(['started', $size], $statuses[0]);
        $this->assertSame(['finished', 4], $statuses[1]);
        $this->assertSame([1, 2], $collection->pagesVisited, 'Every page must be visited exactly once.');
    }

    public function testExportProductsNotifiesStartAndFinishWhenCollectionIsEmpty(): void
    {
        $this->apiConfig->method('isActive')->willReturn(true);

        $collection = $this->paginatedCollection($this->countableIterable([]), $lastPage = 1, $size = 0);

        $store = $this->createMock(Store::class);
        $website = $this->createMock(Website::class);
        $website->method('getDefaultStore')->willReturn($store);
        $this->storeManager->method('getWebsite')->with(self::WEBSITE_ID)->willReturn($website);

        $export = $this->getMockBuilder(Product::class)
            ->setConstructorArgs($this->constructorArgs())
            ->onlyMethods(['getProductCollection', 'getCategoriesMap', 'exportProductToPixlee'])
            ->getMock();
        $export->method('getProductCollection')->willReturn($collection);
        $export->method('getCategoriesMap')->willReturn([]);
        $export->expects($this->never())->method('exportProductToPixlee');

        $statuses = [];
        $this->pixleeService->method('notifyExportStatus')
            ->willReturnCallback(function ($status, $jobId, $num) use (&$statuses) {
                $statuses[] = [$status, $num];
            });

        $export->exportProducts(self::WEBSITE_ID);

        $this->assertSame(['started', 0], $statuses[0]);
        $this->assertSame(['finished', 0], $statuses[1]);
    }

    // ----------------------------------------------------------------------
    // getProductCollection()
    // ----------------------------------------------------------------------

    public function testGetProductCollectionAppliesVisibilityStatusAndWebsiteFilters(): void
    {
        $collection = $this->recordingProductCollection();
        $this->productCollectionFactory->method('create')->willReturn($collection);
        $this->mockWebsiteDefaultStore(1);

        $result = $this->createSubject()->getProductCollection(self::WEBSITE_ID);

        $this->assertSame($collection, $result);
        $this->assertSame(self::WEBSITE_ID, $collection->websiteFilter);

        $byField = [];
        foreach ($collection->filters as [$field, $condition]) {
            $byField[$field] = $condition;
        }

        $this->assertArrayHasKey('visibility', $byField);
        $this->assertSame(
            [Visibility::VISIBILITY_BOTH, Visibility::VISIBILITY_IN_CATALOG],
            $byField['visibility']['in']
        );

        $this->assertArrayHasKey('status', $byField);
        $this->assertSame(Status::STATUS_DISABLED, $byField['status']['neq']);
    }

    /**
     * Regression guard for the export bug where products were skipped because
     * $product->getName() was null.
     *
     * Magento product collections only expose EAV attributes that are
     * explicitly selected. Since the export reads name, image, price, and
     * visibility off each collection item, the collection MUST select them
     * (either via '*' or by name) — otherwise getName() returns null and every
     * product is dropped at the early-return guard in exportProductToPixlee().
     */
    public function testGetProductCollectionSelectsAttributesRequiredByExport(): void
    {
        $collection = $this->recordingProductCollection();
        $this->productCollectionFactory->method('create')->willReturn($collection);
        $this->mockWebsiteDefaultStore(1);

        $this->createSubject()->getProductCollection(self::WEBSITE_ID);

        $this->assertNotEmpty(
            $collection->selectedAttributes,
            'getProductCollection() must call addAttributeToSelect(); otherwise EAV '
            . 'attributes such as "name" are null and products are skipped.'
        );

        // Flatten every addAttributeToSelect() argument into a single set.
        $selected = [];
        foreach ($collection->selectedAttributes as $attribute) {
            foreach ((array) $attribute as $name) {
                $selected[$name] = true;
            }
        }

        if (isset($selected['*'])) {
            // Selecting all attributes covers everything the export needs.
            $this->addToAssertionCount(1);
            return;
        }

        foreach (['name', 'sku', 'image', 'price', 'visibility'] as $required) {
            $this->assertArrayHasKey(
                $required,
                $selected,
                sprintf('Export reads "%s" from collection products; it must be selected.', $required)
            );
        }
    }

    public function testGetProductCollectionSetsStoreScopeAndFinalPrice(): void
    {
        $collection = $this->recordingProductCollection();
        $this->productCollectionFactory->method('create')->willReturn($collection);
        $this->mockWebsiteDefaultStore(1);

        $this->createSubject()->getProductCollection(self::WEBSITE_ID);

        $this->assertSame(
            1,
            $collection->storeId,
            'Collection must be scoped to the website default store so store-view EAV values load.'
        );
        $this->assertTrue(
            $collection->finalPriceAdded,
            'Collection must join final price data so getFinalPrice() is populated.'
        );
    }

    // ----------------------------------------------------------------------
    // getCategoriesMap()
    // ----------------------------------------------------------------------

    public function testGetCategoriesMapBuildsBreadcrumbNamesAndSkipsRootCategories(): void
    {
        // Root (1) -> store root (2) -> Men (3) -> Shirts (4)
        $men = $this->fakeCategory(3, 'Men', '1/2/3', 'http://example.com/men.html');
        $shirts = $this->fakeCategory(4, 'Shirts', '1/2/3/4', 'http://example.com/men/shirts.html');

        $this->categoryCollectionFactory->method('create')
            ->willReturn($this->iterableCollectionWithAttributeSelect([$men, $shirts]));

        $map = $this->createSubject()->getCategoriesMap();

        $this->assertArrayHasKey(3, $map);
        $this->assertArrayHasKey(4, $map);

        // Root ids 0/1/2 are excluded from the breadcrumb and parent_ids.
        $this->assertSame('Men', $map[3]['name']);
        $this->assertSame([3], $map[3]['parent_ids']);

        $this->assertSame('Men > Shirts', $map[4]['name']);
        $this->assertSame([3, 4], $map[4]['parent_ids']);
        $this->assertSame('http://example.com/men/shirts.html', $map[4]['url']);
    }

    // ----------------------------------------------------------------------
    // getCategories()
    // ----------------------------------------------------------------------

    public function testGetCategoriesResolvesParentIdsDedupesAndIgnoresUnknownIds(): void
    {
        $categoriesMap = [
            3 => ['name' => 'Men', 'url' => 'u3', 'parent_ids' => [3]],
            4 => ['name' => 'Men > Shirts', 'url' => 'u4', 'parent_ids' => [3, 4]],
        ];

        // 99 is intentionally absent from the map and must be skipped silently.
        $product = $this->createMock(ProductModel::class);
        $product->method('getCategoryIds')->willReturn([3, 4, 99]);

        $categories = $this->createSubject()->getCategories($product, $categoriesMap);

        $ids = array_column($categories, 'category_id');
        sort($ids);
        $this->assertSame([3, 4], $ids, 'Parent ids must be de-duplicated across categories.');

        $byId = [];
        foreach ($categories as $entry) {
            $byId[$entry['category_id']] = $entry;
        }
        $this->assertSame('Men > Shirts', $byId[4]['category_name']);
        $this->assertSame('u4', $byId[4]['category_url']);
    }

    public function testGetCategoriesReturnsEmptyArrayWhenProductHasNoCategories(): void
    {
        $product = $this->createMock(ProductModel::class);
        $product->method('getCategoryIds')->willReturn([]);

        $this->assertSame([], $this->createSubject()->getCategories($product, []));
    }

    // ----------------------------------------------------------------------
    // getVariantsDict()
    // ----------------------------------------------------------------------

    public function testGetVariantsDictReturnsEmptyObjectForSimpleProduct(): void
    {
        $product = $this->productWithWebsite(self::WEBSITE_ID);
        $this->configurableProduct->method('getUsedProductCollection')
            ->willReturn($this->countableIterable([]));

        $this->assertSame('{}', $this->createSubject()->getVariantsDict($product));
    }

    public function testGetVariantsDictSerializesChildStockAndSku(): void
    {
        $product = $this->productWithWebsite(self::WEBSITE_ID, 10);
        $children = [
            $this->fakeChild(101, 'red-s'),
            $this->fakeChild(102, 'red-m'),
        ];
        $this->configurableProduct->method('getUsedProductCollection')
            ->willReturn($this->countableIterable($children));

        $this->stockRegistry->method('getStockItem')->willReturnMap([
            [101, self::WEBSITE_ID, $this->stockItem(5.0)],
            // Negative quantities must be clamped to 0.
            [102, self::WEBSITE_ID, $this->stockItem(-3.0)],
        ]);

        $json = $this->createSubject()->getVariantsDict($product);
        $decoded = json_decode($json, true);

        $this->assertSame(5, (int) $decoded[101]['variant_stock']);
        $this->assertSame('red-s', $decoded[101]['variant_sku']);
        $this->assertSame(0, (int) $decoded[102]['variant_stock']);
        $this->assertSame('red-m', $decoded[102]['variant_sku']);
    }

    public function testGetVariantsDictUsesNullStockWhenChildHasNoStockItem(): void
    {
        $product = $this->productWithWebsite(self::WEBSITE_ID, 10);
        $children = [$this->fakeChild(101, 'red-s')];
        $this->configurableProduct->method('getUsedProductCollection')
            ->willReturn($this->countableIterable($children));
        $this->stockRegistry->method('getStockItem')->willReturn(null);

        $decoded = json_decode($this->createSubject()->getVariantsDict($product), true);

        $this->assertNull($decoded[101]['variant_stock']);
        $this->assertSame('red-s', $decoded[101]['variant_sku']);
    }

    // ----------------------------------------------------------------------
    // getAggregateStock()
    // ----------------------------------------------------------------------

    public function testGetAggregateStockReturnsQtyForSimpleProduct(): void
    {
        $product = $this->productWithWebsite(self::WEBSITE_ID, 7);
        $this->configurableProduct->method('getUsedProductCollection')
            ->willReturn($this->countableIterable([]));
        $this->stockRegistry->method('getStockItem')->with(7, self::WEBSITE_ID)
            ->willReturn($this->stockItem(42.0));

        $this->assertSame(42.0, $this->createSubject()->getAggregateStock($product));
    }

    public function testGetAggregateStockSumsChildrenAndClampsNegatives(): void
    {
        $product = $this->productWithWebsite(self::WEBSITE_ID, 10);
        $children = [$this->fakeChild(101, 'a'), $this->fakeChild(102, 'b')];
        $this->configurableProduct->method('getUsedProductCollection')
            ->willReturn($this->countableIterable($children));

        $this->stockRegistry->method('getStockItem')->willReturnMap([
            [101, self::WEBSITE_ID, $this->stockItem(8.0)],
            [102, self::WEBSITE_ID, $this->stockItem(-5.0)], // clamped to 0
        ]);

        $this->assertSame(8.0, $this->createSubject()->getAggregateStock($product));
    }

    public function testGetAggregateStockReturnsNullWhenAllChildrenHaveNoStockItem(): void
    {
        $product = $this->productWithWebsite(self::WEBSITE_ID, 10);
        $children = [$this->fakeChild(101, 'a'), $this->fakeChild(102, 'b')];
        $this->configurableProduct->method('getUsedProductCollection')
            ->willReturn($this->countableIterable($children));
        $this->stockRegistry->method('getStockItem')->willReturn(null);

        $this->assertNull($this->createSubject()->getAggregateStock($product));
    }

    // ----------------------------------------------------------------------
    // getExtraFields()
    // ----------------------------------------------------------------------

    public function testGetExtraFieldsSerializesPlatformMetadataAndCategories(): void
    {
        $categoriesMap = [
            3 => ['name' => 'Men', 'url' => 'u3', 'parent_ids' => [3]],
        ];

        $product = $this->createMock(ProductModel::class);
        $product->method('getId')->willReturn(10);
        $product->method('getCategoryIds')->willReturn([3]);

        // getAllPhotos: simple product, no gallery images, no children.
        $loaded = $this->createMock(ProductModel::class);
        $loaded->method('getMediaGalleryImages')->willReturn($this->countableIterable([]));
        $this->productRepository->method('getById')->with(10)->willReturn($loaded);
        $this->configurableProduct->method('getUsedProductCollection')
            ->willReturn($this->countableIterable([]));

        $this->productMetadata->method('getVersion')->willReturn('2.4.8');
        $this->pixlee->method('getExtensionVersion')->willReturn('3.0.1');

        $json = $this->createSubject()->getExtraFields($product, $categoriesMap);
        $decoded = json_decode($json, true);

        $this->assertSame(Pixlee::PLATFORM, $decoded['ecommerce_platform']);
        $this->assertSame('2.4.8', $decoded['ecommerce_platform_version']);
        $this->assertSame('3.0.1', $decoded['version_hash']);
        $this->assertSame([], $decoded['product_photos']);
        $this->assertSame('Men', $decoded['categories'][0]['category_name']);
        $this->assertArrayHasKey('categories_last_updated_at', $decoded);
    }

    // ----------------------------------------------------------------------
    // getAllPhotos()
    // ----------------------------------------------------------------------

    public function testGetAllPhotosCollectsParentAndVariantImagesWithoutDuplicates(): void
    {
        $product = $this->createMock(ProductModel::class);
        $product->method('getId')->willReturn(10);

        $parentLoaded = $this->createMock(ProductModel::class);
        $parentLoaded->method('getMediaGalleryImages')->willReturn($this->countableIterable([
            $this->fakeImage('http://img/a.jpg'),
            $this->fakeImage('http://img/b.jpg'),
        ]));

        $childLoaded = $this->createMock(ProductModel::class);
        $childLoaded->method('getMediaGalleryImages')->willReturn($this->countableIterable([
            // Duplicate of the parent image must be ignored.
            $this->fakeImage('http://img/b.jpg'),
            $this->fakeImage('http://img/c.jpg'),
        ]));

        $this->productRepository->method('getById')->willReturnMap([
            [10, false, null, $parentLoaded],
            [101, false, null, $childLoaded],
        ]);

        $this->configurableProduct->method('getUsedProductCollection')
            ->willReturn($this->countableIterable([$this->fakeChild(101, 'child')]));

        $photos = $this->createSubject()->getAllPhotos($product);

        $this->assertSame(
            ['http://img/a.jpg', 'http://img/b.jpg', 'http://img/c.jpg'],
            $photos
        );
    }

    // ----------------------------------------------------------------------
    // getProductUrl() / getAbsoluteUrl()  (protected — exercised via reflection)
    // ----------------------------------------------------------------------

    public function testGetProductUrlUsesRewriteRequestPathWhenAvailable(): void
    {
        $store = $this->createMock(Store::class);
        $store->method('getId')->willReturn(1);
        $store->method('getBaseUrl')->willReturn('http://example.com/');

        $product = $this->createMock(ProductModel::class);
        $product->method('getId')->willReturn(10);

        $rewrite = $this->createMock(UrlRewrite::class);
        $rewrite->method('getRequestPath')->willReturn('men/shirts/red.html');
        $this->urlFinder->method('findOneByData')->willReturn($rewrite);

        $url = $this->invokeProtected('getProductUrl', [$product, $store]);

        $this->assertSame('http://example.com/men/shirts/red.html', $url);
    }

    public function testGetProductUrlFallsBackToProductUrlWhenNoRewrite(): void
    {
        $store = $this->createMock(Store::class);
        $store->method('getId')->willReturn(1);

        $product = $this->createMock(ProductModel::class);
        $product->method('getId')->willReturn(10);
        $product->method('getProductUrl')->willReturn('http://example.com/catalog/product/view/id/10');

        $this->urlFinder->method('findOneByData')->willReturn(null);

        $url = $this->invokeProtected('getProductUrl', [$product, $store]);

        $this->assertSame('http://example.com/catalog/product/view/id/10', $url);
    }

    public function testGetAbsoluteUrlNormalisesSlashes(): void
    {
        $store = $this->createMock(Store::class);
        $store->method('getBaseUrl')->willReturn('http://example.com/');

        $this->assertSame(
            'http://example.com/men/shirts.html',
            $this->invokeProtected('getAbsoluteUrl', ['/men/shirts.html', $store])
        );
    }

    // ----------------------------------------------------------------------
    // getRegionalInformation()
    // ----------------------------------------------------------------------

    public function testGetRegionalInformationReturnsEntryPerActiveStore(): void
    {
        $product = $this->productWithWebsite(self::WEBSITE_ID, 10);

        $website = $this->createMock(Website::class);
        $website->method('getStoreIds')->willReturn([1, 2]);
        $this->storeManager->method('getWebsite')->with(self::WEBSITE_ID)->willReturn($website);

        // Store 1 active, store 2 inactive -> only one regional entry.
        $this->apiConfig->method('isActive')->willReturnMap([
            [ScopeInterface::SCOPE_STORES, 1, true],
            [ScopeInterface::SCOPE_STORES, 2, false],
        ]);

        $storeProduct = $this->createMock(ProductModel::class);
        $storeProduct->method('getName')->willReturn('Localised Name');
        $storeProduct->method('getProductUrl')->willReturn('http://eu.example.com/p.html');
        $storeProduct->method('getFinalPrice')->willReturn(50.0);
        $storeProduct->method('setStoreId')->willReturnSelf();
        $this->productFactory->method('create')->willReturn($storeProduct);
        $this->productResource->method('load')->willReturnSelf();

        $eurCurrency = $this->createMock(Currency::class);
        $eurCurrency->method('getCode')->willReturn('EUR');
        $baseCurrency = $this->createMock(Currency::class);
        $baseCurrency->method('convert')->with(50.0, $eurCurrency)->willReturn(45.0);

        $store = $this->createMock(Store::class);
        $store->method('getDefaultCurrency')->willReturn($eurCurrency);
        $store->method('getBaseCurrency')->willReturn($baseCurrency);
        $store->method('getCode')->willReturn('eu');
        $this->storeManager->method('getStore')->with(1)->willReturn($store);

        // getAggregateStock for the (simple) product.
        $this->configurableProduct->method('getUsedProductCollection')
            ->willReturn($this->countableIterable([]));
        $this->stockRegistry->method('getStockItem')->willReturn($this->stockItem(12.0));

        $result = $this->createSubject()->getRegionalInformation(self::WEBSITE_ID, $product);

        $this->assertCount(1, $result);
        $this->assertSame('Localised Name', $result[0]['name']);
        $this->assertSame(45.0, $result[0]['price']);
        $this->assertSame('EUR', $result[0]['currency']);
        $this->assertSame('eu', $result[0]['region_code']);
        $this->assertSame(12.0, $result[0]['stock']);
    }

    // ----------------------------------------------------------------------
    // exportProductToPixlee()
    // ----------------------------------------------------------------------

    public function testExportProductSkipsWhenApiInactive(): void
    {
        $this->apiConfig->method('isActive')
            ->with(ScopeInterface::SCOPE_WEBSITES, self::WEBSITE_ID)
            ->willReturn(false);

        $this->pixleeService->expects($this->never())->method('createProduct');

        $product = $this->createMock(ProductModel::class);
        $store = $this->createMock(Store::class);

        $this->createSubject()->exportProductToPixlee($product, [], self::WEBSITE_ID, $store);
    }

    public function testExportProductSkipsProductsNotVisibleInCatalog(): void
    {
        $this->apiConfig->method('isActive')->willReturn(true);

        $this->pixleeService->expects($this->never())->method('createProduct');

        $product = $this->createMock(ProductModel::class);
        $product->method('getVisibility')->willReturn(Visibility::VISIBILITY_NOT_VISIBLE);
        $product->method('getId')->willReturn(10);

        $store = $this->createMock(Store::class);

        $this->createSubject()->exportProductToPixlee($product, [], self::WEBSITE_ID, $store);
    }

    public function testExportProductBuildsExpectedPayloadAndCallsCreateProduct(): void
    {
        // API active for the website, but inactive per-store so regional_info is empty.
        $this->mockApiActiveForWebsiteScope();

        $product = $this->productWithWebsite(self::WEBSITE_ID, 10);
        $product->method('getVisibility')->willReturn(Visibility::VISIBILITY_BOTH);
        $product->method('getName')->willReturn('Test Product');
        $product->method('getSku')->willReturn('test-sku');
        $product->method('getImage')->willReturn('/t/e/test.jpg');
        $product->method('getFinalPrice')->willReturn(19.99);
        $product->method('getProductUrl')->willReturn('http://example.com/test.html');
        $product->method('getCategoryIds')->willReturn([]);

        $this->mediaConfig->method('getMediaUrl')->with('/t/e/test.jpg')
            ->willReturn('http://example.com/media/catalog/product/t/e/test.jpg');

        // Store passed in to the export call.
        $currency = $this->createMock(Currency::class);
        $currency->method('getCode')->willReturn('USD');
        $store = $this->createMock(Store::class);
        $store->method('getId')->willReturn(1);
        $store->method('getCurrentCurrency')->willReturn($currency);

        // No URL rewrite -> falls back to getProductUrl().
        $this->urlFinder->method('findOneByData')->willReturn(null);

        // Regional info iterates the website's stores.
        $website = $this->createMock(Website::class);
        $website->method('getStoreIds')->willReturn([1]);
        $this->storeManager->method('getWebsite')->willReturn($website);

        // Stock + variants: simple product.
        $this->configurableProduct->method('getUsedProductCollection')
            ->willReturn($this->countableIterable([]));
        $this->stockRegistry->method('getStockItem')->willReturn($this->stockItem(15.0));

        // Extra fields dependencies.
        $loaded = $this->createMock(ProductModel::class);
        $loaded->method('getMediaGalleryImages')->willReturn($this->countableIterable([]));
        $this->productRepository->method('getById')->willReturn($loaded);
        $this->productMetadata->method('getVersion')->willReturn('2.4.8');
        $this->pixlee->method('getExtensionVersion')->willReturn('3.0.1');

        $captured = null;
        $this->pixleeService->expects($this->once())
            ->method('createProduct')
            ->willReturnCallback(function (array $info) use (&$captured) {
                $captured = $info;
                return true;
            });

        $this->createSubject()->exportProductToPixlee($product, [], self::WEBSITE_ID, $store);

        $this->assertIsArray($captured);
        $this->assertSame('Test Product', $captured['name']);
        $this->assertSame('test-sku', $captured['sku']);
        $this->assertSame(10, $captured['product_id']);
        $this->assertSame('http://example.com/test.html', $captured['product_url']);
        $this->assertSame(
            'http://example.com/media/catalog/product/t/e/test.jpg',
            $captured['product_image']
        );
        $this->assertSame('USD', $captured['currency_code']);
        $this->assertSame(19.99, $captured['price']);
        $this->assertSame(15.0, $captured['stock']);
        $this->assertSame('{}', $captured['variants']);
        $this->assertSame([], $captured['regional_info']);
        $this->assertIsString($captured['extra_fields']);
    }

    /**
     * The exported payload must carry every expected field, fully populated.
     *
     * This is the assertion missing before: a product exported with a
     * null name, empty SKU, or missing price would previously have slipped
     * through. Here we require every key to be present and non-null, and the
     * human-facing identity fields to be non-empty.
     */
    public function testExportedPayloadContainsEveryExpectedFieldPopulated(): void
    {
        $this->mockApiActiveForWebsiteScope();

        $product = $this->productWithWebsite(self::WEBSITE_ID, 10);
        $product->method('getVisibility')->willReturn(Visibility::VISIBILITY_BOTH);
        $product->method('getName')->willReturn('Test Product');
        $product->method('getSku')->willReturn('test-sku');
        $product->method('getImage')->willReturn('/t/e/test.jpg');
        $product->method('getFinalPrice')->willReturn(19.99);
        $product->method('getProductUrl')->willReturn('http://example.com/test.html');
        $product->method('getCategoryIds')->willReturn([]);

        $this->mediaConfig->method('getMediaUrl')
            ->willReturn('http://example.com/media/catalog/product/t/e/test.jpg');

        $currency = $this->createMock(Currency::class);
        $currency->method('getCode')->willReturn('USD');
        $store = $this->createMock(Store::class);
        $store->method('getId')->willReturn(1);
        $store->method('getCurrentCurrency')->willReturn($currency);

        $this->urlFinder->method('findOneByData')->willReturn(null);
        $website = $this->createMock(Website::class);
        $website->method('getStoreIds')->willReturn([]);
        $this->storeManager->method('getWebsite')->willReturn($website);
        $this->configurableProduct->method('getUsedProductCollection')
            ->willReturn($this->countableIterable([]));
        $this->stockRegistry->method('getStockItem')->willReturn($this->stockItem(15.0));
        $loaded = $this->createMock(ProductModel::class);
        $loaded->method('getMediaGalleryImages')->willReturn($this->countableIterable([]));
        $this->productRepository->method('getById')->willReturn($loaded);
        $this->productMetadata->method('getVersion')->willReturn('2.4.8');
        $this->pixlee->method('getExtensionVersion')->willReturn('3.0.1');

        $captured = null;
        $this->pixleeService->expects($this->once())
            ->method('createProduct')
            ->willReturnCallback(function (array $info) use (&$captured) {
                $captured = $info;
                return true;
            });

        $this->createSubject()->exportProductToPixlee($product, [], self::WEBSITE_ID, $store);

        $this->assertIsArray($captured, 'Product was not exported: createProduct() was never called.');

        // Every key the Emplifi product payload is expected to carry.
        $expectedKeys = [
            'name',
            'sku',
            'product_url',
            'product_image',
            'product_id',
            'currency_code',
            'price',
            'regional_info',
            'stock',
            'variants',
            'extra_fields',
        ];
        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $captured, "Payload is missing the '{$key}' field.");
            $this->assertNotNull($captured[$key], "Payload field '{$key}' must not be null.");
        }

        // Identity fields must be non-empty strings for a usable product record.
        foreach (['name', 'sku', 'product_url', 'currency_code'] as $key) {
            $this->assertIsString($captured[$key]);
            $this->assertNotSame('', $captured[$key], "Payload field '{$key}' must not be empty.");
        }
        $this->assertIsString($captured['product_image']);
        $this->assertNotSame('', $captured['product_image'], 'A product with an image must export a URL.');

        $this->assertGreaterThan(0, $captured['product_id']);
        $this->assertIsNumeric($captured['price']);

        // extra_fields must be valid JSON carrying the platform metadata.
        $extra = json_decode($captured['extra_fields'], true);
        $this->assertIsArray($extra);
        $this->assertSame(Pixlee::PLATFORM, $extra['ecommerce_platform']);
    }

    /**
     * Characterizes the production failure mode: a product whose name is null
     * (e.g., loaded from a collection that did not select the "name" attribute)
     * is silently dropped here without being exported.
     *
     * The real fix lives in getProductCollection() — see
     * {@see testGetProductCollectionSelectsAttributesRequiredByExport()}. This
     * test documents why a null name is fatal to the export.
     */
    public function testExportProductReturnsEarlyWhenProductNameIsNull(): void
    {
        $this->apiConfig->method('isActive')->willReturn(true);

        $product = $this->createMock(ProductModel::class);
        $product->method('getId')->willReturn(10);
        $product->method('getVisibility')->willReturn(Visibility::VISIBILITY_BOTH);
        $product->method('getName')->willReturn(null);

        $store = $this->createMock(Store::class);

        $this->pixleeService->expects($this->never())
            ->method('createProduct');

        $this->createSubject()->exportProductToPixlee($product, [], self::WEBSITE_ID, $store);
    }

    public function testExportProductStillExportsWhenProductNameIsEmptyString(): void
    {
        $this->mockApiActiveForWebsiteScope();

        $product = $this->productWithWebsite(self::WEBSITE_ID, 10);
        $product->method('getVisibility')->willReturn(Visibility::VISIBILITY_BOTH);
        $product->method('getName')->willReturn('');
        $product->method('getSku')->willReturn('empty-name');
        $product->method('getImage')->willReturn(null);
        $product->method('getFinalPrice')->willReturn(1.0);
        $product->method('getProductUrl')->willReturn('http://example.com/empty.html');
        $product->method('getCategoryIds')->willReturn([]);

        $currency = $this->createMock(Currency::class);
        $currency->method('getCode')->willReturn('USD');
        $store = $this->createMock(Store::class);
        $store->method('getId')->willReturn(1);
        $store->method('getCurrentCurrency')->willReturn($currency);

        $this->urlFinder->method('findOneByData')->willReturn(null);
        $website = $this->createMock(Website::class);
        $website->method('getStoreIds')->willReturn([]);
        $this->storeManager->method('getWebsite')->willReturn($website);
        $this->configurableProduct->method('getUsedProductCollection')
            ->willReturn($this->countableIterable([]));
        $this->stockRegistry->method('getStockItem')->willReturn($this->stockItem(0.0));
        $loaded = $this->createMock(ProductModel::class);
        $loaded->method('getMediaGalleryImages')->willReturn($this->countableIterable([]));
        $this->productRepository->method('getById')->willReturn($loaded);
        $this->productMetadata->method('getVersion')->willReturn('2.4.8');
        $this->pixlee->method('getExtensionVersion')->willReturn('3.0.1');

        $captured = null;
        $this->pixleeService->expects($this->once())
            ->method('createProduct')
            ->willReturnCallback(function (array $info) use (&$captured) {
                $captured = $info;
                return true;
            });

        $this->createSubject()->exportProductToPixlee($product, [], self::WEBSITE_ID, $store);

        $this->assertSame('', $captured['name']);
    }

    public function testExportProductExportsCatalogOnlyVisibleProducts(): void
    {
        $this->mockApiActiveForWebsiteScope();

        $product = $this->productWithWebsite(self::WEBSITE_ID, 10);
        $product->method('getVisibility')->willReturn(Visibility::VISIBILITY_IN_CATALOG);
        $product->method('getName')->willReturn('Catalog Only Product');
        $product->method('getSku')->willReturn('catalog-only');
        $product->method('getImage')->willReturn(null);
        $product->method('getFinalPrice')->willReturn(12.0);
        $product->method('getProductUrl')->willReturn('http://example.com/catalog-only.html');
        $product->method('getCategoryIds')->willReturn([]);

        $currency = $this->createMock(Currency::class);
        $currency->method('getCode')->willReturn('USD');
        $store = $this->createMock(Store::class);
        $store->method('getId')->willReturn(1);
        $store->method('getCurrentCurrency')->willReturn($currency);

        $this->urlFinder->method('findOneByData')->willReturn(null);
        $website = $this->createMock(Website::class);
        $website->method('getStoreIds')->willReturn([]);
        $this->storeManager->method('getWebsite')->willReturn($website);
        $this->configurableProduct->method('getUsedProductCollection')
            ->willReturn($this->countableIterable([]));
        $this->stockRegistry->method('getStockItem')->willReturn($this->stockItem(3.0));
        $loaded = $this->createMock(ProductModel::class);
        $loaded->method('getMediaGalleryImages')->willReturn($this->countableIterable([]));
        $this->productRepository->method('getById')->willReturn($loaded);
        $this->productMetadata->method('getVersion')->willReturn('2.4.8');
        $this->pixlee->method('getExtensionVersion')->willReturn('3.0.1');

        $captured = null;
        $this->pixleeService->expects($this->once())
            ->method('createProduct')
            ->willReturnCallback(function (array $info) use (&$captured) {
                $captured = $info;
                return true;
            });

        $this->createSubject()->exportProductToPixlee($product, [], self::WEBSITE_ID, $store);

        $this->assertSame('Catalog Only Product', $captured['name']);
    }

    public function testExportConfigurableProductBuildsVariantsPayload(): void
    {
        $this->mockApiActiveForWebsiteScope();

        $product = $this->productWithWebsite(self::WEBSITE_ID, 1);
        $product->method('getVisibility')->willReturn(Visibility::VISIBILITY_BOTH);
        $product->method('getName')->willReturn('Configurable Product');
        $product->method('getSku')->willReturn('configurable');
        $product->method('getImage')->willReturn(null);
        $product->method('getFinalPrice')->willReturn(0.0);
        $product->method('getProductUrl')->willReturn('http://example.com/configurable.html');
        $product->method('getCategoryIds')->willReturn([]);

        $children = [$this->fakeChild(10, 'simple_10'), $this->fakeChild(20, 'simple_20')];
        $this->configurableProduct->method('getUsedProductCollection')
            ->willReturn($this->countableIterable($children));
        $this->stockRegistry->method('getStockItem')->willReturnMap([
            [10, self::WEBSITE_ID, $this->stockItem(100.0)],
            [20, self::WEBSITE_ID, $this->stockItem(50.0)],
        ]);

        $currency = $this->createMock(Currency::class);
        $currency->method('getCode')->willReturn('USD');
        $store = $this->createMock(Store::class);
        $store->method('getId')->willReturn(1);
        $store->method('getCurrentCurrency')->willReturn($currency);

        $this->urlFinder->method('findOneByData')->willReturn(null);
        $website = $this->createMock(Website::class);
        $website->method('getStoreIds')->willReturn([]);
        $this->storeManager->method('getWebsite')->willReturn($website);
        $loaded = $this->createMock(ProductModel::class);
        $loaded->method('getMediaGalleryImages')->willReturn($this->countableIterable([]));
        $this->productRepository->method('getById')->willReturn($loaded);
        $this->productMetadata->method('getVersion')->willReturn('2.4.8');
        $this->pixlee->method('getExtensionVersion')->willReturn('3.0.1');

        $captured = null;
        $this->pixleeService->expects($this->once())
            ->method('createProduct')
            ->willReturnCallback(function (array $info) use (&$captured) {
                $captured = $info;
                return true;
            });

        $this->createSubject()->exportProductToPixlee($product, [], self::WEBSITE_ID, $store);

        $this->assertSame('Configurable Product', $captured['name']);
        $this->assertSame(0.0, $captured['price']);
        $this->assertSame(150.0, $captured['stock']);
        $variants = json_decode($captured['variants'], true);
        $this->assertSame(100, (int) $variants[10]['variant_stock']);
        $this->assertSame('simple_10', $variants[10]['variant_sku']);
        $this->assertSame(50, (int) $variants[20]['variant_stock']);
    }

    public function testExportProductSendsEmptyImageWhenProductHasNoImage(): void
    {
        $this->mockApiActiveForWebsiteScope();

        $product = $this->productWithWebsite(self::WEBSITE_ID, 10);
        $product->method('getVisibility')->willReturn(Visibility::VISIBILITY_BOTH);
        $product->method('getName')->willReturn('No Image Product');
        $product->method('getSku')->willReturn('no-image');
        $product->method('getImage')->willReturn(null);
        $product->method('getFinalPrice')->willReturn(5.0);
        $product->method('getProductUrl')->willReturn('http://example.com/no-image.html');
        $product->method('getCategoryIds')->willReturn([]);

        $this->mediaConfig->expects($this->never())->method('getMediaUrl');

        $currency = $this->createMock(Currency::class);
        $currency->method('getCode')->willReturn('USD');
        $store = $this->createMock(Store::class);
        $store->method('getId')->willReturn(1);
        $store->method('getCurrentCurrency')->willReturn($currency);

        $this->urlFinder->method('findOneByData')->willReturn(null);
        $website = $this->createMock(Website::class);
        $website->method('getStoreIds')->willReturn([]);
        $this->storeManager->method('getWebsite')->willReturn($website);
        $this->configurableProduct->method('getUsedProductCollection')
            ->willReturn($this->countableIterable([]));
        $this->stockRegistry->method('getStockItem')->willReturn($this->stockItem(0.0));
        $loaded = $this->createMock(ProductModel::class);
        $loaded->method('getMediaGalleryImages')->willReturn($this->countableIterable([]));
        $this->productRepository->method('getById')->willReturn($loaded);
        $this->productMetadata->method('getVersion')->willReturn('2.4.8');
        $this->pixlee->method('getExtensionVersion')->willReturn('3.0.1');

        $captured = null;
        $this->pixleeService->method('createProduct')
            ->willReturnCallback(function (array $info) use (&$captured) {
                $captured = $info;
                return true;
            });

        $this->createSubject()->exportProductToPixlee($product, [], self::WEBSITE_ID, $store);

        $this->assertSame('', $captured['product_image']);
    }

    // ----------------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------------

    /**
     * Constructor arguments in declaration order, for building partial mocks.
     *
     * @return array<int, mixed>
     */
    private function constructorArgs(): array
    {
        return [
            $this->mediaConfig,
            $this->configurableProduct,
            $this->productCollectionFactory,
            $this->categoryCollectionFactory,
            $this->productFactory,
            $this->productResource,
            $this->productRepository,
            $this->stockRegistry,
            $this->storeManager,
            $this->serializer,
            $this->urlFinder,
            $this->apiConfig,
            $this->pixleeService,
            $this->logger,
            $this->productMetadata,
            $this->pixlee,
        ];
    }

    /**
     * Stub apiConfig->isActive() to return true only for website scope.
     */
    private function mockApiActiveForWebsiteScope(): void
    {
        $this->apiConfig->method('isActive')->willReturnCallback(
            static function ($scope) {
                return $scope === ScopeInterface::SCOPE_WEBSITES;
            }
        );
    }

    /**
     * A product-collection stand-in that records the filters, website filter,
     * and attribute selections applied by getProductCollection().
     */
    private function mockWebsiteDefaultStore(int $storeId): void
    {
        $store = $this->createMock(Store::class);
        $store->method('getId')->willReturn($storeId);
        $website = $this->createMock(Website::class);
        $website->method('getDefaultStore')->willReturn($store);
        $this->storeManager->method('getWebsite')->with(self::WEBSITE_ID)->willReturn($website);
    }

    /**
     * @return object
     */
    private function recordingProductCollection()
    {
        return new class() {
            /** @var array<int, array{0:string,1:mixed}> */
            public $filters = [];
            /** @var array<int, mixed> */
            public $selectedAttributes = [];
            public $websiteFilter = null;
            public $storeId = null;
            /** @var bool */
            public $finalPriceAdded = false;

            public function addFieldToFilter($field, $condition = null)
            {
                $this->filters[] = [$field, $condition];
                return $this;
            }

            public function addAttributeToSelect($attribute, $joinType = false)
            {
                $this->selectedAttributes[] = $attribute;
                return $this;
            }

            public function addWebsiteFilter($websiteId)
            {
                $this->websiteFilter = $websiteId;
                return $this;
            }

            public function setStoreId($storeId)
            {
                $this->storeId = $storeId;
                return $this;
            }

            public function addFinalPrice()
            {
                $this->finalPriceAdded = true;
                return $this;
            }
        };
    }

    /**
     * A collection stand-in for the exportProducts() pagination loop. Yields the
     * same page contents for each requested page and records which pages were set.
     */
    /**
     * @param object $page
     * @return object
     */
    private function paginatedCollection($page, int $lastPage, int $size)
    {
        return new class($page, $lastPage, $size) implements \IteratorAggregate {
            /** @var array<int, int> */
            public $pagesVisited = [];

            /** @var object */
            private $page;

            /** @var int */
            private $lastPage;

            /** @var int */
            private $size;

            public function __construct($page, $lastPage, $size)
            {
                $this->page = $page;
                $this->lastPage = $lastPage;
                $this->size = $size;
            }

            public function setPageSize($size)
            {
                return $this;
            }

            public function getLastPageNumber()
            {
                return $this->lastPage;
            }

            public function getSize()
            {
                return $this->size;
            }

            public function clear()
            {
                return $this;
            }

            public function setCurPage($page)
            {
                $this->pagesVisited[] = $page;
                return $this;
            }

            public function getIterator(): \Iterator
            {
                return new \ArrayIterator(iterator_to_array($this->page));
            }
        };
    }

    /**
     * @param array<int, mixed> $args
     */
    private function invokeProtected(string $method, array $args)
    {
        $subject = $this->createSubject();
        $ref = new \ReflectionMethod(Product::class, $method);

        return $ref->invokeArgs($subject, $args);
    }

    /**
     * Build a product mock whose getStore()->getWebsiteId() resolves to $websiteId.
     *
     * @return ProductModel&MockObject
     */
    private function productWithWebsite(int $websiteId, ?int $productId = null)
    {
        $store = $this->createMock(Store::class);
        $store->method('getWebsiteId')->willReturn($websiteId);

        $product = $this->createMock(ProductModel::class);
        $product->method('getStore')->willReturn($store);
        if ($productId !== null) {
            $product->method('getId')->willReturn($productId);
        }

        return $product;
    }

    /**
     * @return StockItemInterface&MockObject
     */
    private function stockItem(float $qty)
    {
        $item = $this->createMock(StockItemInterface::class);
        $item->method('getQty')->willReturn($qty);

        return $item;
    }

    /**
     * @return object
     */
    private function fakeChild(int $id, string $sku)
    {
        return new class($id, $sku) {
            /** @var int */
            private $id;

            /** @var string */
            private $sku;

            public function __construct($id, $sku)
            {
                $this->id = $id;
                $this->sku = $sku;
            }

            public function getId(): int
            {
                return $this->id;
            }

            public function getSku(): string
            {
                return $this->sku;
            }
        };
    }

    /**
     * @return object
     */
    private function fakeImage(string $url)
    {
        return new class($url) {
            /** @var string */
            private $url;

            public function __construct($url)
            {
                $this->url = $url;
            }

            public function getUrl(): string
            {
                return $this->url;
            }
        };
    }

    /**
     * @return object
     */
    private function fakeCategory(int $id, string $name, string $path, string $url)
    {
        return new class($id, $name, $path, $url) {
            /** @var int */
            private $id;

            /** @var string */
            private $name;

            /** @var string */
            private $path;

            /** @var string */
            private $url;

            public function __construct($id, $name, $path, $url)
            {
                $this->id = $id;
                $this->name = $name;
                $this->path = $path;
                $this->url = $url;
            }

            public function getId(): int
            {
                return $this->id;
            }

            public function getName(): string
            {
                return $this->name;
            }

            public function getPath(): string
            {
                return $this->path;
            }

            public function getUrl($self = null): string
            {
                return $this->url;
            }
        };
    }

    /**
     * A countable, iterable stand-in for a Magento collection.
     *
     * @param array<int, mixed> $items
     */
    /**
     * @return object
     */
    private function countableIterable(array $items)
    {
        return new class($items) implements \IteratorAggregate, \Countable {
            /** @var array<int, mixed> */
            private $items;

            public function __construct($items)
            {
                $this->items = $items;
            }

            public function getIterator(): \Iterator
            {
                return new \ArrayIterator($this->items);
            }

            public function count(): int
            {
                return count($this->items);
            }
        };
    }

    /**
     * Category collection stand-in that also accepts addAttributeToSelect().
     *
     * @param array<int, mixed> $items
     */
    /**
     * @return object
     */
    private function iterableCollectionWithAttributeSelect(array $items)
    {
        return new class($items) implements \IteratorAggregate {
            /** @var array<int, mixed> */
            private $items;

            public function __construct($items)
            {
                $this->items = $items;
            }

            public function addAttributeToSelect($attribute)
            {
                return $this;
            }

            public function getIterator(): \Iterator
            {
                return new \ArrayIterator($this->items);
            }
        };
    }
}
