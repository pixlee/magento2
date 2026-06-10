<?php
/**
 * Copyright © Emplifi, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Test\Integration\Cron;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Indexer\Product\Price\Processor as PriceIndexerProcessor;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Pixlee\Pixlee\Cron\ExportCron;
use Pixlee\Pixlee\Model\Export\Product as ProductExport;
use Pixlee\Pixlee\Test\Integration\Model\Export\PixleeServiceSpy;

class ExportCronExecuteTest extends TestCase
{
    private const DEFAULT_WEBSITE_ID = 1;

    /**
     * Cron execute must drive the real export path when cron is enabled.
     *
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     * @magentoConfigFixture base_website pixlee_pixlee/existing_customers/account_settings/active 1
     * @magentoConfigFixture base_website pixlee_pixlee/products/export_enabled 1
     */
    public function testExecuteTriggersProductExportForEnabledWebsite(): void
    {
        $this->reindexProductPrice();

        $objectManager = Bootstrap::getObjectManager();
        $spy = new PixleeServiceSpy();

        /** @var ProductExport $export */
        $export = $objectManager->create(ProductExport::class, ['pixleeService' => $spy]);

        /** @var ExportCron $cron */
        $cron = $objectManager->create(ExportCron::class, ['product' => $export]);
        $cron->execute();

        $this->assertNotEmpty($spy->exportStatuses, 'Cron export should notify start/finish.');
        $this->assertNotEmpty($spy->createdProducts, 'Cron export should create at least one product.');

        $skus = array_column($spy->createdProducts, 'sku');
        $this->assertContains('simple', $skus);
    }

    private function reindexProductPrice(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        /** @var ProductRepositoryInterface $repository */
        $repository = $objectManager->get(ProductRepositoryInterface::class);
        $productId = (int) $repository->get('simple')->getId();

        $objectManager->get(PriceIndexerProcessor::class)
            ->getIndexer()
            ->reindexList([$productId]);
    }
}
