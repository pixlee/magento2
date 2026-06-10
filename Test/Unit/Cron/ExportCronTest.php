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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Pixlee\Pixlee\Cron\ExportCron;
use Pixlee\Pixlee\Model\Config\Product as ProductConfig;
use Pixlee\Pixlee\Model\Export\Product;
use Pixlee\Pixlee\Model\Logger\PixleeLogger;

class ExportCronTest extends TestCase
{
    public function testExecuteExportsOnlyWebsitesWithCronEnabled(): void
    {
        $websiteOne = $this->createConfiguredMock(Website::class, ['getId' => 1]);
        $websiteTwo = $this->createConfiguredMock(Website::class, ['getId' => 2]);

        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getWebsites')->willReturn([$websiteOne, $websiteTwo]);

        $productConfig = $this->createMock(ProductConfig::class);
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
        $website = $this->createConfiguredMock(Website::class, ['getId' => 1]);

        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getWebsites')->willReturn([$website]);

        $productConfig = $this->createMock(ProductConfig::class);
        $productConfig->method('isCronEnabled')->willReturn(false);

        $productExport = $this->createMock(Product::class);
        $productExport->expects($this->never())->method('exportProducts');

        $subject = new ExportCron(
            $this->createMock(PixleeLogger::class),
            $productExport,
            $storeManager,
            $productConfig
        );
        $subject->execute();
    }
}
