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
     const PIXLEE_ACTIVE				      = 'pixlee_pixlee/account_settings/active';
     const PIXLEE_USER_ID             = 'pixlee_pixlee/account_settings/user_id';
     const PIXLEE_API_KEY             = 'pixlee_pixlee/account_settings/api_key';
     const PIXLEE_SECRET_KEY			    = 'pixlee_pixlee/account_settings/secret_key';
     const PIXLEE_RECIPE_ID           = 'pixlee_pixlee/account_settings/recipe_id';
     const PIXLEE_DISPLAY_OPTIONS_ID  = 'pixlee_pixlee/account_settings/display_options_id';

    public function __construct(
        \Magento\Catalog\Model\Product $catalogProduct,
        \Magento\Catalog\Model\Product\Media\Config $mediaConfig,
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
        $this->_mediaConfig       = $mediaConfig;
        $this->_scopeConfig       = $scopeConfig;
        $this->_objectManager     = $objectManager;
        $this->_logger            = $logger;
        $this->_pageFactory       = $pageFactory;
        $this->_pricingHelper     = $pricingHelper;
        $this->_directoryRegion   = $directoryRegion;
        $this->_cookieManager     = $CookieManager;

        $pixleeKey = $this->getApiKey();
        $pixleeSecret = $this->getSecretKey();
        $pixleeUserId = $this->getUserId();

        $this->_pixleeAPI = new \Pixlee\Pixlee\Helper\Pixlee($pixleeKey, $pixleeSecret, $pixleeUserId, $this->_logger);
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

    public function getUserId()
    {
        return $this->_scopeConfig->getValue(
            self::PIXLEE_USER_ID,
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
    		$pixleeUserId = $this->getUserId();
        $pixleeKey = $this->getApiKey();
        $pixleeSecret = $this->getSecretKey();

        if(!empty($pixleeUserId) && !empty($pixleeKey) && !empty($pixleeSecret)) {
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

    public function exportProductToPixlee($product)
    {
        $productName = $product->getName();
        if($this->isInactive() || !isset($productName)) {
            return false;
        }

        $pixlee = $this->_pixleeAPI;
        if($product->getVisibility() != 1) { // Make sure the product is visible in search or catalog
            $product_mediaurl = $this->_mediaConfig->getMediaUrl($product->getImage());
            $response = $pixlee->createProduct($product->getName(), $product->getSku(), $product->getProductUrl(), $product_mediaurl);
            $this->_logger->addInfo("Product Exported to Pixlee");
            return $response;
        }
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
            // $productData['variant_id'] = $product->getIdBySku($product->getSku());  Returns null, other methods don't return the correct variant_id either
            $productData['variant_sku']   = $product->getSku();
            $productData['quantity'] = (int) $product->getQtyToInvoice();
            $productData['price'] = $this->_pricingHelper->currency($product->getPrice(), true, false); // Get price in the main currency of the store. (USD, EUR, etc.)

            $product = $product->getProduct();

            $productData['product_id']    = $product->getId();
            $productData['product_sku']   = $product->getData('sku');
        }

        return $productData;
    }

    public function _extractCart($quote)
    {
        if(is_a($quote, '\Magento\Sales\Model\Order')) {
            foreach ($quote->getAllVisibleItems() as $item) {
                $cartData['cart_contents'][] = $this->_extractProduct($item);
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
