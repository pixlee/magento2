<?php
/**
 * Copyright © Emplifi, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Test\Integration\Model\Cart;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Pixlee\Pixlee\Model\Cart;

class ExtractQuoteItemTest extends TestCase
{
    protected function tearDown(): void
    {
        Bootstrap::getObjectManager()->removeSharedInstance(CheckoutSession::class);
        parent::tearDown();
    }

    /**
     * @magentoDataFixture Magento/Checkout/_files/quote_with_simple_product.php
     */
    public function testExtractQuoteItemFromSimpleQuoteLineItem(): void
    {
        $objectManager = Bootstrap::getObjectManager();

        /** @var CheckoutSession $session */
        $session = $objectManager->create(CheckoutSession::class);
        $quote = $session->getQuote();

        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = $objectManager->get(ProductRepositoryInterface::class);
        $product = $productRepository->get('simple');

        $item = $this->getQuoteItemByProductId($quote, (int) $product->getId());
        $this->assertInstanceOf(QuoteItem::class, $item);
        $this->assertSame((int) $product->getId(), (int) $item->getProductId());

        /** @var Cart $cart */
        $cart = $objectManager->get(Cart::class);
        $itemData = $cart->extractQuoteItem($item, $quote->getStore()->getCurrentCurrency());

        $this->assertSame((int) $item->getProductId(), $itemData['product_id']);
        $this->assertSame('simple', $itemData['product_sku']);
        $this->assertSame(1, $itemData['quantity']);
        $this->assertSame('USD', $itemData['currency']);
    }

    /**
     * @magentoDataFixture Magento/ConfigurableProduct/_files/quote_with_configurable_product.php
     */
    public function testExtractQuoteItemFromConfigurableQuoteLineItem(): void
    {
        $objectManager = Bootstrap::getObjectManager();

        /** @var Quote $quote */
        $quote = $objectManager->create(Quote::class)
            ->load('test_cart_with_configurable', 'reserved_order_id');

        $items = $quote->getAllVisibleItems();
        $this->assertNotEmpty($items);

        /** @var QuoteItem $item */
        $item = $items[0];
        $this->assertSame(Configurable::TYPE_CODE, $item->getProductType());
        $this->assertGreaterThan(0, (int) $item->getProductId());

        $children = $item->getChildren();
        $this->assertNotEmpty($children);
        $child = $children[0];

        /** @var Cart $cart */
        $cart = $objectManager->get(Cart::class);
        $itemData = $cart->extractQuoteItem($item, $quote->getStore()->getCurrentCurrency());

        $this->assertSame((int) $item->getProductId(), $itemData['product_id']);
        $this->assertSame('configurable', $itemData['product_sku']);
        $this->assertEquals((int) $child->getProductId(), $itemData['variant_id']);
        $this->assertSame($child->getSku(), $itemData['variant_sku']);
        $this->assertSame(1, $itemData['quantity']);
        $this->assertSame('USD', $itemData['currency']);
    }

    private function getQuoteItemByProductId(Quote $quote, int $productId): ?QuoteItem
    {
        foreach ($quote->getAllItems() as $item) {
            if ((int) $item->getProductId() === $productId) {
                return $item;
            }
        }

        return null;
    }
}
