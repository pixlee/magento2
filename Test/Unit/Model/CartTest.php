<?php
/**
 * Copyright © Emplifi, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Test\Unit\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Directory\Model\Currency;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Sales\Model\Order\Address;
use Magento\Sales\Model\Order\Item as OrderItem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Pixlee\Pixlee\Model\Cart;
use Pixlee\Pixlee\Model\Logger\PixleeLogger;

class CartTest extends TestCase
{
    /**
     * @var Cart
     */
    protected $cart;

    /** @var ProductRepositoryInterface&MockObject */
    private $productRepository;

    protected function setUp(): void
    {
        $logger = $this->createMock(PixleeLogger::class);
        $this->productRepository = $this->createMock(ProductRepositoryInterface::class);
        $this->cart = new Cart(new Json(), $logger, $this->productRepository);
    }

    public function testExtractQuoteItemQuantityIsInteger(): void
    {
        $currency = $this->createMock(Currency::class);
        $currency->method('format')->willReturn('10.00');
        $currency->method('getCode')->willReturn('USD');

        $item = $this->createQuoteItemStub('simple', [], 3.0, 10.0, 1, 'simple', 'simple');

        $itemData = $this->cart->extractQuoteItem($item, $currency);

        $this->assertSame(3, $itemData['quantity']);
        $this->assertIsInt($itemData['quantity']);
        $this->assertSame('simple', $itemData['product_sku']);
    }

    public function testExtractQuoteItemUsesChildVariantForConfigurableItems(): void
    {
        $currency = $this->createMock(Currency::class);
        $currency->method('format')->willReturn('9.99');
        $currency->method('getCode')->willReturn('USD');

        $childItem = $this->createQuoteItemChildStub(42, 'simple-red');
        $item = $this->createQuoteItemStub(
            Configurable::TYPE_CODE,
            [$childItem],
            1.0,
            9.99,
            10,
            'simple-red',
            'simple-red'
        );
        $this->mockConfigurableParentSku(10, 'configurable-parent');

        $itemData = $this->cart->extractQuoteItem($item, $currency);

        $this->assertSame(42, $itemData['variant_id']);
        $this->assertSame('simple-red', $itemData['variant_sku']);
        $this->assertSame('configurable-parent', $itemData['product_sku']);
        $this->assertSame('USD', $itemData['currency']);
    }

    public function testExtractQuoteItemUsesParentSkuWhenQuoteProductIsSelectedSimple(): void
    {
        $currency = $this->createMock(Currency::class);
        $currency->method('format')->willReturn('77.00');
        $currency->method('getCode')->willReturn('USD');

        $childItem = $this->createQuoteItemChildStub(1382, 'WJ12-XS-Blue');
        $item = $this->createQuoteItemStub(
            Configurable::TYPE_CODE,
            [$childItem],
            1.0,
            77.0,
            1396,
            'WJ12-XS-Blue',
            'WJ12-XS-Blue'
        );
        $this->mockConfigurableParentSku(1396, 'WJ12');

        $itemData = $this->cart->extractQuoteItem($item, $currency);

        $this->assertSame(1382, $itemData['variant_id']);
        $this->assertSame('WJ12-XS-Blue', $itemData['variant_sku']);
        $this->assertSame('WJ12', $itemData['product_sku']);
        $this->assertSame(1396, $itemData['product_id']);
    }

    /**
     * @param array $children
     */
    private function createQuoteItemStub(
        $productType,
        array $children,
        $qty,
        $price,
        $productId,
        $sku,
        $productSku
    ) {
        $product = $this->createConfiguredMock(ProductInterface::class, [
            'getSku' => $productSku,
        ]);

        return new class($productType, $children, $qty, $price, $productId, $sku, $product) extends QuoteItem {
            /** @var string */
            private $productType;
            /** @var array */
            private $children;
            /** @var float */
            private $qty;
            /** @var float */
            private $price;
            /** @var int */
            private $productId;
            /** @var string */
            private $sku;
            /** @var ProductInterface */
            private $product;

            public function __construct($productType, array $children, $qty, $price, $productId, $sku, ProductInterface $product)
            {
                $this->productType = $productType;
                $this->children = $children;
                $this->qty = $qty;
                $this->price = $price;
                $this->productId = $productId;
                $this->sku = $sku;
                $this->product = $product;
            }

            public function getProductType()
            {
                return $this->productType;
            }

            public function getChildren()
            {
                return $this->children;
            }

            public function getQty()
            {
                return $this->qty;
            }

            public function getPrice()
            {
                return $this->price;
            }

            public function getProductId()
            {
                return $this->productId;
            }

            public function getSku()
            {
                return $this->sku;
            }

            public function getProduct()
            {
                return $this->product;
            }
        };
    }

    private function createQuoteItemChildStub($productId, $sku)
    {
        return new class($productId, $sku) {
            /** @var int */
            private $productId;
            /** @var string */
            private $sku;

            public function __construct($productId, $sku)
            {
                $this->productId = $productId;
                $this->sku = $sku;
            }

            public function getProductId()
            {
                return $this->productId;
            }

            public function getSku()
            {
                return $this->sku;
            }
        };
    }

    public function testExtractAddressReturnsEmptyObjectWhenAddressIsNull(): void
    {
        $this->assertSame('{}', $this->cart->extractAddress(null));
    }

    public function testExtractAddressSerializesAddressFields(): void
    {
        /** @var Address&MockObject $address */
        $address = $this->createConfiguredMock(Address::class, [
            'getStreet' => ['123 Main St'],
            'getCity' => 'Los Angeles',
            'getRegion' => 'CA',
            'getCountryId' => 'US',
            'getPostcode' => '90001',
        ]);

        $serialized = $this->cart->extractAddress($address);
        $decoded = json_decode($serialized, true);

        $this->assertSame('Los Angeles', $decoded['city']);
        $this->assertSame('US', $decoded['country']);
    }

    public function testExtractItemQuantityIsInteger(): void
    {
        $currency = $this->createMock(Currency::class);
        $currency->method('format')->willReturn('10.00');
        $currency->method('getCode')->willReturn('USD');

        /** @var OrderItem&MockObject $item */
        $item = $this->getMockBuilder(OrderItem::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getProductType',
                'getChildrenItems',
                'getQtyOrdered',
                'getPrice',
                'getProductId',
                'getProduct',
            ])
            ->getMock();
        $item->method('getProductType')->willReturn('simple');
        $item->method('getChildrenItems')->willReturn([]);
        $item->method('getQtyOrdered')->willReturn(2.0);
        $item->method('getPrice')->willReturn(10.0);
        $item->method('getProductId')->willReturn(1);
        $parentProduct = $this->createConfiguredMock(ProductInterface::class, [
            'getSku' => 'simple',
        ]);
        $item->method('getProduct')->willReturn($parentProduct);

        $itemData = $this->cart->extractItem($item, $currency);

        $this->assertSame(2, $itemData['quantity']);
        $this->assertIsInt($itemData['quantity']);
    }

    public function testExtractItemUsesChildVariantForConfigurableItems(): void
    {
        $currency = $this->createMock(Currency::class);
        $currency->method('format')->willReturn('9.99');
        $currency->method('getCode')->willReturn('USD');

        $childItem = $this->createConfiguredMock(OrderItem::class, [
            'getProductId' => 42,
            'getSku' => 'simple-red',
        ]);

        /** @var OrderItem&MockObject $item */
        $item = $this->getMockBuilder(OrderItem::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getProductType',
                'getChildrenItems',
                'getQtyOrdered',
                'getPrice',
                'getProductId',
                'getProduct',
            ])
            ->getMock();
        $item->method('getProductType')->willReturn(Configurable::TYPE_CODE);
        $item->method('getChildrenItems')->willReturn([$childItem]);
        $item->method('getQtyOrdered')->willReturn(1.0);
        $item->method('getPrice')->willReturn(9.99);
        $item->method('getProductId')->willReturn(10);
        $parentProduct = $this->createConfiguredMock(ProductInterface::class, [
            'getSku' => 'configurable-parent',
        ]);
        $item->method('getProduct')->willReturn($parentProduct);
        $this->mockConfigurableParentSku(10, 'configurable-parent');

        $itemData = $this->cart->extractItem($item, $currency);

        $this->assertSame(42, $itemData['variant_id']);
        $this->assertSame('simple-red', $itemData['variant_sku']);
        $this->assertSame('configurable-parent', $itemData['product_sku']);
        $this->assertSame('USD', $itemData['currency']);
    }

    private function mockConfigurableParentSku(int $productId, string $sku): void
    {
        $parentProduct = $this->createConfiguredMock(ProductInterface::class, [
            'getSku' => $sku,
        ]);
        $this->productRepository->method('getById')
            ->with($productId)
            ->willReturn($parentProduct);
    }

}
