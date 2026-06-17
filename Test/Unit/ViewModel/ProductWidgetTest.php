<?php
/**
 * Copyright © Emplifi, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Test\Unit\ViewModel;

use Exception;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;
use Pixlee\Pixlee\Model\Config\Api;
use Pixlee\Pixlee\Model\Config\Widget;
use Pixlee\Pixlee\Test\Unit\AbstractUnitTestCase;
use Pixlee\Pixlee\ViewModel\ProductWidget;

class ProductWidgetTest extends AbstractUnitTestCase
{
    private const SCOPE = [
        'scopeType' => ScopeInterface::SCOPE_WEBSITES,
        'scopeCode' => 1,
    ];

    public function testIsActiveDelegatesToApiConfig(): void
    {
        $apiConfig = $this->createPassiveDouble(Api::class);
        $apiConfig->method('getScope')->with(1)->willReturn(self::SCOPE);
        $apiConfig->method('isActive')
            ->with(ScopeInterface::SCOPE_WEBSITES, 1)
            ->willReturn(true);

        $subject = $this->createSubject($apiConfig, $this->createPassiveDouble(Widget::class));

        $this->assertTrue($subject->isActive());
    }

    public function testGettersDelegateWithWebsiteScope(): void
    {
        $apiConfig = $this->createPassiveDouble(Api::class);
        $apiConfig->method('getScope')->willReturn(self::SCOPE);
        $apiConfig->method('getApiKey')
            ->with(ScopeInterface::SCOPE_WEBSITES, 1)
            ->willReturn('api-key');

        $widgetConfig = $this->createPassiveDouble(Widget::class);
        $widgetConfig->method('getAccountId')
            ->with(ScopeInterface::SCOPE_WEBSITES, 1)
            ->willReturn('acct-1');
        $widgetConfig->method('getCDPWidgetId')
            ->with(ScopeInterface::SCOPE_WEBSITES, 1)
            ->willReturn('cdp-1');
        $widgetConfig->method('getPDPWidgetId')
            ->with(ScopeInterface::SCOPE_WEBSITES, 1)
            ->willReturn('pdp-1');

        $subject = $this->createSubject($apiConfig, $widgetConfig);

        $this->assertSame('api-key', $subject->getApiKey());
        $this->assertSame('acct-1', $subject->getAccountId());
        $this->assertSame('cdp-1', $subject->getCDPWidgetId());
        $this->assertSame('pdp-1', $subject->getPDPWidgetId());
    }

    public function testGetScopeFallsBackToDefaultStoreWebsiteWhenGetWebsiteThrows(): void
    {
        $defaultStore = $this->createConfiguredPassiveDouble(Store::class, ['getWebsiteId' => 2]);

        $storeManager = $this->createPassiveDouble(StoreManagerInterface::class);
        $storeManager->method('getWebsite')->willThrowException(new Exception('no website'));
        $storeManager->method('getDefaultStoreView')->willReturn($defaultStore);

        $apiConfig = $this->createMock(Api::class);
        $apiConfig->expects($this->once())->method('getScope')->with(2)->willReturn([
            'scopeType' => ScopeInterface::SCOPE_WEBSITES,
            'scopeCode' => 2,
        ]);
        $apiConfig->method('isActive')->willReturn(false);

        $subject = new ProductWidget(
            $apiConfig,
            $this->createPassiveDouble(Widget::class),
            $storeManager
        );

        $this->assertFalse($subject->isActive());
    }

    private function createSubject(Api $apiConfig, Widget $widgetConfig): ProductWidget
    {
        $website = $this->createConfiguredPassiveDouble(Website::class, ['getId' => 1]);
        $storeManager = $this->createPassiveDouble(StoreManagerInterface::class);
        $storeManager->method('getWebsite')->willReturn($website);

        return new ProductWidget($apiConfig, $widgetConfig, $storeManager);
    }
}
