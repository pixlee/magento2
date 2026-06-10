<?php
/**
 * Copyright © Emplifi, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Test\Unit\Observer;

use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Pixlee\Pixlee\Api\AnalyticsServiceInterface;
use Pixlee\Pixlee\Model\Cart;
use Pixlee\Pixlee\Model\Config\Api;
use Pixlee\Pixlee\Model\Logger\PixleeLogger;
use Pixlee\Pixlee\Observer\CheckoutSuccessObserver;

class CheckoutSuccessObserverTest extends TestCase
{
    public function testGetOrdersReturnsSingleOrderFromEvent(): void
    {
        $order = $this->createMock(OrderInterface::class);
        $event = new Event(['order' => $order]);

        $subject = $this->createSubject(
            $this->createMock(Api::class),
            $this->createMock(Cart::class),
            $this->createMock(AnalyticsServiceInterface::class)
        );

        $orders = $subject->getOrders($event);

        $this->assertSame([$order], $orders);
    }

    public function testGetOrdersLoadsOrdersFromCollectionWhenOrderIdsPresent(): void
    {
        $collection = $this->createMock(Collection::class);
        $collection->expects($this->once())
            ->method('addFieldToFilter')
            ->with('entity_id', ['in' => [10, 11]])
            ->willReturnSelf();

        $subject = new CheckoutSuccessObserver(
            $collection,
            $this->createMock(PixleeLogger::class),
            $this->createMock(StoreManagerInterface::class),
            $this->createMock(Cart::class),
            $this->createMock(Api::class),
            $this->createMock(AnalyticsServiceInterface::class)
        );

        $event = new Event(['order_ids' => [10, 11]]);
        $this->assertSame($collection, $subject->getOrders($event));
    }

    public function testExecuteSendsConversionEventForEachOrder(): void
    {
        $store = $this->createConfiguredMock(Store::class, ['getCode' => 'default']);
        $payload = ['order_id' => 100];

        /** @var Order&MockObject $order */
        $order = $this->createMock(Order::class);
        $order->method('getStore')->willReturn($store);

        $apiConfig = $this->createMock(Api::class);
        $apiConfig->method('isActive')
            ->with(ScopeInterface::SCOPE_STORES, 'default')
            ->willReturn(true);

        $cart = $this->createMock(Cart::class);
        $cart->expects($this->once())
            ->method('getConversionPayload')
            ->with($order)
            ->willReturn($payload);

        $analytics = $this->createMock(AnalyticsServiceInterface::class);
        $analytics->expects($this->once())
            ->method('sendEvent')
            ->with('checkoutSuccess', $payload, $store)
            ->willReturn(true);

        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        $subject = new CheckoutSuccessObserver(
            $this->createMock(Collection::class),
            $this->createMock(PixleeLogger::class),
            $storeManager,
            $cart,
            $apiConfig,
            $analytics
        );

        $event = new Event(['order' => $order]);
        $subject->execute(new Observer(['event' => $event]));
    }

    public function testExecuteSkipsWhenPixleeInactive(): void
    {
        $apiConfig = $this->createMock(Api::class);
        $apiConfig->method('isActive')->willReturn(false);

        $cart = $this->createMock(Cart::class);
        $cart->expects($this->never())->method('getConversionPayload');

        $analytics = $this->createMock(AnalyticsServiceInterface::class);
        $analytics->expects($this->never())->method('sendEvent');

        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn(
            $this->createConfiguredMock(Store::class, ['getCode' => 'default'])
        );

        $subject = $this->createSubject($apiConfig, $cart, $analytics);
        $event = new Event(['order' => $this->createMock(OrderInterface::class)]);
        $subject->execute(new Observer(['event' => $event]));
    }

    private function createSubject(
        Api $apiConfig,
        Cart $cart,
        AnalyticsServiceInterface $analytics
    ): CheckoutSuccessObserver {
        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn(
            $this->createConfiguredMock(Store::class, ['getCode' => 'default'])
        );

        return new CheckoutSuccessObserver(
            $this->createMock(Collection::class),
            $this->createMock(PixleeLogger::class),
            $storeManager,
            $cart,
            $apiConfig,
            $analytics
        );
    }
}
