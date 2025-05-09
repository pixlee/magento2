<?php
/**
 * Copyright © Emplifi, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Model;

use Exception;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Directory\Model\Currency as DirectoryCurrency;
use Magento\Framework\Currency\Data\Currency;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Pixlee\Pixlee\Model\Logger\PixleeLogger;

class Cart
{
    /**
     * @var SerializerInterface
     */
    protected $serializer;
    /**
     * @var PixleeLogger
     */
    protected $logger;

    /**
     * @param SerializerInterface $serializer
     * @param PixleeLogger $logger
     */
    public function __construct(
        SerializerInterface $serializer,
        PixleeLogger $logger
    ) {
        $this->serializer = $serializer;
        $this->logger = $logger;
    }

    /**
     * @param $product
     * @return array
     */
    public function extractProduct($product)
    {
        $this->logger->addInfo("Cart extractProduct - ID: {$product->getId()}, SKU: {$product->getSku()}, type: {$product->getTypeId()}");
        $currency = $product->getStore()->getCurrentCurrency();
        $productData['product_id'] = (int) $product->getId();
        $productData['product_sku'] = $product->getData('sku');
        $productData['variant_id'] = (int) $product->getIdBySku($product->getSku());
        $productData['variant_sku'] = $product->getSku();
        $productData['price'] = $currency->format($this->getProductPrice($product), ['display' => Currency::NO_SYMBOL], false);
        $productData['quantity'] = (int) $product->getQty();
        $productData['currency'] = $currency->getCode();
        $this->logger->addInfo($this->serializer->serialize($productData));

        return $productData;
    }

    /**
     * @param $product
     * @return string|float
     */
    public function getProductPrice($product)
    {
        return $product->getQuoteItemPrice() ?: $product->getFinalPrice() ?: $product->getPrice();
    }

    /**
     * @param $order
     * @return array
     */
    public function getConversionPayload($order)
    {
        $this->logger->addInfo("Cart getConversionPayload - Order ID: {$order->getId()}}");
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

        return $cartData;
    }

    /**
     * @param OrderInterface $order
     * @return array
     */
    public function getCartContents($order)
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
        $itemData['product_sku'] = $item->getProduct()->getSku();
        $itemData['currency'] = $currency->getCode();

        return $itemData;
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
}
