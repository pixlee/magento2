<?php
/**
 * Copyright © Emplifi, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Test\Unit\Model;

use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Pixlee\Pixlee\Model\CookieManager;
use Pixlee\Pixlee\Test\Unit\AbstractUnitTestCase;

class CookieManagerTest extends AbstractUnitTestCase
{
    /** @var CookieManagerInterface&MockObject */
    private $cookieManager;

    protected function setUp(): void
    {
        $this->cookieManager = $this->createPassiveDouble(CookieManagerInterface::class);
    }

    public function testGetReturnsPrimaryCookieWhenPresent(): void
    {
        $this->cookieManager->method('getCookie')
            ->willReturnMap([
                [CookieManager::COOKIE_NAME, 'primary-cookie-value'],
                [CookieManager::LEGACY_COOKIE_NAME, 'legacy-cookie-value'],
            ]);

        $subject = $this->createSubject();

        $this->assertSame('primary-cookie-value', $subject->get());
    }

    public function testGetFallsBackToLegacyCookie(): void
    {
        $this->cookieManager->method('getCookie')
            ->willReturnMap([
                [CookieManager::COOKIE_NAME, null],
                [CookieManager::LEGACY_COOKIE_NAME, 'legacy-cookie-value'],
            ]);

        $subject = $this->createSubject();

        $this->assertSame('legacy-cookie-value', $subject->get());
    }

    public function testGetReturnsNullWhenNoCookiePresent(): void
    {
        $this->cookieManager->method('getCookie')->willReturn(null);

        $subject = $this->createSubject();

        $this->assertNull($subject->get());
    }

    private function createSubject(): CookieManager
    {
        return new CookieManager(
            $this->cookieManager,
            $this->createPassiveDouble(CookieMetadataFactory::class),
            $this->createPassiveDouble(SessionManagerInterface::class)
        );
    }
}
