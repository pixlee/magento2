<?php
/**
 * Copyright © Emplifi, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Test\Unit\Cron;

use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;
use Pixlee\Pixlee\Cron\ExportCron;
use Pixlee\Pixlee\Model\Config\Product as ProductConfig;
use Pixlee\Pixlee\Model\Export\Product;
use Pixlee\Pixlee\Model\Logger\PixleeLogger;
use Pixlee\Pixlee\Test\Unit\AbstractUnitTestCase;

class ExportCronTest extends AbstractUnitTestCase
{
    public function testExecuteExportsOnlyWebsitesWithCronEnabled(): void
    {
        $websiteOne = $this->createConfiguredPassiveDouble(Website::class, ['getId' => 1]);
        $websiteTwo = $this->createConfiguredPassiveDouble(Website::class, ['getId' => 2]);

        $storeManager = $this->createPassiveDouble(StoreManagerInterface::class);
        $storeManager->method('getWebsites')->willReturn([$websiteOne, $websiteTwo]);

        $productConfig = $this->createPassiveDouble(ProductConfig::class);
        $productConfig->method('isCronEnabled')
            ->willReturnMap([
                [ScopeInterface::SCOPE_WEBSITES, 1, true],
                [ScopeInterface::SCOPE_WEBSITES, 2, false],
            ]);

        $exportedWebsiteIds = [];

        $productExport = $this->createMock(Product::class);
        $productExport->expects($this->once())
            ->method('exportProducts')
            ->willReturnCallback(function ($websiteId) use (&$exportedWebsiteIds): void {
                $exportedWebsiteIds[] = $websiteId;
            });

        $logger = $this->createMock(PixleeLogger::class);
        $logger->expects($this->exactly(2))->method('info');

        $subject = new ExportCron($logger, $productExport, $storeManager, $productConfig);
        $subject->execute();

        $this->assertSame([1], $exportedWebsiteIds);
    }

    public function testExecuteSkipsAllWebsitesWhenCronDisabled(): void
    {
        $website = $this->createConfiguredPassiveDouble(Website::class, ['getId' => 1]);

        $storeManager = $this->createPassiveDouble(StoreManagerInterface::class);
        $storeManager->method('getWebsites')->willReturn([$website]);

        $productConfig = $this->createPassiveDouble(ProductConfig::class);
        $productConfig->method('isCronEnabled')->willReturn(false);

        $productExport = $this->createMock(Product::class);
        $productExport->expects($this->never())->method('exportProducts');

        $subject = new ExportCron(
            $this->createPassiveDouble(PixleeLogger::class),
            $productExport,
            $storeManager,
            $productConfig
        );
        $subject->execute();
    }
}
