<?php
/**
 * Copyright © Emplifi, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Test\Unit\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Directory\Model\Currency;
use Magento\Framework\Currency\Data\Currency as FrameworkCurrency;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Locale\FormatInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Serialize\Serializer\Json as MagentoJson;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Quote\Model\Quote\Item\Compare;
use Magento\Quote\Model\Quote\Item\Option\Comparator;
use Magento\Quote\Model\Quote\Item\OptionFactory;
use Magento\Framework\Serialize\Serializer\Json as SerializerJson;
use Magento\Sales\Model\Order\Address;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Sales\Model\OrderFactory as SalesOrderFactory;
use Magento\Sales\Model\Status\ListFactory;
use Magento\Sales\Model\Status\ListStatus;
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

    /** @var ObjectManager */
    private $objectManagerHelper;

    protected function setUp(): void
    {
        $logger = $this->createMock(PixleeLogger::class);
        $this->productRepository = $this->createMock(ProductRepositoryInterface::class);
        $this->cart = new Cart(new Json(), $logger, $this->productRepository);
        $this->objectManagerHelper = new ObjectManager($this);
    }

    public function testExtractQuoteItemQuantityIsInteger(): void
    {
        $currency = $this->createMock(Currency::class);
        $currency->method('format')->willReturn('10.00');
        $currency->method('getCode')->willReturn('USD');

        $item = $this->createQuoteItemStub('simple', [], 3.0, 10.0, 1, 'simple', 'simple');

        $itemData = $this->cart->extractQuoteItem($item, $currency);

        $this->assertSame(1, (int) $item->getProductId());
        $this->assertSame(3, $itemData['quantity']);
        $this->assertIsInt($itemData['quantity']);
        $this->assertSame(1, $itemData['product_id']);
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

    public function testExtractQuoteItemFallsBackToProductFinalPriceWhenQuotePriceUnset(): void
    {
        $currency = $this->createMock(Currency::class);
        $currency->expects($this->once())
            ->method('format')
            ->with(25.0, ['display' => FrameworkCurrency::NO_SYMBOL], false)
            ->willReturn('25.00');
        $currency->method('getCode')->willReturn('USD');

        $product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getSku', 'getTypeId', 'setFinalPrice', 'getFinalPrice', 'getPrice'])
            ->getMock();
        $product->method('getSku')->willReturn('simple');
        $product->method('getTypeId')->willReturn('simple');
        $product->method('setFinalPrice')->willReturnSelf();
        $product->method('getFinalPrice')->with(2.0)->willReturn(25.0);
        $product->method('getPrice')->willReturn(30.0);

        $item = $this->createQuoteItemInstance();
        $item->setData('product_type', 'simple');
        $item->setData('qty', 2.0);
        $item->setData('price', null);
        $item->setData('product_id', 1);
        $item->setSku('simple');
        $item->setData('product', $product);

        $itemData = $this->cart->extractQuoteItem($item, $currency);

        $this->assertSame('25.00', $itemData['price']);
        $this->assertSame(2, $itemData['quantity']);
    }

    public function testExtractQuoteItemFallsBackToChildProductFinalPriceForConfigurable(): void
    {
        $currency = $this->createMock(Currency::class);
        $currency->expects($this->once())
            ->method('format')
            ->with(49.99, ['display' => FrameworkCurrency::NO_SYMBOL], false)
            ->willReturn('49.99');
        $currency->method('getCode')->willReturn('USD');

        $childProduct = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getSku', 'getTypeId', 'setFinalPrice', 'getFinalPrice', 'getPrice'])
            ->getMock();
        $childProduct->method('getSku')->willReturn('simple-red');
        $childProduct->method('getTypeId')->willReturn('simple');
        $childProduct->method('setFinalPrice')->willReturnSelf();
        $childProduct->method('getFinalPrice')->with(1.0)->willReturn(49.99);
        $childProduct->method('getPrice')->willReturn(55.0);

        $parentProduct = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getSku', 'getTypeId', 'setFinalPrice', 'getFinalPrice', 'getPrice'])
            ->getMock();
        $parentProduct->method('getSku')->willReturn('configurable-parent');
        $parentProduct->method('getTypeId')->willReturn(Configurable::TYPE_CODE);
        $parentProduct->method('setFinalPrice')->willReturnSelf();
        $parentProduct->method('getFinalPrice')->with(1.0)->willReturn(0.0);
        $parentProduct->method('getPrice')->willReturn(0.0);

        $childItem = $this->createQuoteItemChildStub(42, 'simple-red');
        $childItem->setData('qty', 1.0);
        $childItem->setData('price', null);
        $childItem->setData('product', $childProduct);

        $item = $this->createQuoteItemInstance();
        $item->setData('product_type', Configurable::TYPE_CODE);
        $item->setData('qty', 1.0);
        $item->setData('price', null);
        $item->setData('product_id', 10);
        $item->setSku('configurable-parent');
        $item->setData('product', $parentProduct);
        $item->addChild($childItem);
        $this->mockConfigurableParentSku(10, 'configurable-parent');

        $itemData = $this->cart->extractQuoteItem($item, $currency);

        $this->assertSame('49.99', $itemData['price']);
        $this->assertSame(42, $itemData['variant_id']);
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
     * Build a real QuoteItem with data fields (product_id via magic getter, not a custom stub method).
     *
     * @param string $productType
     * @param QuoteItem[] $children
     * @param float $qty
     * @param float $price
     * @param int $productId
     * @param string $sku
     * @param string $productSku
     * @return QuoteItem
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
        $product = $this->createQuoteItemProductMock($productType, $productSku);

        $item = $this->createQuoteItemInstance();
        $item->setData('product_type', $productType);
        $item->setData('qty', $qty);
        $item->setData('price', $price);
        $item->setData('product_id', $productId);
        $item->setSku($sku);
        $item->setData('product', $product);

        foreach ($children as $child) {
            $item->addChild($child);
        }

        return $item;
    }

    /**
     * @param int $productId
     * @param string $sku
     * @return QuoteItem
     */
    private function createQuoteItemChildStub($productId, $sku)
    {
        $child = $this->createQuoteItemInstance();
        $child->setData('product_id', $productId);
        $child->setSku($sku);

        return $child;
    }

    /**
     * Product mock compatible with QuoteItem::getProduct() (calls setFinalPrice).
     *
     * @param string $productType
     * @param string $productSku
     * @return Product&MockObject
     */
    private function createQuoteItemProductMock($productType, $productSku)
    {
        /** @var Product&MockObject $product */
        $product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getSku', 'getTypeId', 'setFinalPrice'])
            ->getMock();
        $product->method('getSku')->willReturn($productSku);
        $product->method('getTypeId')->willReturn($productType);
        $product->method('setFinalPrice')->willReturnSelf();

        return $product;
    }

    /**
     * @return QuoteItem
     */
    private function createQuoteItemInstance()
    {
        $modelContext = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getEventDispatcher'])
            ->getMock();
        $modelContext->method('getEventDispatcher')->willReturn($this->createMock(ManagerInterface::class));

        $errorInfos = $this->getMockBuilder(ListStatus::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['clear', 'addItem', 'getItems', 'removeItemsByParams'])
            ->getMock();

        $statusListFactory = $this->getMockBuilder(ListFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $statusListFactory->method('create')->willReturn($errorInfos);

        $itemOptionFactory = $this->getMockBuilder(OptionFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        return $this->objectManagerHelper->getObject(
            QuoteItem::class,
            [
                'localeFormat' => $this->createMock(FormatInterface::class),
                'context' => $modelContext,
                'statusListFactory' => $statusListFactory,
                'itemOptionFactory' => $itemOptionFactory,
                'quoteItemCompare' => $this->createMock(Compare::class),
                'serializer' => $this->createMock(MagentoJson::class),
                'itemOptionComparator' => new Comparator(),
            ]
        );
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

        $item = $this->createOrderItemStub(
            'simple',
            [],
            2.0,
            10.0,
            1,
            'simple',
            'simple'
        );

        $itemData = $this->cart->extractItem($item, $currency);

        $this->assertSame(1, (int) $item->getProductId());
        $this->assertSame(2, $itemData['quantity']);
        $this->assertIsInt($itemData['quantity']);
        $this->assertSame(1, $itemData['product_id']);
    }

    public function testExtractItemUsesChildVariantForConfigurableItems(): void
    {
        $currency = $this->createMock(Currency::class);
        $currency->method('format')->willReturn('9.99');
        $currency->method('getCode')->willReturn('USD');

        $childItem = $this->createOrderItemChildStub(42, 'simple-red');
        $item = $this->createOrderItemStub(
            Configurable::TYPE_CODE,
            [$childItem],
            1.0,
            9.99,
            10,
            'configurable-parent',
            'configurable-parent'
        );
        $this->mockConfigurableParentSku(10, 'configurable-parent');

        $itemData = $this->cart->extractItem($item, $currency);

        $this->assertEquals(42, $itemData['variant_id']);
        $this->assertSame('simple-red', $itemData['variant_sku']);
        $this->assertSame('configurable-parent', $itemData['product_sku']);
        $this->assertSame('USD', $itemData['currency']);
    }

    /**
     * @param string $productType
     * @param OrderItem[] $children
     * @param float $qty
     * @param float $price
     * @param int $productId
     * @param string $sku
     * @param string $productSku
     * @return OrderItem
     */
    private function createOrderItemStub(
        $productType,
        array $children,
        $qty,
        $price,
        $productId,
        $sku,
        $productSku
    ) {
        $product = $this->createQuoteItemProductMock($productType, $productSku);

        $item = $this->createOrderItemInstance();
        $item->setData('product_type', $productType);
        $item->setData('qty_ordered', $qty);
        $item->setData('price', $price);
        $item->setData('product_id', $productId);
        $item->setSku($sku);
        $item->setData('product', $product);

        foreach ($children as $child) {
            $item->addChildItem($child);
        }

        return $item;
    }

    /**
     * @param int $productId
     * @param string $sku
     * @return OrderItem
     */
    private function createOrderItemChildStub($productId, $sku)
    {
        $child = $this->createOrderItemInstance();
        $child->setData('product_id', $productId);
        $child->setSku($sku);

        return $child;
    }

    /**
     * @return OrderItem
     */
    private function createOrderItemInstance()
    {
        $orderFactory = $this->createPartialMock(SalesOrderFactory::class, ['create']);

        return $this->objectManagerHelper->getObject(
            OrderItem::class,
            [
                'orderFactory' => $orderFactory,
                'serializer' => $this->createMock(SerializerJson::class),
            ]
        );
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
