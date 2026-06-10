<?php
/**
 * Copyright © Emplifi, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Test\Unit\Observer;

use Magento\Directory\Model\Currency;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Pixlee\Pixlee\Api\AnalyticsServiceInterface;
use Pixlee\Pixlee\Model\Cart;
use Pixlee\Pixlee\Model\Config\Api;
use Pixlee\Pixlee\Model\Logger\PixleeLogger;
use Pixlee\Pixlee\Observer\AddToCartObserver;

class AddToCartObserverTest extends TestCase
{
    public function testExecuteSendsAnalyticsEventWhenActive(): void
    {
        $itemData = ['product_sku' => 'simple', 'price' => '10.00'];
        $store = $this->createConfiguredMock(Store::class, ['getCode' => 'default']);
        $currency = $this->createMock(Currency::class);
        $store->method('getCurrentCurrency')->willReturn($currency);

        $quote = $this->createMock(Quote::class);
        $quote->method('getStore')->willReturn($store);

        /** @var QuoteItem&MockObject $quoteItem */
        $quoteItem = $this->createMock(QuoteItem::class);
        $quoteItem->method('getQuote')->willReturn($quote);

        $apiConfig = $this->createMock(Api::class);
        $apiConfig->method('isActive')
            ->with(ScopeInterface::SCOPE_STORES, 'default')
            ->willReturn(true);

        $cart = $this->createMock(Cart::class);
        $cart->expects($this->once())
            ->method('extractQuoteItem')
            ->with($quoteItem, $currency)
            ->willReturn($itemData);

        $analytics = $this->createMock(AnalyticsServiceInterface::class);
        $analytics->expects($this->once())
            ->method('sendEvent')
            ->with('addToCart', $itemData, $store)
            ->willReturn(true);

        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        $subject = new AddToCartObserver(
            $this->createMock(PixleeLogger::class),
            $storeManager,
            $cart,
            $apiConfig,
            $analytics
        );

        $event = new Event(['quote_item' => $quoteItem]);
        $subject->execute(new Observer(['event' => $event]));
    }

    public function testExecuteSkipsWhenQuoteItemMissing(): void
    {
        $store = $this->createConfiguredMock(Store::class, ['getCode' => 'default']);

        $apiConfig = $this->createMock(Api::class);
        $apiConfig->method('isActive')->willReturn(true);

        $cart = $this->createMock(Cart::class);
        $cart->expects($this->never())->method('extractQuoteItem');

        $analytics = $this->createMock(AnalyticsServiceInterface::class);
        $analytics->expects($this->never())->method('sendEvent');

        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        $subject = new AddToCartObserver(
            $this->createMock(PixleeLogger::class),
            $storeManager,
            $cart,
            $apiConfig,
            $analytics
        );

        $event = new Event([]);
        $subject->execute(new Observer(['event' => $event]));
    }

    public function testExecuteSkipsWhenPixleeInactive(): void
    {
        $store = $this->createConfiguredMock(Store::class, ['getCode' => 'default']);

        $apiConfig = $this->createMock(Api::class);
        $apiConfig->method('isActive')->willReturn(false);

        $cart = $this->createMock(Cart::class);
        $cart->expects($this->never())->method('extractQuoteItem');

        $analytics = $this->createMock(AnalyticsServiceInterface::class);
        $analytics->expects($this->never())->method('sendEvent');

        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        $subject = new AddToCartObserver(
            $this->createMock(PixleeLogger::class),
            $storeManager,
            $cart,
            $apiConfig,
            $analytics
        );

        $event = new Event(['quote_item' => $this->createMock(QuoteItem::class)]);
        $subject->execute(new Observer(['event' => $event]));
    }

    public function testExecuteLogsExceptionWithoutRethrowing(): void
    {
        $store = $this->createConfiguredMock(Store::class, ['getCode' => 'default']);
        $currency = $this->createMock(Currency::class);
        $store->method('getCurrentCurrency')->willReturn($currency);

        $quote = $this->createMock(Quote::class);
        $quote->method('getStore')->willReturn($store);

        $quoteItem = $this->createMock(QuoteItem::class);
        $quoteItem->method('getQuote')->willReturn($quote);

        $apiConfig = $this->createMock(Api::class);
        $apiConfig->method('isActive')->willReturn(true);

        $cart = $this->createMock(Cart::class);
        $cart->method('extractQuoteItem')->willThrowException(new \RuntimeException('boom'));

        $logger = $this->createMock(PixleeLogger::class);
        $logger->expects($this->once())->method('error');

        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        $subject = new AddToCartObserver(
            $logger,
            $storeManager,
            $cart,
            $apiConfig,
            $this->createMock(AnalyticsServiceInterface::class)
        );

        $event = new Event(['quote_item' => $quoteItem]);
        $subject->execute(new Observer(['event' => $event]));
    }
}
