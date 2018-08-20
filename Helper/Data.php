<?php

namespace Pixlee\Pixlee\Helper;
use Magento\Catalog\Api\CategoryRepositoryInterface;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $_catalogProduct;
    protected $_mediaConfig;
    protected $_scopeConfig;
    protected $_objectManager;
    protected $_logger;

    const ANALYTICS_BASE_URL = 'https://takehomemagento.herokuapp.com/analytics/';
    protected $_urls = array();

    /**
    * Config paths
    */
    const PIXLEE_ACTIVE              = 'pixlee_pixlee/existing_customers/account_settings/active';
    const PIXLEE_API_KEY             = 'pixlee_pixlee/existing_customers/account_settings/api_key';
    const PIXLEE_SECRET_KEY          = 'pixlee_pixlee/existing_customers/account_settings/secret_key';
    const PIXLEE_ACCOUNT_ID          = 'pixlee_pixlee/existing_customers/pdp_widget_settings/account_id';
    const PIXLEE_PDP_WIDGET_ID           = 'pixlee_pixlee/existing_customers/pdp_widget_settings/pdp_widget_id';
    const PIXLEE_CDP_WIDGET_ID           = 'pixlee_pixlee/existing_customers/pdp_widget_settings/cdp_widget_id';

    public function __construct(
        \Magento\Catalog\Model\Product $catalogProduct,
        \Magento\Catalog\Model\Product\Media\Config $mediaConfig,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\ConfigurableProduct\Model\Product\Type\Configurable $configurableProduct,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\ObjectManager\ObjectManager $objectManager,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Framework\Pricing\Helper\Data $pricingHelper,
        \Magento\Directory\Model\Region $directoryRegion,
        \Pixlee\Pixlee\Helper\CookieManager $CookieManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ConfigResource\ConfigInterface $resourceConfig,
        \Magento\Catalog\Model\ProductFactory $productFactory
    ){
        $this->_catalogProduct    = $catalogProduct;
        $this->_configurableProduct    = $configurableProduct;
        $this->_mediaConfig       = $mediaConfig;
        $this->_stockRegistry     = $stockRegistry;
        $this->_scopeConfig       = $scopeConfig;
        $this->_objectManager     = $objectManager;
        $this->_logger            = $logger;
        $this->_pageFactory       = $pageFactory;
        $this->_pricingHelper     = $pricingHelper;
        $this->_directoryRegion   = $directoryRegion;
        $this->_cookieManager     = $CookieManager;
        $this->_storeManager      = $storeManager;
        $this->resourceConfig     = $resourceConfig;
        $this->productFactory     = $productFactory;
    }

    public function getStoreCode()
    {
        return $this->_storeManager->getStore()->getCode();
    } 

    public function getStoreName()
    {
        return $this->_storeManager->getStore()->getName();
    }

    public function getWebsiteId()
    {
        return $this->_storeManager->getStore()->getWebsiteId();
    }

    public function initializePixleeAPI($websiteId) {
        $this->websiteId = $websiteId;
        $pixleeKey = $this->getApiKey($websiteId);
        $pixleeSecretKey = $this->getSecretKey($websiteId);
        $this->_pixleeAPI = new \Pixlee\Pixlee\Helper\Pixlee($pixleeKey, $pixleeSecretKey, $this->_logger);
    }

    private function _logPixleeMsg($message)
    {
        $this->_logger->addInfo("[Pixlee] :: ".$message);
    }

    public function getApiKey()
    {
        return $this->_scopeConfig->getValue(
            self::PIXLEE_API_KEY,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $this->websiteId
        );
    }

    public function getSecretKey()
    {
        return $this->_scopeConfig->getValue(
            self::PIXLEE_SECRET_KEY,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $this->websiteId
        );
    }

    public function getAccountId()
    {
        return $this->_scopeConfig->getValue(
            self::PIXLEE_ACCOUNT_ID,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $this->websiteId
        );
    }

    public function getPDPWidgetId()
    {
        return $this->_scopeConfig->getValue(
            self::PIXLEE_PDP_WIDGET_ID,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $this->websiteId
        );
    }

    public function getCDPWidgetId()
    {
        return $this->_scopeConfig->getValue(
            self::PIXLEE_CDP_WIDGET_ID,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE,
            $this->websiteId
        );
    }

    public function isActive()
    {
        if($this->_scopeConfig->isSetFlag(self::PIXLEE_ACTIVE, \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE, $this->websiteId)) {
            return true;
        } else {
            return false;
        }
    }

    public function isInactive()
    {
        return !$this->isActive();
    }

    public function getTotalProductsCount($websiteId)
    {
        $collection = $this->_catalogProduct->getCollection();
        $collection->addFieldToFilter('visibility', array('neq' => 1));
        $collection->addFieldToFilter('status', array('neq' => 2));
        $collection->addWebsiteFilter($websiteId);
        $count = $collection->getSize();
        return $count;
    }

    public function getPaginatedProducts($limit, $offset, $websiteId) {
        $products = $this->_catalogProduct->getCollection();
        $products->addFieldToFilter('visibility', array('neq' => 1));
        $products->addFieldToFilter('status', array('neq' => 2));
        $products->addWebsiteFilter($websiteId);
        $products->getSelect()->limit($limit, $offset);
        $products->addAttributeToSelect('*');
        return $products;
    }

    public function getPixleeRemainingText()
    {
        if($this->isInactive()) {
            return "Save your Pixlee API access information before exporting your products.";
        } else {
            return "Export your products to Pixlee and start collecting photos.";
        }
    }

    public function getAggregateStock($product) {

        $aggregateStock = NULL;
        $childProducts = $this->_configurableProduct->getUsedProductCollection($product);

        // For some reason !empty() doesn't work here
        if (count($childProducts) > 0) {
            $this->_logger->addDebug("This product has children");

            // Get aggregate stock of children
            foreach($childProducts as $child) {
                $this->_logger->addDebug("Child ID: {$child->getId()}");
                $this->_logger->addDebug("Child SKU: {$child->getSku()}");

                // Then, try to actually get the stock count
                $stockItem = $this->_stockRegistry->getStockItem($child->getId(), $product->getStore()->getWebsiteId());

                if (!is_null($stockItem)) {
                    if (is_null($aggregateStock)) {
                        $aggregateStock = max(0, $stockItem->getQty());
                    } else {
                        $aggregateStock += max(0, $stockItem->getQty());
                    }
                }

                $this->_logger->addDebug("Aggregate stock after {$child->getId()}: {$aggregateStock}");
            }
        } else {
            $stockItem = $this->_stockRegistry->getStockItem($product->getId(), $product->getStore()->getWebsiteId());
            $aggregateStock = $stockItem->getQty();
        }

        $this->_logger->addDebug("Product {$product->getId()} aggregate stock: {$aggregateStock}");
        return $aggregateStock;
    }

    public function getVariantsDict($product) {

        // If the passed product is configurable, return a dictionary
        // (which will get converted to a JSON), otherwise, empty array
        $variantsDict = NULL;

        // This will just return an empty collection if this product is not configurable
        // e.g., won't throw an exception
        $childProducts = $this->_configurableProduct->getUsedProductCollection($product);

        // If we have no children products, just skip to the bottom where we'll
        // return variantsDict, untouched
        if (count($childProducts) > 0) {
            $this->_logger->addDebug("This product has children");

            foreach($childProducts as $child) {
                $this->_logger->addDebug("Child ID: {$child->getId()}");
                $this->_logger->addDebug("Child SKU: {$child->getSku()}");

                if (is_null($variantsDict)) {
                    $variantsDict = array();
                }

                $stockItem = $this->_stockRegistry->getStockItem($child->getId(), $product->getStore()->getWebsiteId());
                // It could be that Magento isn't keeping stock...
                if (is_null($stockItem)) {
                  $variantStock = NULL;
                } else {
                  $variantStock = max(0, $stockItem->getQty());
                }
                $this->_logger->addDebug("Child Stock: {$variantStock}");

                $variantsDict[$child->getId()] = array(
                  'variant_stock' => $variantStock,
                  'variant_sku' => $child->getSku(),
                );
            }
        }

        $this->_logger->addDebug("Product {$product->getId()} variantsDict: " . json_encode($variantsDict));
        if (empty($variantsDict)) {
            return "{}";
        } else {
            return json_encode($variantsDict);
        }
    }

    public function getAllPhotos($product) {
        $productPhotos = array();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $productRepository = $objectManager->get('\Magento\Catalog\Model\ProductRepository');
        $extractedProduct = $productRepository->getById($product->getId());
        $images = $extractedProduct->getMediaGalleryImages();
        if (count($images) > 0) {
            foreach ($images as $image) {
                $photoURL = $image->getUrl();
                if (in_array($photoURL, $productPhotos) == false) {
                    array_push($productPhotos, $photoURL);
                }
            }
        }

        // Get Variants
        $childProducts = $this->_configurableProduct->getUsedProductCollection($product);
        if (count($childProducts) > 0) {
            foreach ($childProducts as $child) {
                $actualChildProduct = $productRepository->getById($child->getId());
                $childImages = $actualChildProduct->getMediaGalleryImages();
                if (count($childImages) > 0) {
                    foreach ($childImages as $image) {
                        $photoURL = $image->getUrl();
                        if (in_array($photoURL, $productPhotos) == false) {
                            array_push($productPhotos, $photoURL);
                        }
                    }
                }
            }
        }

        return $productPhotos;
    }

    public function getRegionalInformation($websiteId, $product) {
        $result = array();

        $website = $this->_storeManager->getWebsite($websiteId);
        $storeIds = $website->getStoreIds();   
        foreach ($storeIds as $storeId) {
            $storeCode = $this->_storeManager->getStore($storeId)->getCode();
            $storeBaseUrl = $this->_storeManager->getStore($storeId)->getBaseUrl();
            $storeProduct = $this->productFactory->create()->setStoreId($storeId)->load($product->getId());

            $basePrice = $storeProduct->getFinalPrice();
            $storeCurrency = $this->_storeManager->getStore($storeId)->getDefaultCurrency();
            $convertedPrice = $this->_storeManager->getStore($storeId)->getBaseCurrency()->convert($basePrice, $storeCurrency);

            $productUrl = $storeBaseUrl . $storeProduct->getUrlKey() . ".html";

            array_push($result, array(
                'name' => $storeProduct->getName(),
                'buy_now_link_url' => $productUrl,
                'price' => $convertedPrice,
                'stock' => $this->getAggregateStock($product),
                'currency' => $storeCurrency->getCode(),
                'description' => $storeProduct->getDescription(),
                'variants_json' => $this->getVariantsDict($storeProduct),
                'region_code' => $storeCode
            ));
        }

        return $result;
    }    

    public function exportProductToPixlee($product, $websiteId)
    {   
        if ($product->getVisibility() <= 1) {
            $this->_logger->addDebug("*** Product ID {$product->getId()} not visible in catalog, NOT EXPORTING");
            return;
        }

        $this->_logger->addDebug("Product ID {$product->getID()} class: " . get_class($product));

        $productName = $product->getName();
        if($this->isInactive() || !isset($productName)) {
            return false;
        }

        $pixlee = $this->_pixleeAPI;

        $response = $pixlee->createProduct(
            $product->getName(), 
            $product->getSku(), 
            $product->getProductUrl(),
            $this->_mediaConfig->getMediaUrl($product->getImage()),
            intval($product->getId()), 
            $this->getAggregateStock($product),
            $this->getVariantsDict($product),
            $this->getExtraFields($product), 
            $this->_storeManager->getStore()->getCurrentCurrency()->getCode(),
            $product->getFinalPrice(),
            $this->getRegionalInformation($websiteId, $product)
        );

        $this->_logger->addInfo("Product Exported to Pixlee");
        return $response;
    }

    public function getExtraFields($product)
    {
        $productPhotos = $this->getAllPhotos($product);

        $extraFields = json_encode(array(
            'product_photos' => $productPhotos
        ));
        return $extraFields;
    }

    public function _validateCredentials()
    {   
        // this function gets executed after the configuration is saved
        // The idea is that we make an API call that requires credentails. 
        // If it goes through, we say "successfull". Else, we say "not successfull" and set the credentails to point zero
        // I originally wanted to do this is a backend model where we can do stuff before save. But unfortunately, backend models are not avaialble for group of items.
        $this->_logger->addInfo("Validating Credentails");
        if ($this->isActive()) {
            $this->_logger->addInfo("Making the call"); 
            $test_call_success = $this->_pixleeAPI->getAlbums();
            if ($test_call_success) {
                $this->_logger->addInfo("Show Message that everything went fine"); 
            } else {
                $this->resourceConfig->saveConfig(
                    self::PIXLEE_ACTIVE, 
                    '0', 
                    \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 
                    \Magento\Store\Model\Store::DEFAULT_STORE_ID
                );

                $this->resourceConfig->saveConfig(
                    self::PIXLEE_API_KEY, 
                    '', 
                    \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 
                    \Magento\Store\Model\Store::DEFAULT_STORE_ID
                );

                $this->resourceConfig->saveConfig(
                    self::PIXLEE_SECRET_KEY, 
                    '', 
                    \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 
                    \Magento\Store\Model\Store::DEFAULT_STORE_ID
                );

                throw new \Exception("Please check the credentails and try again. Your settings were not saved");
                $this->_logger->addInfo("Show Message that config was not saved"); 
            }
        }
    }

    protected function _getVersionHash() {
        $version_hash = file_get_contents($this->_module_dir('Pixlee_Pixlee').'/version.txt');
        $version_hash = str_replace(array("\r", "\n"), '', $version_hash);
        return $version_hash;
    }

    function _module_dir($moduleName, $type = '')
    {
        $om = \Magento\Framework\App\ObjectManager::getInstance();
        $reader = $om->get('Magento\Framework\Module\Dir\Reader');
        return $reader->getModuleDir($type, $moduleName);
    }

    protected function _getPixleeCookie() {
        $cookie = $this->_cookieManager->get();
        if (isset($cookie)) {
            return json_decode($cookie, true);
        }
        return false;
    }

    protected function isBetween($theNum, $low, $high)
    {
        if($theNum >= $low && $theNum <= $high) {
            return true;
        } else {
            return false;
        }
    }
}
