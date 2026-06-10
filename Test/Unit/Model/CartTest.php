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
