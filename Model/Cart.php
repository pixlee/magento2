<?php
/**
 * Copyright © Emplifi, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Directory\Model\Currency as DirectoryCurrency;
use Magento\Framework\Currency\Data\Currency;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Model\Order\Address;
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
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @param SerializerInterface $serializer
     * @param PixleeLogger $logger
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        SerializerInterface $serializer,
        PixleeLogger $logger,
        ProductRepositoryInterface $productRepository
    ) {
        $this->serializer = $serializer;
        $this->logger = $logger;
        $this->productRepository = $productRepository;
    }

    /**
     * Extract quote item data for add-to-cart analytics.
     *
     * @param QuoteItem $item
     * @param DirectoryCurrency $currency
     * @return array
     * @throws NoSuchEntityException
     */
    public function extractQuoteItem(QuoteItem $item, $currency)
    {
        $this->logger->addInfo(
            "Cart extractQuoteItem - ID: {$item->getProductId()}, SKU: {$item->getSku()},"
            . " type: {$item->getProductType()}"
        );
        $itemData = $this->buildItemData(
            $item->getProductType(),
            $item->getChildren(),
            $item->getQty(),
            $this->resolveQuoteItemPrice($item),
            (int) $item->getProductId(),
            $item->getProduct(),
            $currency
        );
        $this->logger->addInfo($this->serializer->serialize($itemData));

        return $itemData;
    }

    /**
     * Get a conversion payload for a given order.
     *
     * @param OrderInterface $order
     * @return array
     * @throws NoSuchEntityException
     */
    public function getConversionPayload($order)
    {
        $this->logger->addInfo("Cart getConversionPayload - Order ID: {$order->getId()}");
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
     * Get cart contents for a given order.
     *
     * @param OrderInterface $order
     * @return array
     * @throws NoSuchEntityException
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
     * Extract item data for Analytics API.
     *
     * @param OrderItemInterface $item
     * @param DirectoryCurrency $currency
     * @return array
     * @throws NoSuchEntityException
     */
    public function extractItem($item, $currency)
    {
        return $this->buildItemData(
            (string) $item->getProductType(),
            $item->getChildrenItems(),
            $item->getQtyOrdered(),
            $item->getPrice(),
            (int) $item->getProductId(),
            $item->getProduct(),
            $currency
        );
    }

    /**
     * Resolve quote item price which may not be set before collectTotals(); fall back to catalog pricing.
     *
     * @param QuoteItem $item
     * @return float|string|null
     */
    protected function resolveQuoteItemPrice(QuoteItem $item)
    {
        $price = $item->getPrice();
        if ($price) {
            return $price;
        }

        $product = $item->getProduct();
        if ($product) {
            $productPrice = $product->getFinalPrice($item->getQty()) ?: $product->getPrice();
            if ($productPrice) {
                return $productPrice;
            }
        }

        foreach ($item->getChildren() as $child) {
            $childPrice = $child->getPrice();
            if ($childPrice) {
                return $childPrice;
            }
            $childProduct = $child->getProduct();
            if ($childProduct) {
                $productPrice = $childProduct->getFinalPrice($child->getQty()) ?: $childProduct->getPrice();
                if ($productPrice) {
                    return $productPrice;
                }
            }
        }

        return $price;
    }

    /**
     * Build analytics payload fields shared by quote and order line items.
     *
     * @param string $productType
     * @param iterable $childItems
     * @param float|string $quantity
     * @param float|string $price
     * @param int $productId
     * @param ProductInterface $product
     * @param DirectoryCurrency $currency
     * @return array
     * @throws NoSuchEntityException
     */
    protected function buildItemData(
        string $productType,
        iterable $childItems,
        $quantity,
        $price,
        int $productId,
        ProductInterface $product,
        $currency
    ): array {
        $itemData = [];
        if ($productType === Configurable::TYPE_CODE) {
            foreach ($childItems as $childItem) {
                $itemData['variant_id'] = $childItem->getProductId();
                $itemData['variant_sku'] = $childItem->getSku();
                break;
            }
        }
        $itemData['quantity'] = (int) round((float) $quantity);
        $itemData['price'] = $currency->format($price, ['display' => Currency::NO_SYMBOL], false);
        $itemData['product_id'] = $productId;
        $itemData['product_sku'] = $this->resolveProductSku($productType, $productId, $product);
        $itemData['currency'] = $currency->getCode();

        return $itemData;
    }

    /**
     * Resolve catalog product SKU for analytics (parent SKU for configurables).
     *
     * @param string $productType
     * @param int $productId
     * @param ProductInterface $product
     * @return string
     * @throws NoSuchEntityException
     */
    protected function resolveProductSku(string $productType, int $productId, ProductInterface $product): string
    {
        if ($productType === Configurable::TYPE_CODE) {
            return $this->productRepository->getById($productId)->getSku();
        }

        return $product->getSku();
    }

    /**
     * Extract address data for Analytics API.
     *
     * @param OrderAddressInterface|Address|null $address
     * @return string The API expects a serialized string
     */
    public function extractAddress($address)
    {
        if ($address === null) {
            return '{}';
        }

        return $this->serializer->serialize([
            'street'  => $address->getStreet(),
            'city'    => $address->getCity(),
            'state'   => $address->getRegion(),
            'country' => $address->getCountryId(),
            'zipcode' => $address->getPostcode(),
        ]);
    }
}
