<?php
/**
 * Copyright © Emplifi, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Test\Integration\Model\Cart;

use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\ObjectManagerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Pixlee\Pixlee\Model\Cart;

class ExtractOrderItemTest extends TestCase
{
    protected const FIXTURE_INCREMENT_ID = '100000001';

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testExtractItemFromSimpleOrderLineItem(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $order = $this->loadOrderByIncrementId($objectManager, self::FIXTURE_INCREMENT_ID);

        $items = $order->getAllVisibleItems();
        $this->assertNotEmpty($items);

        /** @var OrderItem $item */
        $item = $items[0];
        $this->assertGreaterThan(0, (int) $item->getProductId());

        /** @var Cart $cart */
        $cart = $objectManager->get(Cart::class);
        $itemData = $cart->extractItem($item, $order->getOrderCurrency());

        $this->assertSame((int) $item->getProductId(), $itemData['product_id']);
        $this->assertSame('simple', $itemData['product_sku']);
        $this->assertSame(2, $itemData['quantity']);
        $this->assertSame('USD', $itemData['currency']);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order_configurable_product.php
     */
    public function testExtractItemFromConfigurableOrderLineItem(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $order = $this->loadOrderByIncrementId($objectManager, self::FIXTURE_INCREMENT_ID);

        $items = $order->getAllVisibleItems();
        $this->assertNotEmpty($items);

        /** @var OrderItem $item */
        $item = $items[0];
        $this->assertSame(Configurable::TYPE_CODE, $item->getProductType());

        $children = $item->getChildrenItems();
        $this->assertNotEmpty($children);
        $child = $children[0];

        /** @var Cart $cart */
        $cart = $objectManager->get(Cart::class);
        $itemData = $cart->extractItem($item, $order->getOrderCurrency());

        $this->assertSame((int) $item->getProductId(), $itemData['product_id']);
        $this->assertSame('configurable', $itemData['product_sku']);
        $this->assertEquals((int) $child->getProductId(), $itemData['variant_id']);
        $this->assertSame($child->getSku(), $itemData['variant_sku']);
        $this->assertSame(2, $itemData['quantity']);
    }

    /**
     * @param ObjectManagerInterface $objectManager
     * @param string $incrementId
     * @return OrderInterface
     */
    private function loadOrderByIncrementId($objectManager, $incrementId)
    {
        /** @var OrderRepositoryInterface $orderRepository */
        $orderRepository = $objectManager->get(OrderRepositoryInterface::class);
        /** @var Order $order */
        $order = $objectManager->create(Order::class);
        $order->loadByIncrementId($incrementId);
        $this->assertNotEmpty($order->getId(), 'Order fixture must exist.');

        return $orderRepository->get((int) $order->getId());
    }
}
