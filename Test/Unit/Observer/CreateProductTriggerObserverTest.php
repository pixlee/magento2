<?php
/**
 * Copyright © Emplifi, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Test\Unit\Observer;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Pixlee\Pixlee\Model\Config\Api;
use Pixlee\Pixlee\Model\Export\Product as ProductExport;
use Pixlee\Pixlee\Model\Logger\PixleeLogger;
use Pixlee\Pixlee\Observer\CreateProductTriggerObserver;
use RuntimeException;

class CreateProductTriggerObserverTest extends TestCase
{
    public function testExecuteExportsProductForActiveEnabledWebsite(): void
    {
        $categoriesMap = ['1' => ['category_id' => 1]];
        $store = $this->createConfiguredMock(Store::class, ['getId' => 1]);
        $website = $this->createConfiguredMock(Website::class, [
            'getId' => 1,
            'getDefaultStore' => $store,
        ]);

        /** @var Product&MockObject $product */
        $product = $this->createMock(Product::class);
        $product->method('getWebsiteIds')->willReturn([1]);
        $product->method('getStatus')->willReturn(Status::STATUS_ENABLED);

        $productExport = $this->createMock(ProductExport::class);
        $productExport->method('getCategoriesMap')->willReturn($categoriesMap);
        $productExport->expects($this->once())
            ->method('exportProductToPixlee')
            ->with($product, $categoriesMap, 1, $store);

        $apiConfig = $this->createMock(Api::class);
        $apiConfig->method('isActive')
            ->with(ScopeInterface::SCOPE_WEBSITES, 1)
            ->willReturn(true);

        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getWebsite')->with(1)->willReturn($website);

        $subject = new CreateProductTriggerObserver(
            $productExport,
            $apiConfig,
            $this->createMock(PixleeLogger::class),
            $storeManager
        );

        $event = new Event(['product' => $product]);
        $subject->execute(new Observer(['event' => $event]));
    }

    public function testExecuteExportsWhenStatusIsStringFromEav(): void
    {
        $categoriesMap = ['1' => ['category_id' => 1]];
        $store = $this->createConfiguredMock(Store::class, ['getId' => 1]);
        $website = $this->createConfiguredMock(Website::class, [
            'getId' => 1,
            'getDefaultStore' => $store,
        ]);

        /** @var Product&MockObject $product */
        $product = $this->createMock(Product::class);
        $product->method('getWebsiteIds')->willReturn([1]);
        // EAV-loaded products often return status as a string from the database.
        $product->method('getStatus')->willReturn('1');

        $productExport = $this->createMock(ProductExport::class);
        $productExport->method('getCategoriesMap')->willReturn($categoriesMap);
        $productExport->expects($this->once())
            ->method('exportProductToPixlee')
            ->with($product, $categoriesMap, 1, $store);

        $apiConfig = $this->createMock(Api::class);
        $apiConfig->method('isActive')->willReturn(true);

        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getWebsite')->with(1)->willReturn($website);

        $subject = new CreateProductTriggerObserver(
            $productExport,
            $apiConfig,
            $this->createMock(PixleeLogger::class),
            $storeManager
        );

        $event = new Event(['product' => $product]);
        $subject->execute(new Observer(['event' => $event]));
    }

    public function testExecuteSkipsDisabledProduct(): void
    {
        /** @var Product&MockObject $product */
        $product = $this->createMock(Product::class);
        $product->method('getWebsiteIds')->willReturn([1]);
        $product->method('getStatus')->willReturn(Status::STATUS_DISABLED);

        $productExport = $this->createMock(ProductExport::class);
        $productExport->expects($this->never())->method('exportProductToPixlee');

        $apiConfig = $this->createMock(Api::class);
        $apiConfig->method('isActive')->willReturn(true);

        $subject = new CreateProductTriggerObserver(
            $productExport,
            $apiConfig,
            $this->createMock(PixleeLogger::class),
            $this->createMock(StoreManagerInterface::class)
        );

        $event = new Event(['product' => $product]);
        $subject->execute(new Observer(['event' => $event]));
    }

    public function testExecuteSkipsInactiveWebsite(): void
    {
        /** @var Product&MockObject $product */
        $product = $this->createMock(Product::class);
        $product->method('getWebsiteIds')->willReturn([1]);
        $product->method('getStatus')->willReturn(Status::STATUS_ENABLED);

        $productExport = $this->createMock(ProductExport::class);
        $productExport->expects($this->never())->method('exportProductToPixlee');

        $apiConfig = $this->createMock(Api::class);
        $apiConfig->method('isActive')->willReturn(false);

        $subject = new CreateProductTriggerObserver(
            $productExport,
            $apiConfig,
            $this->createMock(PixleeLogger::class),
            $this->createMock(StoreManagerInterface::class)
        );

        $event = new Event(['product' => $product]);
        $subject->execute(new Observer(['event' => $event]));
    }

    public function testExecuteLogsExceptionPerWebsite(): void
    {
        /** @var Product&MockObject $product */
        $product = $this->createMock(Product::class);
        $product->method('getWebsiteIds')->willReturn([1]);
        $product->method('getStatus')->willReturn(Status::STATUS_ENABLED);

        $productExport = $this->createMock(ProductExport::class);
        $productExport->method('getCategoriesMap')->willThrowException(new RuntimeException('export failed'));

        $apiConfig = $this->createMock(Api::class);
        $apiConfig->method('isActive')->willReturn(true);

        $logger = $this->createMock(PixleeLogger::class);
        $logger->expects($this->once())->method('error')->with('export failed');

        $subject = new CreateProductTriggerObserver(
            $productExport,
            $apiConfig,
            $logger,
            $this->createMock(StoreManagerInterface::class)
        );

        $event = new Event(['product' => $product]);
        $subject->execute(new Observer(['event' => $event]));
    }
}
