<?php
/**
 * Copyright © Emplifi, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Test\Integration\Model\Di;

use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Pixlee\Pixlee\Api\AnalyticsServiceInterface;
use Pixlee\Pixlee\Api\PixleeServiceInterface;
use Pixlee\Pixlee\Service\Analytics;
use Pixlee\Pixlee\Service\Distillery;

class ServicePreferencesTest extends TestCase
{
    public function testPixleeServiceInterfaceResolvesToDistillery(): void
    {
        $objectManager = Bootstrap::getObjectManager();

        $this->assertInstanceOf(
            Distillery::class,
            $objectManager->get(PixleeServiceInterface::class)
        );
    }

    public function testAnalyticsServiceInterfaceResolvesToAnalytics(): void
    {
        $objectManager = Bootstrap::getObjectManager();

        $this->assertInstanceOf(
            Analytics::class,
            $objectManager->get(AnalyticsServiceInterface::class)
        );
    }
}
