<?php
/**
 * Copyright © Emplifi, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Test\Integration\Model\Cart;

use Magento\Framework\Serialize\SerializerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Pixlee\Pixlee\Model\Cart;
use Pixlee\Pixlee\Model\Pixlee;

class ConversionPayloadTest extends TestCase
{
    protected const FIXTURE_INCREMENT_ID = '100000001';

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testGetConversionPayloadIncludesExpectedFields(): void
    {
        $objectManager = Bootstrap::getObjectManager();

        /** @var OrderRepositoryInterface $orderRepository */
        $orderRepository = $objectManager->get(OrderRepositoryInterface::class);
        /** @var Order $order */
        $order = $orderRepository->get($this->getOrderIdByIncrementId(self::FIXTURE_INCREMENT_ID));

        /** @var Cart $cart */
        $cart = $objectManager->get(Cart::class);
        $payload = $cart->getConversionPayload($order);

        $this->assertSame(Pixlee::PLATFORM, $payload['cart_type']);
        $this->assertSame('customer@example.com', $payload['email']);
        $this->assertSame('USD', $payload['currency']);
        $this->assertIsArray($payload['cart_contents']);
        $this->assertNotEmpty($payload['cart_contents']);

        $lineItem = $payload['cart_contents'][0];
        $this->assertSame('simple', $lineItem['product_sku']);
        $this->assertSame(2, $lineItem['quantity']);

        /** @var SerializerInterface $serializer */
        $serializer = $objectManager->get(SerializerInterface::class);
        $billingAddress = $serializer->unserialize($payload['billing_address']);
        $this->assertSame('Los Angeles', $billingAddress['city']);
    }

    protected function getOrderIdByIncrementId(string $incrementId): int
    {
        $objectManager = Bootstrap::getObjectManager();
        /** @var Order $order */
        $order = $objectManager->create(Order::class);
        $order->loadByIncrementId($incrementId);

        $this->assertNotEmpty($order->getId(), 'Order fixture must exist.');

        return (int) $order->getId();
    }
}
