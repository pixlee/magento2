<?php
/**
 * Copyright © Emplifi, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
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
     * @return array|null Null when the line item product cannot be resolved.
     */
    public function extractQuoteItem(QuoteItem $item, $currency)
    {
        $product = $this->extractLineItemProduct($item);
        if ($product === null) {
            return null;
        }

        $itemData = $this->buildItemData(
            $item->getProductType(),
            $item->getChildren(),
            $item->getQty(),
            $this->resolveQuoteItemPrice($item),
            (int) $item->getProductId(),
            $product,
            $currency
        );
        $this->logger->info('Cart extractQuoteItem completed', $this->getLineItemLogContext($item));

        return $itemData;
    }

    /**
     * Get a conversion payload for a given order.
     *
     * @param OrderInterface $order
     * @return array
     */
    public function getConversionPayload($order)
    {
        $orderId = (int) $order->getData('entity_id');
        $this->logger->info('Cart getConversionPayload started', ['order_id' => $orderId]);
        $cartData['cart_contents'] = $this->getCartContents($order);
        $cartData['cart_total'] = $order->getGrandTotal();
        $cartData['email'] = $order->getCustomerEmail();
        $cartData['cart_type'] = Pixlee::PLATFORM;
        $cartData['cart_total_quantity'] = (int) $order->getData('total_qty_ordered');
        $cartData['billing_address'] = $this->extractAddress($order->getBillingAddress());
        $cartData['shipping_address'] = $this->extractAddress($order->getShippingAddress());
        $cartData['order_id'] = $orderId;
        $cartData['currency'] = $order->getData('base_currency_code');
        $this->logger->info('Cart getConversionPayload completed', [
            'order_id' => $orderId,
            'items_count' => count($cartData['cart_contents']),
            'cart_total_quantity' => $cartData['cart_total_quantity'],
            'cart_total' => $cartData['cart_total'],
        ]);

        return $cartData;
    }

    /**
     * Get cart contents for a given order.
     *
     * @param OrderInterface $order
     * @return array
     */
    public function getCartContents($order)
    {
        $cartContents = [];
        /** @var OrderItemInterface $item */
        foreach ($order->getAllVisibleItems() as $item) {
            $itemData = $this->extractItem($item, $order->getOrderCurrency());
            if ($itemData === null) {
                continue;
            }
            $cartContents[] = $itemData;
        }

        return $cartContents;
    }

    /**
     * Extract item data for Analytics API.
     *
     * @param OrderItemInterface $item
     * @param DirectoryCurrency $currency
     * @return array|null Null when the line item product cannot be resolved.
     */
    public function extractItem($item, $currency)
    {
        $product = $this->extractLineItemProduct($item);
        if ($product === null) {
            return null;
        }

        return $this->buildItemData(
            (string) $item->getProductType(),
            $item->getChildrenItems(),
            $item->getQtyOrdered(),
            $item->getPrice(),
            (int) $item->getProductId(),
            $product,
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
        if (is_numeric($price)) {
            return (float) $price;
        }

        $isConfigurable = $item->getProductType() === Configurable::TYPE_CODE;

        // Configurable parents are not directly purchasable; their catalog price is often zero.
        // Resolve from child line items first, then fall back to the parent catalog price.
        if (!$isConfigurable) {
            $product = $item->getProduct();
            if ($product) {
                $productPrice = $this->resolveProductPrice($product, $item->getQty());
                if ($productPrice !== null) {
                    return $productPrice;
                }
            }
        }

        foreach ($item->getChildren() as $child) {
            $childPrice = $child->getPrice();
            if (is_numeric($childPrice)) {
                return (float) $childPrice;
            }
            $childProduct = $child->getProduct();
            if ($childProduct) {
                $childProductPrice = $this->resolveProductPrice(
                    $childProduct,
                    $child->getQty()
                );
                if ($childProductPrice !== null) {
                    return $childProductPrice;
                }
            }
        }

        if ($isConfigurable) {
            $product = $item->getProduct();
            if ($product) {
                $productPrice = $this->resolveProductPrice($product, $item->getQty());
                if ($productPrice !== null) {
                    return $productPrice;
                }
            }
        }

        return $item->getPrice();
    }

    /**
     * Resolve the catalog product for a quote or order line item.
     *
     * Uses the attached product when present; otherwise loads by product_id.
     * Returns null when the line item has no resolvable product (missing ID, deleted catalog product, etc.).
     *
     * @param QuoteItem|OrderItemInterface $item
     * @return ProductInterface|null
     */
    protected function extractLineItemProduct($item): ?ProductInterface
    {
        // For configurable items, getProduct() may return the associated simple child instead of
        // the configurable parent. Always load by product_id for configurables so product_sku in
        // the analytics payload reflects the parent, not the variant.
        if ($item->getProductType() !== Configurable::TYPE_CODE) {
            $product = $item->getProduct();
            if ($product instanceof ProductInterface) {
                return $product;
            }
        }

        $productId = (int) $item->getProductId();
        if ($productId <= 0) {
            $this->logger->error('Cart line item has no resolvable product', array_merge(
                $this->getLineItemLogContext($item),
                ['reason' => 'missing_or_invalid_product_id']
            ));
            return null;
        }

        try {
            return $this->productRepository->getById($productId);
        } catch (NoSuchEntityException $e) {
            $this->logger->error('Cart line item product not found in catalog', array_merge(
                $this->getLineItemLogContext($item),
                ['exception' => $e]
            ));
            return null;
        }
    }

    /**
     * Build shared log context for quote and order line items.
     *
     * @param QuoteItem|OrderItemInterface $item
     * @return array
     */
    protected function getLineItemLogContext($item): array
    {
        return [
            'item_id' => $item->getItemId(),
            'product_id' => (int) $item->getProductId(),
            'sku' => $item->getSku(),
            'product_type' => $item->getProductType(),
        ];
    }

    /**
     * Resolve catalog product price: final price first, then base price.
     *
     * @param Product $product
     * @param float|int|null $qty
     * @return float|null
     */
    protected function resolveProductPrice(Product $product, $qty = null)
    {
        $finalPrice = $product->getFinalPrice($qty);
        if ($finalPrice !== null) {
            return $finalPrice;
        }

        $basePrice = $product->getPrice();
        if ($basePrice !== null) {
            return $basePrice;
        }

        return null;
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
            if (!isset($itemData['variant_id'])) {
                $this->logger->error('Cart configurable item has no child line items; variant data unavailable', [
                    'product_id' => $productId,
                ]);
            }
        }
        $itemData['quantity'] = (int) round((float) $quantity);
        $itemData['price'] = $currency->format($price, ['display' => Currency::NO_SYMBOL], false);
        $itemData['product_id'] = $productId;
        $itemData['product_sku'] = $product->getSku();
        $itemData['currency'] = $currency->getCode();

        return $itemData;
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
