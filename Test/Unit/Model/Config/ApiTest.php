<?php
/**
 * Copyright © Emplifi, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Test\Unit\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\TestCase;
use Pixlee\Pixlee\Model\Config\Api;

class ApiTest extends TestCase
{
    public function testGetScopeReturnsDefaultWhenWebsiteIdEmpty(): void
    {
        $subject = new Api(
            $this->createMock(ScopeConfigInterface::class),
            $this->createMock(WriterInterface::class)
        );

        $scope = $subject->getScope(null);

        $this->assertSame(ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scope['scopeType']);
        $this->assertNull($scope['scopeCode']);
    }

    public function testGetScopeReturnsWebsiteScopeWhenWebsiteIdProvided(): void
    {
        $subject = new Api(
            $this->createMock(ScopeConfigInterface::class),
            $this->createMock(WriterInterface::class)
        );

        $scope = $subject->getScope(2);

        $this->assertSame(ScopeInterface::SCOPE_WEBSITES, $scope['scopeType']);
        $this->assertSame(2, $scope['scopeCode']);
    }
}
