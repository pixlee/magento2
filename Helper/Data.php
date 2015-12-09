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
  protected $_productAlbum;
  protected $_logger;

  const ANALYTICS_BASE_URL = 'https://limitless-beyond-4328.herokuapp.com/events/';
  protected $_urls = array();

	   /**
     * Config paths
     */
    const PIXLEE_ACTIVE				      = 'pixlee_pixlee/account_settings/active';
    const PIXLEE_USER_ID            = 'pixlee_pixlee/account_settings/user_id';
    const PIXLEE_API_KEY				    = 'pixlee_pixlee/account_settings/api_key';
    const PIXLEE_SECRET_KEY			    = 'pixlee_pixlee/account_settings/secret_key';
    const PIXLEE_RECIPE_ID				  = 'pixlee_pixlee/account_settings/recipe_id';
    const PIXLEE_DISPLAY_OPTIONS_ID	= 'pixlee_pixlee/account_settings/display_options_id';

    public function __construct(
      \Magento\Catalog\Model\Product $catalogProduct,
      \Magento\Catalog\Model\Product\Media\Config $mediaConfig,
      \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
      \Pixlee\Pixlee\Model\Product\Album $productAlbum,
      \Psr\Log\LoggerInterface $logger,
      \Magento\Framework\Pricing\Helper\Data $pricingHelper,
      \Magento\Directory\Model\Region $directoryRegion
      ){
      $this->_urls['addToCart'] = self::ANALYTICS_BASE_URL . 'addToCart';
      $this->_urls['removeFromCart'] = self::ANALYTICS_BASE_URL . 'removeFromCart';
      $this->_urls['checkoutStart'] = self::ANALYTICS_BASE_URL . 'checkoutStart';
      $this->_urls['checkoutSuccess'] = self::ANALYTICS_BASE_URL . 'conversion';

      $this->_catalogProduct    = $catalogProduct;
      $this->_mediaConfig       = $mediaConfig;
      $this->_scopeConfig       = $scopeConfig;
      $this->_productAlbum      = $productAlbum;
      $this->_logger            = $logger;
      $this->_pricingHelper     = $pricingHelper;
      $this->_directoryRegion   = $directoryRegion;

      $pixleeKey = $this->getApiKey();
      $pixleeSecret = $this->getSecretKey();
      $pixleeUserId = $this->getUserId();

      $this->_pixleeAPI = new \Pixlee\Pixlee\Helper\Pixlee($pixleeKey, $pixleeSecret, $pixleeUserId);
    }

    private function _logPixleeMsg($message){
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

    public function isActive($store = null) {
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

    public function isInactive() {
    	return !$this->isActive();
    }

    public function getUnexportedProducts(){
      $albumTable = 'px_product_albums';
      $collection = $this->_catalogProduct->getCollection()->addFieldToFilter('visibility', array('neq' => 1));
      $collection->getSelect()->joinLeft(array('albums' => $albumTable), 'e.entity_id = albums.product_id')->where('albums.product_id is NULL');
      $collection->addAttributeToSelect('*');
      return $collection;
    }

    public function getPixleeRemainingText() {
      // $c = $this->getUnexportedProducts()->count();
      // if($this->isInactive()) {
      //   return "Save your Pixlee API access information before exporting your products.";
      // } elseif($c > 0) {
      //   return "Export your products to Pixlee and start collecting photos. There ". (($c > 1) ? 'are' : 'is') ." <strong>". $c ." ". (($c > 1) ? 'products' : 'product') ."</strong> to export to Pixlee.";
      // } else {
      //   return "All your products have been exported to Pixlee. Congratulations!";
      // }
      if($this->isInactive()) {
        return "Save your Pixlee API access information before exporting your products.";
      } else {
        return "Export your products to Pixlee and start collecting photos.";
      }
    }

    public function exportProductToPixlee($product) {
      $productName = $product->getName();
      if($this->isInactive() || !isset($productName)) {
        return false;
      }
      $pixlee = $this->_pixleeAPI;
        if($product->getVisibility() != 1) { // Make sure the product is visible in search or catalog
          $product_mediaurl = $this->_mediaConfig->getMediaUrl($product->getImage());
          $response = $pixlee->createProduct($product->getName(), $product->getSku(), $product->getProductUrl(), $product_mediaurl);
          // try {
          //   $albumId = 0;
          //   if(isset($response->data->album->id)) {
          //     $albumId = $response->data->album->id;
          //   } else if(isset($response->data->product->album_id)) {
          //     $albumId = $response->data->product->album_id;
          //   }

          //   if($albumId) {
          //     $this->_logPixleeMsg("PIXLEE ERROR1: " . $albumId);
          //     $album = $this->_productAlbum;
          //     $album->setProductId($product->getId())->setPixleeAlbumId($albumId);
          //     $album->save();
          //     $this->_logPixleeMsg("PIXLEE ERROR2: " . $albumId);
          //   } else {
          //     return false;
          //   }
          // } catch (Exception $e) {
          //   $this->_logPixleeMsg("PIXLEE ERROR: " . $e->getMessage());
          //   return false;
          // }
        }

        return true;
      }

      public function _sendPayload($event, $payload) {
        if($payload && isset($this->_urls[$event])) {
          $ch = curl_init($this->_urls[$event]);

          curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
          curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
          curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/x-www-form-urlencoded'));

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

      public function _extractProduct($product) {
        $productData = array();
        $productData['quantity'] = $product->getQty();

        if($product->getId()) {
          $productData['id']    = $product->getId();
          $productData['name']  = $product->getName();
          $productData['sku']   = $product->getSku();
          $productData['price'] = $this->_pricingHelper->currency($product->getPrice(), true, false); // Get price in the main currency of the store. (USD, EUR, etc.)
        }

        return $productData;
      }

      public function _extractCart($quote) {
        if(is_a($quote, '\Magento\Sales\Model\Quote') || is_a($quote, '\Magento\Sales\Model\Order')) {
          $cartData = array('products' => array());
          foreach ($quote->getAllItems() as $item) {
            $cartData['products'][] = $this->_extractProduct($item);
          }
          $cartData['total'] = $quote->getGrandTotal();
          return $cartData;
        }
        return false;
      }

      public function _extractCustomer($quote) {
        if(is_a($quote, '\Magento\Sales\Model\Order')) {
          return array(
            'name'      => $quote->getShippingAddress()->getName(),
            'email'     => $quote->getShippingAddress()->getEmail(),
            'city'      => $quote->getShippingAddress()->getCity(),
            'state'     => $this->_directoryRegion->load($quote->getShippingAddress()->getRegionId())->getName(),
            'country'   => $quote->getShippingAddress()->getCountry()
            );
        }
        return false;
      }

      public function _validateCredentials() {
        try{
          $this->_pixleeAPI->getAlbums();
        }catch(Exception $e){
          throw new \Magento\Framework\Exception\LocalizedException(__('You may have entered the wrong credentials. Please check again.'));
        }
      }

      public function _preparePayload($extraData = array()) {
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
          $payload['uid'] = $payload['CURRENT_PIXLEE_USER_ID'];
          $payload['pixlee_album_photos_timestamps'] = $payload['CURRENT_PIXLEE_ALBUM_PHOTOS_TIMESTAMP'];
          $payload['pixlee_album_photos'] = $payload['CURRENT_PIXLEE_ALBUM_PHOTOS'];
          $payload['horizontal_page'] = $payload['HORIZONTAL_PAGE'];
          return http_build_query($payload);
        }
        return false; // No cookie exists, 
      }

      protected function _getPixleeCookie() {
        if(isset($_COOKIE['pixlee_analytics_cookie'])){
          if($cookie = $_COOKIE['pixlee_analytics_cookie']) {
                // Return the decoded cookie as an associative array, not a PHP object
                // as json_decode prefers.
            return json_decode($cookie, true);
          }
        }
        return false;
      }

      protected function isBetween($theNum, $low, $high) {
        if($theNum >= $low && $theNum <= $high) {
          return true;
        } else {
          return false;
        }
      }
    }