<?php
/**
 * Copyright © Pixlee TurnTo, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Model;

use Magento\Catalog\Model\Product as CatalogProduct;
use Magento\Catalog\Model\Product\Interceptor;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Module\Dir;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Sales\Model\Order as SalesOrder;
use Magento\Sales\Model\Order\Item as SalesItem;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Pixlee\Pixlee\Model\Logger\PixleeLogger;
use Pixlee\Pixlee\Model\Config\Api;

class Cart
{
    /**
     * @var PixleeLogger
     */
    protected $logger;
    /**
     * @var CatalogProduct
     */
    protected $catalogProduct;
    /**
     * @var Configurable
     */
    protected $configurableProduct;
    /**
     * @var PricingHelper
     */
    protected $pricingHelper;
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var CookieManager
     */
    protected $cookieManager;
    /**
     * @var Dir
     */
    protected $moduleDirs;
    /**
     * @var Api
     */
    protected $apiConfig;
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
     * @param CatalogProduct $catalogProduct
     * @param Configurable $configurableProduct
     * @param PricingHelper $pricingHelper
     * @param StoreManagerInterface $storeManager
     * @param SerializerInterface $serializer
     * @param CookieManager $cookieManager
     * @param Dir $moduleDirs
     * @param Api $apiConfig
     * @param PixleeLogger $logger
     * @param ProductMetadataInterface $productMetadata
     * @param Pixlee $pixlee
     */
    public function __construct(
        CatalogProduct $catalogProduct,
        Configurable $configurableProduct,
        PricingHelper $pricingHelper,
        StoreManagerInterface $storeManager,
        SerializerInterface $serializer,
        CookieManager $cookieManager,
        Dir $moduleDirs,
        Api $apiConfig,
        PixleeLogger $logger,
        ProductMetadataInterface $productMetadata,
        Pixlee $pixlee
    ) {
        $this->catalogProduct = $catalogProduct;
        $this->configurableProduct = $configurableProduct;
        $this->pricingHelper = $pricingHelper;
        $this->storeManager = $storeManager;
        $this->serializer = $serializer;
        $this->cookieManager = $cookieManager;
        $this->moduleDirs = $moduleDirs;
        $this->apiConfig = $apiConfig;
        $this->logger = $logger;
        $this->productMetadata = $productMetadata;
        $this->pixlee = $pixlee;
    }

    /**
     * @param $product
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function extractProduct($product)
    {
        $this->logger->addInfo("Passed product class: " . get_class($product));
        $this->logger->addInfo("Passed product ID: {$product->getId()}");
        $this->logger->addInfo("Passed product SKU: {$product->getSku()}");
        $this->logger->addInfo("Passed product type: {$product->getTypeId()}");

        $productData = [];

        if ($product->getId() && is_a($product, Interceptor::class)) {
            // Add to Cart and Remove from Cart
            $productData['product_id']    = (int) $product->getId();
            $productData['product_sku']   = $product->getData('sku');
            $productData['variant_id']    = (int) $product->getIdBySku($product->getSku());
            $productData['variant_sku']   = $product->getSku();
            // Get price in the main currency of the store. (USD, EUR, etc.)
            $productData['price']         = $this->pricingHelper->currency($product->getPrice(), true, false);
            $productData['quantity']      = (int) $product->getQty();
            $productData['currency']      = $this->storeManager->getStore()->getCurrentCurrency()->getCode();
        } elseif ($product->getId() && is_a($product, SalesItem::class)) {
            // Checkout Start and Conversion
            $actualProduct = $product->getProduct();

            // TIME TO JUMP THROUGH HOOPS FOR CONFIGURABLE PRODUCTS YAYYYYYY
            // Now that we have what we think is the actual product, try to find a
            // parent product (Note: This parent product is essentially generated from the variant SKU)
            $maybeParentIds = $this->configurableProduct->getParentIdsByChild($actualProduct->getId());
            $maybeParentId = empty($maybeParentIds) ? null : $maybeParentIds[0];
            $maybeParentFromSkuProduct = $this->catalogProduct->load($maybeParentId);
            $this->logger->addInfo("Maybe my parent class (from SKU): " . get_class($maybeParentFromSkuProduct));
            $this->logger->addInfo("Maybe my parent ID (from SKU): {$maybeParentFromSkuProduct->getId()}");
            $this->logger->addInfo("Maybe my parent SKU (from SKU): {$maybeParentFromSkuProduct->getSku()}");
            $this->logger->addInfo("Maybe my parent type (from SKU): {$maybeParentFromSkuProduct->getTypeId()}");

            if ($maybeParentFromSkuProduct->getId() === null) {
                $this->logger->addInfo("Ended up with null parent object, using self (probably 'simple' type)");
                $maybeParent = $actualProduct;
            } else {
                $maybeParent = $maybeParentFromSkuProduct;
            }

            $productData['variant_id']    = $actualProduct->getId();
            $productData['variant_sku']   = $actualProduct->getSku();
            $productData['quantity']      = round($product->getQtyOrdered(), PHP_ROUND_HALF_UP);
            $productData['price']         = $this->pricingHelper->currency($actualProduct->getPrice(), true, false); // Get price in the main currency of the store. (USD, EUR, etc.)
            $productData['product_id']    = $maybeParent->getId();
            $productData['product_sku']   = $maybeParent->getData('sku');
            $productData['currency']      = $this->storeManager->getStore()->getCurrentCurrency()->getCode();
        }
        return $productData;
    }

    /**
     * @param $quote
     * @return false
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function extractCart($quote)
    {
        if (is_a($quote, SalesOrder::class)) {
            foreach ($quote->getAllItems() as $item) {
                // $quote->getAllVisibleItems will actually give us only 'configurable' items
                // ...when we COULD use with more data from 'simple' items
                // It might be less robust? Let's see how we all feel about this
                if ($item->getProduct()->getTypeId() == 'configurable') {
                    $this->logger->addInfo("Skipping configurable item: {$item->getId()}");
                } else {
                    $cartData['cart_contents'][] = $this->extractProduct($item);
                }
            }

            $cartData['cart_total'] = $quote->getGrandTotal();
            $cartData['email'] = $quote->getCustomerEmail();
            $cartData['cart_type'] = Pixlee::PLATFORM;
            $cartData['cart_total_quantity'] = (int) $quote->getData('total_qty_ordered');
            $cartData['billing_address'] = $this->extractAddress($quote->getBillingAddress());
            $cartData['shipping_address'] = $this->extractAddress($quote->getShippingAddress());
            $cartData['order_id'] = (int) $quote->getData('entity_id');
            $cartData['currency'] = $quote->getData('base_currency_code');
            $this->logger->addInfo($this->serializer->serialize($cartData));
            return $cartData;
        }

        return false;
    }

    /**
     * @param $address
     * @return string
     */
    public function extractAddress($address)
    {
        // 2016-03-21, Yunfan
        // Something went wonky with my caches, and it always asks me to 'update'
        // my address when I input it - this fixes whatever weird edge case I'm running
        // into right now, and shouldn't hurt the normal case
        if ($address === null) {
            $sortedAddress = [];
        } else {
            $sortedAddress = [
                'street'    => $address->getStreet(),
                'city'      => $address->getCity(),
                'state'     => $address->getRegion(),
                'country'   => $address->getCountryId(),
                'zipcode'   => $address->getPostcode()
            ];
        }
        $jsonAddress = $this->serializer->serialize($sortedAddress);
        return $jsonAddress ?: '{}';
    }

    /**
     * @param $store
     * @param $extraData
     * @return false|string
     */
    public function preparePayload($store, $extraData = [])
    {
        if ($payload = $this->getPixleeCookie()) {
            foreach ($extraData as $key => $value) {
                // Don't overwrite existing data.
                if (!isset($payload[$key])) {
                    $payload[$key] = $value;
                }
            }
            // Required key/value pairs not in the payload by default.
            $payload['API_KEY']= $this->apiConfig->getPrivateApiKey(ScopeInterface::SCOPE_STORES, $store->getId());
            $payload['distinct_user_hash'] = $payload['CURRENT_PIXLEE_USER_ID'];
            $payload['ecommerce_platform'] = Pixlee::PLATFORM;
            $payload['ecommerce_platform_version'] = $this->productMetadata->getVersion();
            $payload['version_hash'] = $this->pixlee->getExtensionVersion();
            $payload['region_code'] = $store->getCode();
            $serializedPayload = $this->serializer->serialize($payload);
            $this->logger->addInfo("Sending payload: " . $serializedPayload);
            return $serializedPayload;
        }
        $this->logger->addInfo("Analytics event not sent because the cookie wasn't found");
        return false;
    }

    /**
     * @return array|null
     */
    protected function getPixleeCookie()
    {
        $cookie = $this->cookieManager->get();
        if (isset($cookie)) {
            return $this->serializer->unserialize($cookie);
        }

        return false;
    }
}
