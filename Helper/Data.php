<?php
/**
 * Copyright © 2015 Pixlee
 * @author teemingchew
 */
namespace Pixlee\Pixlee\Helper;
use Magento\Catalog\Api\CategoryRepositoryInterface;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $_catalogProduct;
    protected $_mediaConfig;
    protected $_scopeConfig;
    protected $_objectManager;
    protected $_logger;

    const ANALYTICS_BASE_URL = 'https://inbound-analytics.pixlee.com/events/';
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
        CategoryRepositoryInterface $categoryRepository,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory
    ){
        $this->_urls['addToCart'] = self::ANALYTICS_BASE_URL . 'addToCart';
        $this->_urls['removeFromCart'] = self::ANALYTICS_BASE_URL . 'removeFromCart';
        $this->_urls['checkoutStart'] = self::ANALYTICS_BASE_URL . 'checkoutStart';
        $this->_urls['checkoutSuccess'] = self::ANALYTICS_BASE_URL . 'conversion';

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
        $this->categoryRepository = $categoryRepository;
        $this->categoryFactory = $categoryFactory;

        $pixleeKey = $this->getApiKey();
        $pixleeSecretKey = $this->getSecretKey();

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
    		\Magento\Store\Model\ScopeInterface::SCOPE_STORE
       );
    }

    public function getSecretKey()
    {
    	return $this->_scopeConfig->getValue(
    		self::PIXLEE_SECRET_KEY,
    		\Magento\Store\Model\ScopeInterface::SCOPE_STORE
       );
    }

    public function getAccountId()
    {
        return $this->_scopeConfig->getValue(
            self::PIXLEE_ACCOUNT_ID,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getPDPWidgetId()
    {
        return $this->_scopeConfig->getValue(
            self::PIXLEE_PDP_WIDGET_ID,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getCDPWidgetId()
    {
        return $this->_scopeConfig->getValue(
            self::PIXLEE_CDP_WIDGET_ID,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function isActive($store = null)
    {
    	if($this->_scopeConfig->isSetFlag(self::PIXLEE_ACTIVE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $store)){
            return true;
        } else {
            return false;
        }

    }

    public function isInactive()
    {
    	return !$this->isActive();
    }

    public function getTotalProductsCount()
    {
        $collection = $this->_catalogProduct->getCollection();
        $collection->addFieldToFilter('visibility', array('neq' => 1));
        $collection->addFieldToFilter('status', array('neq' => 2));
        $count = $collection->getSize();
        return $count;
    }

    public function getPaginatedProducts($limit, $offset) {
        $products = $this->_catalogProduct->getCollection();
        $products->addFieldToFilter('visibility', array('neq' => 1));
        $products->addFieldToFilter('status', array('neq' => 2));
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

        // 1) If this is a 'simple'-ish product, just return stockItem->getQty for this product
        // If it's not a simple product:
        //      2) If ALL child products have a 'NULL' stockItem->getQty, return a NULL for
        //      the aggregate
        //      3) If ANY child products have a non-null stock quantity (including 0), add them up
        $aggregateStock = NULL;

        // Regardless of what type of product this is, we can check the ConfigurableProduct class
        // to see if this product has "children" products.
        // If it is, we'll get a collection of products, if not, we'll simply get an empty collection
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

    public function getCategories($product, $categoriesMap) {
        $allCategoriesIds = array();
        $productCategories = $product->getCategoryIds();

        foreach ($productCategories as $categoryId) {
            $parent_ids = $categoriesMap[$categoryId]['parent_ids'];
            $allCategoriesIds = array_merge($allCategoriesIds, $parent_ids);
        }

        $allCategoriesIds = array_unique($allCategoriesIds, SORT_NUMERIC);
        $result = array();
        foreach ($allCategoriesIds as $categoryId) {
            $fields = array(
                'category_id' => $categoryId,
                'category_name' => $categoriesMap[$categoryId]['name'],
                'category_url' => $categoriesMap[$categoryId]['url']
            );

            array_push($result, $fields);
        }

        return $result;
    }

    public function getCategoriesMap() {
        $categories = $this->categoryFactory->create()->getCollection()->addAttributeToSelect('*');

        $helper = array();
        foreach ($categories as $category) {
            $helper[$category->getId()] = $category->getName();
        }

        $allCategories = array();
        foreach ($categories as $cat) {
            $path = $cat->getPath();
            $parents = explode('/', $path);
            $fullName = '';

            $realParentIds = array();

            foreach ($parents as $parent) {
                if ((int) $parent != 1 && (int) $parent != 2) {
                    $name = $helper[(int) $parent];
                    $fullName = $fullName . $name . ' > ';
                    array_push($realParentIds, (int) $parent);
                }
            }

            $categoryBody = array(
                'name' => substr($fullName, 0, -3), 
                'url' => $cat->getUrl($cat),
                'parent_ids' => $realParentIds
            );

            $allCategories[$cat->getId()] = $categoryBody;
        }

        // Format
        // Hashmap where keys are category_ids and values are a hashmp with name and url keys
        return $allCategories;
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

    public function exportProductToPixlee($product, $categoriesMap)
    {
        // NOTE: 2016-03-21 - JUST noticed, that we were originally checking for getVisibility()
        // later on in the code, but since now I need $product to be reasonable in order to
        // call getAggregateStock and getVariantsDict, keeping this version of the check
        if ($product->getVisibility() <= 1) {
            $this->_logger->addDebug("*** Product ID {$product->getId()} not visible in catalog, NOT EXPORTING");
            return;
        }

        $aggregateStock = $this->getAggregateStock($product);
        $variantsDict = $this->getVariantsDict($product);

        $this->_logger->addDebug("Product ID {$product->getID()} class: " . get_class($product));

        $this->_logger->addDebug("Product ID {$product->getID()} stock: {$aggregateStock}");
        $this->_logger->addDebug("Product ID {$product->getID()} variants: " . json_encode($variantsDict));

        $productName = $product->getName();
        if($this->isInactive() || !isset($productName)) {
            return false;
        }

        $pixlee = $this->_pixleeAPI;

        $extraFields = $this->getExtraFields($product, $categoriesMap);
        $currencyCode = $this->_storeManager->getStore()->getCurrentCurrency()->getCode();

        $product_mediaurl = $this->_mediaConfig->getMediaUrl($product->getImage());
        $response = $pixlee->createProduct($product->getName(), $product->getSku(), $product->getProductUrl(), $product_mediaurl, intval($product->getId()), $aggregateStock, $variantsDict, $extraFields, $currencyCode);
        $this->_logger->addInfo("Product Exported to Pixlee");
        return $response;
    }

    public function getExtraFields($product, $categoriesMap)
    {
        $categoriesList = $this->getCategories($product, $categoriesMap);
        $productPhotos = $this->getAllPhotos($product);

        $extraFields = json_encode(array(
            'product_photos' => $productPhotos,
            'categories' => $categoriesList
        ));
        return $extraFields;
    }

    public function _sendPayload($event, $payload)
    {
        if($payload && isset($this->_urls[$event])) {
            $ch = curl_init($this->_urls[$event]);

            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));

            // Set User Agent
            if(isset($_SERVER['HTTP_USER_AGENT'])){
            curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
            }

            $response   = curl_exec($ch);
            $responseInfo   = curl_getinfo($ch);
            $responseCode   = $responseInfo['http_code'];
            curl_close($ch);

            if( !$this->isBetween($responseCode, 200, 299) ) {
                $this->_logger->addInfo("HTTP $responseCode response from Pixlee API");
            } elseif ( is_object($response) && is_null( $response->status ) ) {
                $this->_logger->addInfo("Pixlee did not return a status");
            } elseif( is_object($response) && !$this->isBetween( $response->status, 200, 299 ) ) {
                $errorMessage   = implode(',', (array)$response->message);
                $this->_logger->addInfo("$response->status - $errorMessage ");
            } else {
                $this->_logger->addInfo("Analytics event sent");
                return true;
            }
        }
        $this->_logger->addInfo("Analytics event not sent ".json_encode($payload));
        return false;
    }

    public function _extractProduct($product)
    {
        $this->_logger->addDebug("Passed product class: " . get_class($product));
        $this->_logger->addDebug("Passed product ID: {$product->getId()}");
        $this->_logger->addDebug("Passed product SKU: {$product->getSku()}");
        $this->_logger->addDebug("Passed product type: {$product->getTypeId()}");

        $productData = array();

        if($product->getId() && is_a($product, '\Magento\Catalog\Model\Product\Interceptor')) {
            // Add to Cart and Remove from Cart
            $productData['product_id']    = (int) $product->getId();
            $productData['product_sku']   = $product->getData('sku');
            $productData['variant_id']    = (int) $product->getIdBySku($product->getSku());
            $productData['variant_sku']   = $product->getSku();
            $productData['price']         = $this->_pricingHelper->currency($product->getPrice(), true, false); // Get price in the main currency of the store. (USD, EUR, etc.)
            $productData['quantity']      = (int) $product->getQty();
            $productData['currency']      = $this->_storeManager->getStore()->getCurrentCurrency()->getCode();
        } elseif ($product->getId() && is_a($product, '\Magento\Sales\Model\Order\Item')) {
            // Checkout Start and Conversion
            $actualProduct = $product->getProduct();

            // TIME TO JUMP THROUGH HOOPS FOR CONFIGURABLE PRODUCTS YAYYYYYY
            // Now that we have what we think is the actual product, try to find a
            // parent product (Note: This parent product is essentially generated from the variant SKU)
            $myObjectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $maybeParentConfigurable = $myObjectManager->create('Magento\ConfigurableProduct\Model\Product\Type\Configurable');
            $maybeParentIds = $maybeParentConfigurable->getParentIdsByChild($actualProduct->getId());
            $maybeParentId = empty($maybeParentIds) ? NULL : $maybeParentIds[0];
            $maybeParentFromSkuProductObject = $myObjectManager->create('Magento\Catalog\Model\Product');
            $maybeParentFromSkuProduct = $maybeParentFromSkuProductObject->load($maybeParentId);
            $this->_logger->addDebug("Maybe my parent class (from SKU): " . get_class($maybeParentFromSkuProduct));
            $this->_logger->addDebug("Maybe my parent ID (from SKU): {$maybeParentFromSkuProduct->getId()}");
            $this->_logger->addDebug("Maybe my parent SKU (from SKU): {$maybeParentFromSkuProduct->getSku()}");
            $this->_logger->addDebug("Maybe my parent type (from SKU): {$maybeParentFromSkuProduct->getTypeId()}");
            // After all that logic, it's possible we have a null parent, in which case...
            // ...we are our own parent
            // awks
            $maybeParent = NULL;
            if (is_null($maybeParentFromSkuProduct->getId())) {
                $this->_logger->addDebug("Ended up with null parent object, using self (probably 'simple' type)");
                $maybeParent = $actualProduct;
            } else {
                $maybeParent = $maybeParentFromSkuProduct;
            }


            $productData['variant_id']    = $actualProduct->getId();
            $productData['variant_sku']   = $actualProduct->getSku();
            $productData['quantity']      = round($product->getQtyOrdered(), $mode=PHP_ROUND_HALF_UP);
            $productData['price']         = $this->_pricingHelper->currency($actualProduct->getPrice(), true, false); // Get price in the main currency of the store. (USD, EUR, etc.)
            $product = $product->getProduct();
            $productData['product_id']    = $maybeParent->getId();
            $productData['product_sku']   = $maybeParent->getData('sku');
            $productData['currency']      = $this->_storeManager->getStore()->getCurrentCurrency()->getCode();
        }
        return $productData;
    }

    public function _extractCart($quote)
    {
        if(is_a($quote, '\Magento\Sales\Model\Order')) {
            foreach ($quote->getAllItems() as $item) {
                // $quote->getAllVisibleItems will actually give us only 'configurable' items
                // ...when we COULD use with more data from 'simple' items
                // It might be less robust? Let's see how we all feel about this
                if ($item->getProduct()->getTypeId() == 'configurable') {
                    $this->_logger->addDebug("Skipping configurable item: {$item->getId()}");
                } else {
                    $cartData['cart_contents'][] = $this->_extractProduct($item);
                }
            }

            $cartData['cart_total'] = $quote->getGrandTotal();
            $cartData['email'] = $quote->getCustomerEmail();
            $cartData['cart_type'] = "magento_2";
            $cartData['cart_total_quantity'] = (int) $quote->getData('total_qty_ordered');
            $cartData['billing_address'] = $this->_extractAddress($quote->getBillingAddress());
            $cartData['shipping_address'] = $this->_extractAddress($quote->getShippingAddress());
            $cartData['order_id'] = (int) $quote->getData('entity_id');
            $cartData['currency'] = $quote->getData('base_currency_code');
            $this->_logger->addInfo(json_encode($cartData));
            return $cartData;
        }

        return false;
    }

    public function _extractAddress($address)
    {
        // 2016-03-21, Yunfan
        // Something went wonky with my caches, and it always asks me to 'update'
        // my address when I input it - this fixes whatever weird edge case I'm running
        // into right now, and shouldn't hurt the normal case
        if (is_null($address)) {
            $sortedAddress = array();
        } else {
            $sortedAddress = array(
                'street'    => $address->getStreet(),
                'city'      => $address->getCity(),
                'state'     => $address->getRegion(),
                'country'   => $address->getCountryId(),
                'zipcode'   => $address->getPostcode()
            );
        }
        return json_encode($sortedAddress);
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

    public function _preparePayload($extraData = array())
    {
        if(($payload = $this->_getPixleeCookie()) && $this->isActive()) {
            // Append all extra data to the payload
            foreach($extraData as $key => $value) {
              // Don't accidentally overwrite existing data.
                if(!isset($payload[$key])) {
                    $payload[$key] = $value;
                }
            }
              // Required key/value pairs not in the payload by default.
            $payload['API_KEY']= $this->getApiKey();
            $payload['distinct_user_hash'] = $payload['CURRENT_PIXLEE_USER_ID'];
            $payload['ecommerce_platform'] = 'magento_2';
            $payload['ecommerce_platform_version'] = '2.0.0';
            $payload['version_hash'] = $this->_getVersionHash();
            $this->_logger->addDebug("Sending payload: " . json_encode($payload));
            return json_encode($payload);
        }
        $this->_logger->addInfo("Analytics event not sent because the cookie wasn't found");
        return false; // No cookie exists,
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
