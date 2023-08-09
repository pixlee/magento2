<?php
/**
 * Copyright © Pixlee TurnTo, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Model\Export;

use Magento\Catalog\Model\Product\Media\Config;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\CatalogUrlRewrite\Model\ProductUrlRewriteGenerator;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\UrlRewrite\Model\UrlFinderInterface;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;
use Pixlee\Pixlee\Model\Logger\PixleeLogger;
use Pixlee\Pixlee\Model\Config\Api;
use Pixlee\Pixlee\Api\PixleeServiceInterface;
use Pixlee\Pixlee\Model\Pixlee;

class Product
{
    /**
     * @var PixleeServiceInterface
     */
    protected $pixleeService;
    /**
     * @var Api
     */
    protected $apiConfig;
    /**
     * @var ProductResource\CollectionFactory
     */
    protected $productCollection;
    /**
     * @var PixleeLogger
     */
    protected $logger;
    /**
     * @var Config
     */
    protected $mediaConfig;
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var CollectionFactory
     */
    protected $categoryCollection;
    /**
     * @var ProductFactory
     */
    protected $productFactory;
    /**
     * @var StockRegistryInterface
     */
    protected $stockRegistry;
    /**
     * @var Configurable
     */
    protected $configurableProduct;
    /**
     * @var ProductRepository
     */
    protected $productRepository;
    /**
     * @var UrlFinderInterface
     */
    protected $urlFinder;
    /**
     * @var ProductResource
     */
    protected $productResource;
    /**
     * @var SerializerInterface
     */
    protected $serializer;
    /**
     * @var ProductMetadataInterface
     */
    protected $productMetadata;
    /**
     * @var Pixlee
     */
    protected $pixlee;

    /**
     * @param Config $mediaConfig
     * @param Configurable $configurableProduct
     * @param ProductResource\CollectionFactory $productCollection
     * @param CollectionFactory $categoryCollection
     * @param ProductFactory $productFactory
     * @param ProductResource $productResource
     * @param ProductRepository $productRepository
     * @param StockRegistryInterface $stockRegistry
     * @param StoreManagerInterface $storeManager
     * @param SerializerInterface $serializer
     * @param UrlFinderInterface $urlFinder
     * @param Api $apiConfig
     * @param PixleeServiceInterface $pixleeService
     * @param PixleeLogger $logger
     * @param ProductMetadataInterface $productMetadata
     * @param Pixlee $pixlee
     */
    public function __construct(
        Config $mediaConfig,
        Configurable $configurableProduct,
        ProductResource\CollectionFactory $productCollection,
        CollectionFactory $categoryCollection,
        ProductFactory $productFactory,
        ProductResource $productResource,
        ProductRepository $productRepository,
        StockRegistryInterface $stockRegistry,
        StoreManagerInterface $storeManager,
        SerializerInterface $serializer,
        UrlFinderInterface $urlFinder,
        Api $apiConfig,
        PixleeServiceInterface $pixleeService,
        PixleeLogger $logger,
        ProductMetadataInterface $productMetadata,
        Pixlee $pixlee
    ) {
        $this->mediaConfig = $mediaConfig;
        $this->configurableProduct = $configurableProduct;
        $this->productCollection = $productCollection;
        $this->categoryCollection = $categoryCollection;
        $this->productFactory = $productFactory;
        $this->productResource = $productResource;
        $this->productRepository = $productRepository;
        $this->stockRegistry = $stockRegistry;
        $this->storeManager = $storeManager;
        $this->serializer = $serializer;
        $this->urlFinder = $urlFinder;
        $this->apiConfig = $apiConfig;
        $this->pixleeService = $pixleeService;
        $this->logger = $logger;
        $this->productMetadata = $productMetadata;
        $this->pixlee = $pixlee;
    }

    /**
     * @param string|int $websiteId
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function exportProducts($websiteId)
    {
        if ($this->apiConfig->isActive($websiteId)) {
            // Pagination variables
            $num_products = $this->getTotalProductsCount($websiteId);
            $counter = 0;
            $limit = 100;
            $offset = 0;
            $job_id = uniqid();
            $this->pixleeService->notifyExportStatus('started', $job_id, $num_products, $websiteId);
            $categoriesMap = $this->getCategoriesMap();

            while ($offset < $num_products) {
                $products = $this->getPaginatedProducts($limit, $offset, $websiteId);
                $offset = $offset + $limit;

                foreach ($products as $product) {
                    $counter++;
                    $this->exportProductToPixlee($product, $categoriesMap, $websiteId);
                }
            }

            $this->pixleeService->notifyExportStatus('finished', $job_id, $counter, $websiteId);
        }
    }

    /**
     * @param $websiteId
     * @return int
     */
    public function getTotalProductsCount($websiteId)
    {
        $collection = $this->productCollection->create();
        $collection->addFieldToFilter(
            'visibility',
            [
                'in' =>
                    [
                        Visibility::VISIBILITY_BOTH,
                        Visibility::VISIBILITY_IN_CATALOG
                    ]
            ]
        );
        $collection->addFieldToFilter('status', ['neq' => 2]);
        $collection->addWebsiteFilter($websiteId);
        return $collection->getSize();
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    public function getCategoriesMap()
    {
        $categories = $this->categoryCollection->create();
        $categories->addAttributeToSelect('*');

        $helper = [];
        foreach ($categories as $category) {
            $helper[$category->getId()] = $category->getName();
        }

        $allCategories = [];
        foreach ($categories as $cat) {
            $path = $cat->getPath();
            $parents = explode('/', $path);
            $fullName = '';

            $realParentIds = [];

            foreach ($parents as $parent) {
                if ((int) $parent != 0 && (int) $parent != 1 && (int) $parent != 2) {
                    $name = $helper[(int) $parent];
                    $fullName = $fullName . $name . ' > ';
                    $realParentIds[] = (int)$parent;
                }
            }

            $categoryBody = [
                'name' => substr($fullName, 0, -3),
                'url' => $cat->getUrl($cat),
                'parent_ids' => $realParentIds
            ];

            $allCategories[$cat->getId()] = $categoryBody;
        }

        // Format
        // Hashmap where keys are category_ids and values are a hashmp with name and url keys
        return $allCategories;
    }

    /**
     * @param $limit
     * @param $offset
     * @param $websiteId
     * @return ProductResource\Collection
     */
    public function getPaginatedProducts($limit, $offset, $websiteId)
    {
        $products = $this->productCollection->create();
        $products->addFieldToFilter(
            'visibility',
            [
                'in' =>
                    [
                        Visibility::VISIBILITY_BOTH,
                        Visibility::VISIBILITY_IN_CATALOG
                    ]
            ]
        );
        $products->addFieldToFilter('status', ['neq' => 2]);
        $products->addWebsiteFilter($websiteId);
        $products->getSelect()->limit($limit, $offset);
        $products->addAttributeToSelect('*');

        return $products;
    }

    /**
     * @param $product
     * @param $categoriesMap
     * @param $websiteId
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function exportProductToPixlee($product, $categoriesMap, $websiteId)
    {
        if (!$this->apiConfig->isActive($websiteId)) {
            return;
        }
        // NOTE: 2016-03-21 - JUST noticed, that we were originally checking for getVisibility()
        // later on in the code, but since now I need $product to be reasonable in order to
        if ((int)$product->getVisibility() === Visibility::VISIBILITY_NOT_VISIBLE) {
            $this->logger->addInfo("*** Product ID {$product->getId()} not visible in catalog, NOT EXPORTING");
            return;
        }

        $this->logger->addInfo("Exporting Product ID {$product->getID()}");

        $productName = $product->getName();
        if (!isset($productName)) {
            return;
        }

        $productImageUrl = $product->getImage() ? $this->mediaConfig->getMediaUrl($product->getImage()) : '';
        $productInfo = [
            'name' => $product->getName(),
            'sku' => $product->getSku(),
            'product_url' => $this->getProductUrl($product, $websiteId),
            'product_image' => $productImageUrl,
            'product_id' => (int) $product->getId(),
            'currency_code' => $this->storeManager->getStore()->getCurrentCurrency()->getCode(),
            'price' => $product->getFinalPrice(),
            'regional_info' => $this->getRegionalInformation($websiteId, $product),
            'stock' => $this->getAggregateStock($product),
            'variants' => $this->getVariantsDict($product),
            'extra_fields' => $this->getExtraFields($product, $categoriesMap)
        ];
        $this->pixleeService->createProduct($websiteId, $productInfo);

        $this->logger->addInfo("Product Exported to Pixlee");
    }

    /**
     * @param $product
     * @param string|int $websiteId
     * @return string
     * @throws LocalizedException
     */
    protected function getProductUrl($product, $websiteId) {
        // Pixlee works off of website ID, but for this we want a store ID. This fetches the default store from
        // the website ID
        $storeId = $this->storeManager->getWebsite($websiteId)->getDefaultStore()->getId();

        // Ported from TurnTo Magento Extension
        // Due to core bug, it is necessary to retrieve url using this method (see https://github.com/magento/magento2/issues/3074)
        $urlRewrite = $this->urlFinder->findOneByData(
            [
                UrlRewrite::ENTITY_ID => $product->getId(),
                UrlRewrite::ENTITY_TYPE =>
                    ProductUrlRewriteGenerator::ENTITY_TYPE,
                UrlRewrite::STORE_ID => $storeId
            ]
        );

        if (isset($urlRewrite)) {
            return $this->getAbsoluteUrl($urlRewrite->getRequestPath(), $storeId);
        }

        return $product->getProductUrl();
    }

    /**
     * @param $relativeUrl
     * @param $storeId
     * @return string
     * @throws NoSuchEntityException
     */
    protected function getAbsoluteUrl($relativeUrl, $storeId)
    {
        $storeUrl = $this->storeManager->getStore($storeId)->getBaseUrl(UrlInterface::URL_TYPE_LINK);
        return rtrim($storeUrl, '/') . '/' . ltrim($relativeUrl, '/');
    }

    /**
     * @param $websiteId
     * @param $product
     * @return array
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function getRegionalInformation($websiteId, $product)
    {
        $result = [];
        $storeIds = $this->storeManager->getWebsite($websiteId)->getStoreIds();
        foreach ($storeIds as $storeId) {
            $storeProduct = $this->productFactory->create()->setStoreId($storeId);
            $this->productResource->load($storeProduct, $product->getId());

            $store = $this->storeManager->getStore($storeId);
            $storeCurrency = $store->getDefaultCurrency();
            $convertedPrice = $store->getBaseCurrency()->convert(
                $storeProduct->getFinalPrice(),
                $storeCurrency
            );

            $result[] = [
                'name' => $storeProduct->getName(),
                'buy_now_link_url' => $storeProduct->getProductUrl(),
                'price' => $convertedPrice,
                'stock' => $this->getAggregateStock($product),
                'currency' => $storeCurrency->getCode(),
                'variants_json' => $this->getVariantsDict($storeProduct),
                'region_code' => $store->getCode()
            ];
        }

        return $result;
    }

    /**
     * @param $product
     * @return float|null
     */
    public function getAggregateStock($product)
    {
        // 1) If this is a 'simple'-ish product, just return stockItem->getQty for this product
        // If it's not a simple product:
        //      2) If ALL child products have a 'null' stockItem->getQty, return a null for
        //      the aggregate
        //      3) If ANY child products have a non-null stock quantity (including 0), add them up
        $aggregateStock = null;

        // Regardless of what type of product this is, we can check the ConfigurableProduct class
        // to see if this product has "children" products.
        // If it is, we'll get a collection of products, if not, we'll simply get an empty collection
        $childProducts = $this->configurableProduct->getUsedProductCollection($product);

        // For some reason !empty() doesn't work here
        if (count($childProducts) > 0) {
            $this->logger->addInfo("This product has children");

            // Get aggregate stock of children
            foreach ($childProducts as $child) {
                $this->logger->addInfo("Child ID: {$child->getId()}");
                $this->logger->addInfo("Child SKU: {$child->getSku()}");
                // Then, try to actually get the stock count
                $stockItem = $this->stockRegistry->getStockItem($child->getId(), $product->getStore()->getWebsiteId());

                if ($stockItem !== null) {
                    if ($aggregateStock === null) {
                        $aggregateStock = max(0, $stockItem->getQty());
                    } else {
                        $aggregateStock += max(0, $stockItem->getQty());
                    }
                }

                $this->logger->addInfo("Aggregate stock after {$child->getId()}: $aggregateStock");
            }
        } else {
            $stockItem = $this->stockRegistry->getStockItem($product->getId(), $product->getStore()->getWebsiteId());
            $aggregateStock = $stockItem->getQty();
        }

        $this->logger->addInfo("Product {$product->getId()} aggregate stock: $aggregateStock");
        return $aggregateStock;
    }

    /**
     * @param $product
     * @return false|string
     */
    public function getVariantsDict($product)
    {
        // If the passed product is configurable, return a dictionary
        // (which will get converted to a JSON), otherwise, empty array
        $variantsDict = null;

        // This will just return an empty collection if this product is not configurable
        // e.g., won't throw an exception
        $childProducts = $this->configurableProduct->getUsedProductCollection($product);

        // If we have no children products, just skip to the bottom where we'll
        // return variantsDict, untouched
        if (count($childProducts) > 0) {
            $this->logger->addInfo("This product has children");

            foreach ($childProducts as $child) {
                $this->logger->addInfo("Child ID: {$child->getId()}");
                $this->logger->addInfo("Child SKU: {$child->getSku()}");

                if ($variantsDict === null) {
                    $variantsDict = [];
                }

                $stockItem = $this->stockRegistry->getStockItem($child->getId(), $product->getStore()->getWebsiteId());
                // It could be that Magento isn't keeping stock...
                if ($stockItem === null) {
                    $variantStock = null;
                } else {
                    $variantStock = max(0, $stockItem->getQty());
                }
                $this->logger->addInfo("Child Stock: $variantStock");

                $variantsDict[$child->getId()] = [
                    'variant_stock' => $variantStock,
                    'variant_sku' => $child->getSku(),
                ];
            }
        }

        $this->logger->addInfo("Product {$product->getId()} variantsDict: " . $this->serializer->serialize($variantsDict));
        if (empty($variantsDict)) {
            return '{}';
        } else {
            return $this->serializer->serialize($variantsDict);
        }
    }

    /**
     * @param $product
     * @param $categoriesMap
     * @return false|string
     * @throws NoSuchEntityException
     */
    public function getExtraFields($product, $categoriesMap)
    {
        $categoriesList = $this->getCategories($product, $categoriesMap);
        $productPhotos = $this->getAllPhotos($product);

        return $this->serializer->serialize([
            'product_photos' => $productPhotos,
            'categories' => $categoriesList,
            'ecommerce_platform' => Pixlee::PLATFORM,
            'ecommerce_platform_version' => $this->productMetadata->getVersion(),
            'version_hash' => $this->pixlee->getExtensionVersion(),
            'categories_last_updated_at' => time()
        ]);
    }

    /**
     * @param $product
     * @param $categoriesMap
     * @return array
     */
    public function getCategories($product, $categoriesMap)
    {
        $allCategoriesIds = [];
        $productCategories = $product->getCategoryIds();

        foreach ($productCategories as $categoryId) {
            // Check to make sure the category is in the categoriesMap before accessing
            // This should always be the case, but there seems to be a bug causing
            // Some Ids not to be found
            $categoryInMap = isset($categoriesMap[$categoryId]);
            if ($categoryInMap) {
                $parent_ids = $categoriesMap[$categoryId]['parent_ids'];
                foreach ($parent_ids as $parent_id) {
                    $allCategoriesIds[] = $parent_id;
                }
            }
        }

        $allCategoriesIds = array_unique($allCategoriesIds, SORT_NUMERIC);
        $result = [];
        foreach ($allCategoriesIds as $categoryId) {
            $fields = [
                'category_id' => $categoryId,
                'category_name' => $categoriesMap[$categoryId]['name'],
                'category_url' => $categoriesMap[$categoryId]['url']
            ];

            $result[] = $fields;
        }

        return $result;
    }

    /**
     * @param $product
     * @return array
     * @throws NoSuchEntityException
     */
    public function getAllPhotos($product)
    {
        $productPhotos = [];
        $extractedProduct = $this->productRepository->getById($product->getId());
        $images = $extractedProduct->getMediaGalleryImages();
        if (count($images) > 0) {
            foreach ($images as $image) {
                $photoURL = $image->getUrl();
                if (!in_array($photoURL, $productPhotos)) {
                    $productPhotos[] = $photoURL;
                }
            }
        }

        // Get Variants
        $childProducts = $this->configurableProduct->getUsedProductCollection($product);
        if (count($childProducts) > 0) {
            foreach ($childProducts as $child) {
                $actualChildProduct = $this->productRepository->getById($child->getId());
                $childImages = $actualChildProduct->getMediaGalleryImages();
                if (count($childImages) > 0) {
                    foreach ($childImages as $image) {
                        $photoURL = $image->getUrl();
                        if (!in_array($photoURL, $productPhotos)) {
                            $productPhotos[] = $photoURL;
                        }
                    }
                }
            }
        }

        return $productPhotos;
    }
}
