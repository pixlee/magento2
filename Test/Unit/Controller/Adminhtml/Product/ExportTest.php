<?php
/**
 * Copyright © Emplifi, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Test\Unit\Controller\Adminhtml\Product;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;
use PHPUnit\Framework\MockObject\MockObject;
use Pixlee\Pixlee\Controller\Adminhtml\Product\Export;
use Pixlee\Pixlee\Model\Export\Product;
use Pixlee\Pixlee\Test\Unit\AbstractUnitTestCase;

class ExportTest extends AbstractUnitTestCase
{
    public function testExecuteExportsSingleWebsiteWhenWebsiteIdProvided(): void
    {
        $exportedWebsiteIds = [];

        $productExport = $this->createMock(Product::class);
        $productExport->expects($this->once())
            ->method('exportProducts')
            ->willReturnCallback(function ($websiteId) use (&$exportedWebsiteIds): void {
                $exportedWebsiteIds[] = $websiteId;
            });

        $request = $this->createPassiveDouble(RequestInterface::class);
        $request->method('getParam')->with('website_id')->willReturn('3');

        $subject = $this->createSubject($productExport, $request, $this->createPassiveDouble(StoreManagerInterface::class));
        $subject->execute();

        $this->assertSame(['3'], $exportedWebsiteIds);
    }

    public function testExecuteExportsAllWebsitesWhenWebsiteIdMissing(): void
    {
        $websiteOne = $this->createConfiguredPassiveDouble(Website::class, ['getId' => 1]);
        $websiteTwo = $this->createConfiguredPassiveDouble(Website::class, ['getId' => 2]);

        $storeManager = $this->createPassiveDouble(StoreManagerInterface::class);
        $storeManager->method('getWebsites')->willReturn([$websiteOne, $websiteTwo]);

        $exportedWebsiteIds = [];

        $productExport = $this->createMock(Product::class);
        $productExport->expects($this->exactly(2))
            ->method('exportProducts')
            ->willReturnCallback(function ($websiteId) use (&$exportedWebsiteIds): void {
                $exportedWebsiteIds[] = $websiteId;
            });

        $request = $this->createPassiveDouble(RequestInterface::class);
        $request->method('getParam')->with('website_id')->willReturn(null);

        $subject = $this->createSubject($productExport, $request, $storeManager);
        $subject->execute();

        $this->assertSame([1, 2], $exportedWebsiteIds);
    }

    /**
     * @param Product&MockObject $productExport
     * @param RequestInterface&MockObject $request
     */
    private function createSubject(
        Product $productExport,
        RequestInterface $request,
        StoreManagerInterface $storeManager
    ): Export {
        $context = $this->createPassiveDouble(Context::class);
        $context->method('getRequest')->willReturn($request);

        return new Export($context, $productExport, $storeManager);
    }
}
