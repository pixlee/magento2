<?php
/**
 * Copyright © 2015 Pixlee
 * @author teemingchew
 */
namespace Pixlee\Pixlee\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $_catalogProduct;
    protected $_mediaConfig;
    protected $_scopeConfig;
    protected $_objectManager;
    protected $_logger;

    const ANALYTICS_BASE_URL = 'https://limitless-beyond-4328.herokuapp.com/events/';
    protected $_urls = array();

	/**
    * Config paths
    */
    const PIXLEE_ACTIVE              = 'pixlee_pixlee/account_settings/active';
    const PIXLEE_API_KEY             = 'pixlee_pixlee/account_settings/api_key';
    // 2016-03-21 - Commenting out the secret key stuff because it's not needed
    // for distillery...although if in the future we'd like to re-introduce it
    // I'd like for the code to still be here
    //
    //const PIXLEE_SECRET_KEY			    = 'pixlee_pixlee/account_settings/secret_key';
    const PIXLEE_RECIPE_ID           = 'pixlee_pixlee/account_settings/recipe_id';
    const PIXLEE_DISPLAY_OPTIONS_ID  = 'pixlee_pixlee/account_settings/display_options_id';

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
        \Pixlee\Pixlee\Helper\CookieManager $CookieManager
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

        $pixleeKey = $this->getApiKey();
        // 2016-03-21 - Commenting out the secret key stuff because it's not needed
        // for distillery...although if in the future we'd like to re-introduce it
        // I'd like for the code to still be here
        //
        //$pixleeSecret = $this->getSecretKey();

        $this->_pixleeAPI = new \Pixlee\Pixlee\Helper\Pixlee($pixleeKey, $this->_logger);
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

    // 2016-03-21 - Commenting out the secret key stuff because it's not needed
    // for distillery...although if in the future we'd like to re-introduce it
    // I'd like for the code to still be here
    //
    //public function getSecretKey()
    //{
    //	return $this->_scopeConfig->getValue(
    //		self::PIXLEE_SECRET_KEY,
    //		\Magento\Store\Model\ScopeInterface::SCOPE_STORE
    //   );
    //}

    public function getRecipeId()
    {
    	return $this->_scopeConfig->getValue(
    		self::PIXLEE_RECIPE_ID,
    		\Magento\Store\Model\ScopeInterface::SCOPE_STORE
       );
    }

    public function getDisplayOptionsId()
    {
    	return $this->_scopeConfig->getValue(
    		self::PIXLEE_DISPLAY_OPTIONS_ID,
    		\Magento\Store\Model\ScopeInterface::SCOPE_STORE
       );
    }

    public function isActive($store = null)
    {
    	if($this->_scopeConfig->isSetFlag(self::PIXLEE_ACTIVE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $store)){
            $pixleeKey = $this->getApiKey();

            // 2016-03-21 - Commenting out the secret key check because it's not needed
            // for distillery...although if in the future we'd like to re-introduce it
            // I'd like for the code to still be here
            //
            //$pixleeSecret = $this->getSecretKey();
            //if(!empty($pixleeKey) && !empty($pixleeSecret)) {

            if(!empty($pixleeKey)) {
                return true;
            }
        }

        return false;
    }

    public function isInactive()
    {
    	return !$this->isActive();
    }

    public function getUnexportedProducts()
    {
        $albumTable = 'px_product_albums';
        $collection = $this->_catalogProduct->getCollection()->addFieldToFilter('visibility', array('neq' => 1));
        $collection->getSelect()->joinLeft(array('albums' => $albumTable), 'e.entity_id = albums.product_id')->where('albums.product_id is NULL');
        $collection->addAttributeToSelect('*');
        return $collection;
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
        return $variantsDict;
    }

    public function exportProductToPixlee($product) 
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
        $product_mediaurl = $this->_mediaConfig->getMediaUrl($product->getImage());
        $response = $pixlee->createProduct($product->getName(), $product->getSku(), $product->getProductUrl(), $product_mediaurl, intval($product->getId()), $aggregateStock, $variantsDict);
        $this->_logger->addInfo("Product Exported to Pixlee");
        return $response;
    }

    public function _sendPayload($event, $payload)
    {
        if($payload && isset($this->_urls[$event])) {
            $ch = curl_init($this->_urls[$event]);

            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));

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
            $productData['price'] = $this->_pricingHelper->currency($product->getPrice(), true, false); // Get price in the main currency of the store. (USD, EUR, etc.)
            $productData['quantity'] = (int) $product->getQty();
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


            $productData['variant_id']   = $actualProduct->getId();
            $productData['variant_sku']   = $actualProduct->getSku();
            $productData['quantity'] = round($product->getQtyOrdered(), $mode=PHP_ROUND_HALF_UP);
            $productData['price'] = $this->_pricingHelper->currency($actualProduct->getPrice(), true, false); // Get price in the main currency of the store. (USD, EUR, etc.)
            $product = $product->getProduct();
            $productData['product_id']    = $maybeParent->getId();
            $productData['product_sku']   = $maybeParent->getData('sku');
        }
        return $productData;
    }

    public function _extractCart($quote)
    {
        if(is_a($quote, '\Magento\Sales\Model\Order')) {
            foreach ($quote->getAllVisibleItems() as $item) {
                $cartData['cart_contents'][] = $this->_extractProduct($item);

            $cartData['cart_total'] = $quote->getGrandTotal();
            $cartData['email'] = $quote->getCustomerEmail();
            $cartData['cart_type'] = "magento_2";
            $cartData['cart_total_quantity'] = (int) $quote->getData('total_qty_ordered');
            //$cartData['billing_address'] = $this->_extractAddress($quote->getBillingAddress());
            //$cartData['shipping_address'] = $this->_extractAddress($quote->getShippingAddress());
            $cartData['order_id'] = (int) $quote->getData('entity_id');
            $cartData['currency'] = $quote->getData('base_currency_code');
            $this->_logger->addInfo(json_encode($cartData));
            return $cartData;
        }

        return false;
    }

    public function _extractAddress($address)
    {
      $sortedAddress = array(
        'street'    => $address->getStreet(),
        'city'      => $address->getCity(),
        'state'     => $address->getRegion(),
        'country'   => $address->getCountryId(),
        'zipcode'   => $address->getPostcode()
      );
      return json_encode($sortedAddress);
    }

    public function _validateCredentials()
    {
        try{
            $this->_pixleeAPI->getAlbums();
        }catch(Exception $e){
            throw new \Magento\Framework\Exception\LocalizedException(__('You may have entered the wrong credentials. Please check again.'));
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
