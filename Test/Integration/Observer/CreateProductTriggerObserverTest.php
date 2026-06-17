<?php
/**
 * Copyright © Emplifi, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Test\Integration\Observer;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Pixlee\Pixlee\Model\Export\Product as ProductExport;
use Pixlee\Pixlee\Observer\CreateProductTriggerObserver;
use Pixlee\Pixlee\Test\Integration\Model\Export\PixleeServiceSpy;

class CreateProductTriggerObserverTest extends TestCase
{
    /**
     * Product save observer must export enabled products when Pixlee is active.
     *
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     * @magentoConfigFixture base_website pixlee_pixlee/existing_customers/account_settings/active 1
     */
    public function testExecuteExportsEnabledProductForActiveWebsite(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $spy = new PixleeServiceSpy();

        /** @var ProductExport $export */
        $export = $objectManager->create(ProductExport::class, ['pixleeService' => $spy]);

        /** @var CreateProductTriggerObserver $observer */
        $observer = $objectManager->create(CreateProductTriggerObserver::class, [
            'productExport' => $export,
        ]);

        /** @var ProductRepositoryInterface $repository */
        $repository = $objectManager->get(ProductRepositoryInterface::class);
        $product = $repository->get('simple');

        $event = new Event(['product' => $product]);
        $observer->execute(new Observer(['event' => $event]));

        $this->assertCount(1, $spy->createdProducts);
        $this->assertSame('simple', $spy->createdProducts[0]['sku']);
        $this->assertSame('Simple Product', $spy->createdProducts[0]['name']);
    }
}
