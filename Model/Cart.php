<?php
/**
 * Copyright © Pixlee TurnTo, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Model;

use Exception;
use Magento\Bundle\Model\Product\Type as Bundle;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Directory\Model\Currency as DirectoryCurrency;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Currency\Data\Currency;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
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
     * @param PricingHelper $pricingHelper
     * @param StoreManagerInterface $storeManager
     * @param SerializerInterface $serializer
     * @param CookieManager $cookieManager
     * @param Api $apiConfig
     * @param PixleeLogger $logger
     * @param ProductMetadataInterface $productMetadata
     * @param Pixlee $pixlee
     */
    public function __construct(
        PricingHelper $pricingHelper,
        StoreManagerInterface $storeManager,
        SerializerInterface $serializer,
        CookieManager $cookieManager,
        Api $apiConfig,
        PixleeLogger $logger,
        ProductMetadataInterface $productMetadata,
        Pixlee $pixlee
    ) {
        $this->pricingHelper = $pricingHelper;
        $this->storeManager = $storeManager;
        $this->serializer = $serializer;
        $this->cookieManager = $cookieManager;
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
        $this->logger->addInfo("extractProduct - ID: {$product->getId()}, SKU: {$product->getSku()}, type: {$product->getTypeId()}");

        $productData = [];

        if ($product->getId()) {
            // Add to Cart and Remove from Cart
            $productData['product_id']    = (int) $product->getId();
            $productData['product_sku']   = $product->getData('sku');
            $productData['variant_id']    = (int) $product->getIdBySku($product->getSku());
            $productData['variant_sku']   = $product->getSku();
            // Get price in the main currency of the store. (USD, EUR, etc.)
            $productData['price']         = $this->pricingHelper->currency($product->getPrice(), true, false);
            $productData['quantity']      = (int) $product->getQty();
            $productData['currency']      = $this->storeManager->getStore()->getCurrentCurrency()->getCode();
        }

        if (empty($productData)) {
            $this->logger->error("Cart extractProduct ERROR - No product data found");
        }

        return $productData;
    }

    /**
     * @param OrderInterface $order
     * @return false|string
     */
    public function getConversionPayload($order)
    {
        $cartData['cart_contents'] = $this->getCartContents($order);
        $cartData['cart_total'] = $order->getGrandTotal();
        $cartData['email'] = $order->getCustomerEmail();
        $cartData['cart_type'] = Pixlee::PLATFORM;
        $cartData['cart_total_quantity'] = (int) $order->getData('total_qty_ordered');
        $cartData['billing_address'] = $this->extractAddress($order->getBillingAddress());
        $cartData['shipping_address'] = $this->extractAddress($order->getShippingAddress());
        $cartData['order_id'] = (int) $order->getData('entity_id');
        $cartData['currency'] = $order->getData('base_currency_code');
        $this->logger->addInfo($this->serializer->serialize($cartData));

        return $this->preparePayload($order->getStore(), $cartData);
    }

    /**
     * @param OrderInterface $order
     * @return array
     */
    protected function getCartContents($order)
    {
        $cartContents = [];
        /** @var OrderItemInterface $item */
        foreach ($order->getAllVisibleItems() as $item) {
            $cartContents[] = $this->extractItem($item, $order->getOrderCurrency());
        }

        return $cartContents;
    }

    /**
     * @param OrderItemInterface $item
     * @param DirectoryCurrency $currency
     * @return array
     */
    public function extractItem($item, $currency)
    {
        $itemData = [];
        if ($item->getProductType() === Configurable::TYPE_CODE) {
            foreach ($item->getChildrenItems() as $childItem) {
                $itemData['variant_id'] = $childItem->getProductId();
                $itemData['variant_sku'] = $childItem->getSku();
                break;
            }
        }
        $itemData['quantity'] = round((float)$item->getQtyOrdered());
        $itemData['price'] = $currency->format($item->getPrice(), ['display' => Currency::NO_SYMBOL], false);
        $itemData['product_id'] = $item->getProductId();
        $itemData['product_sku'] = $this->getItemSku($item);
        $itemData['currency'] = $currency->getCode();

        return $itemData;
    }

    /**
     * @param $item
     * @return string
     */
    public function getItemSku($item)
    {
        if ($item->getProductType() === Bundle::TYPE_CODE) {
            return $item->getProduct()->getSku();
        }
        return $item->getSku();
    }

    /**
     * @param $address
     * @return string The API expects a serialized string
     */
    public function extractAddress($address)
    {
        try {
            $sortedAddress = [
                'street'    => $address->getStreet(),
                'city'      => $address->getCity(),
                'state'     => $address->getRegion(),
                'country'   => $address->getCountryId(),
                'zipcode'   => $address->getPostcode()
            ];

            return $this->serializer->serialize($sortedAddress);
        } catch (Exception $e) {
            $this->logger->error("Cart extractAddress ERROR", ['exception' => $e]);
        }

        return '{}';
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
            $payload['API_KEY']= $this->apiConfig->getApiKey(ScopeInterface::SCOPE_STORES, $store->getId());
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
     * @return array|bool
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
