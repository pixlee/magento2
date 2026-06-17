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
use Pixlee\Pixlee\Test\Unit\Helper\ObjectManager;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;
use Pixlee\Pixlee\Model\Config\Api;
use Pixlee\Pixlee\Model\Export\Product as ProductExport;
use Pixlee\Pixlee\Model\Logger\PixleeLogger;
use Pixlee\Pixlee\Observer\CreateProductTriggerObserver;
use Pixlee\Pixlee\Test\Unit\AbstractUnitTestCase;
use RuntimeException;

class CreateProductTriggerObserverTest extends AbstractUnitTestCase
{
    /** @var ObjectManager */
    private $objectManagerHelper;

    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManager($this);
    }

    public function testExecuteExportsProductForActiveEnabledWebsite(): void
    {
        $categoriesMap = ['1' => ['category_id' => 1]];
        $store = $this->createConfiguredPassiveDouble(Store::class, ['getId' => 1]);
        $website = $this->createConfiguredPassiveDouble(Website::class, [
            'getId' => 1,
            'getDefaultStore' => $store,
        ]);

        $product = $this->createProduct([1], Status::STATUS_ENABLED);

        $productExport = $this->createMock(ProductExport::class);
        $productExport->method('getCategoriesMap')->willReturn($categoriesMap);
        $productExport->expects($this->once())
            ->method('exportProductToPixlee')
            ->with($product, $categoriesMap, 1, $store);

        $apiConfig = $this->createPassiveDouble(Api::class);
        $apiConfig->method('isActive')
            ->with(ScopeInterface::SCOPE_WEBSITES, 1)
            ->willReturn(true);

        $storeManager = $this->createPassiveDouble(StoreManagerInterface::class);
        $storeManager->method('getWebsite')->with(1)->willReturn($website);

        $subject = new CreateProductTriggerObserver(
            $productExport,
            $apiConfig,
            $this->createPassiveDouble(PixleeLogger::class),
            $storeManager
        );

        $event = new Event(['product' => $product]);
        $subject->execute(new Observer(['event' => $event]));
    }

    public function testExecuteExportsWhenStatusIsStringFromEav(): void
    {
        $categoriesMap = ['1' => ['category_id' => 1]];
        $store = $this->createConfiguredPassiveDouble(Store::class, ['getId' => 1]);
        $website = $this->createConfiguredPassiveDouble(Website::class, [
            'getId' => 1,
            'getDefaultStore' => $store,
        ]);

        $product = $this->createProduct([1], '1');

        $productExport = $this->createMock(ProductExport::class);
        $productExport->method('getCategoriesMap')->willReturn($categoriesMap);
        $productExport->expects($this->once())
            ->method('exportProductToPixlee')
            ->with($product, $categoriesMap, 1, $store);

        $apiConfig = $this->createPassiveDouble(Api::class);
        $apiConfig->method('isActive')->willReturn(true);

        $storeManager = $this->createPassiveDouble(StoreManagerInterface::class);
        $storeManager->method('getWebsite')->with(1)->willReturn($website);

        $subject = new CreateProductTriggerObserver(
            $productExport,
            $apiConfig,
            $this->createPassiveDouble(PixleeLogger::class),
            $storeManager
        );

        $event = new Event(['product' => $product]);
        $subject->execute(new Observer(['event' => $event]));
    }

    public function testExecuteSkipsDisabledProduct(): void
    {
        $product = $this->createProduct([1], Status::STATUS_DISABLED);

        $productExport = $this->createMock(ProductExport::class);
        $productExport->expects($this->never())->method('exportProductToPixlee');

        $apiConfig = $this->createPassiveDouble(Api::class);
        $apiConfig->method('isActive')->willReturn(true);

        $subject = new CreateProductTriggerObserver(
            $productExport,
            $apiConfig,
            $this->createPassiveDouble(PixleeLogger::class),
            $this->createPassiveDouble(StoreManagerInterface::class)
        );

        $event = new Event(['product' => $product]);
        $subject->execute(new Observer(['event' => $event]));
    }

    public function testExecuteSkipsInactiveWebsite(): void
    {
        $product = $this->createProduct([1], Status::STATUS_ENABLED);

        $productExport = $this->createMock(ProductExport::class);
        $productExport->expects($this->never())->method('exportProductToPixlee');

        $apiConfig = $this->createPassiveDouble(Api::class);
        $apiConfig->method('isActive')->willReturn(false);

        $subject = new CreateProductTriggerObserver(
            $productExport,
            $apiConfig,
            $this->createPassiveDouble(PixleeLogger::class),
            $this->createPassiveDouble(StoreManagerInterface::class)
        );

        $event = new Event(['product' => $product]);
        $subject->execute(new Observer(['event' => $event]));
    }

    public function testExecuteLogsExceptionPerWebsite(): void
    {
        $product = $this->createProduct([1], Status::STATUS_ENABLED);

        $productExport = $this->createPassiveDouble(ProductExport::class);
        $productExport->method('getCategoriesMap')->willThrowException(new RuntimeException('export failed'));

        $apiConfig = $this->createPassiveDouble(Api::class);
        $apiConfig->method('isActive')->willReturn(true);

        $logger = $this->createMock(PixleeLogger::class);
        $logger->expects($this->once())->method('error')->with('export failed');

        $subject = new CreateProductTriggerObserver(
            $productExport,
            $apiConfig,
            $logger,
            $this->createPassiveDouble(StoreManagerInterface::class)
        );

        $event = new Event(['product' => $product]);
        $subject->execute(new Observer(['event' => $event]));
    }

    /**
     * @param int[] $websiteIds
     * @param int|string $status
     * @return Product
     */
    private function createProduct(array $websiteIds, $status)
    {
        /** @var Product $product */
        $product = $this->objectManagerHelper->getObject(Product::class);
        $product->setWebsiteIds($websiteIds);
        $product->setData('status', $status);

        return $product;
    }
}
