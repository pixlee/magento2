<?php
/**
 * Copyright © Emplifi, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Test\Integration\Cron;

use Magento\Cron\Model\Config as CronConfig;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Pixlee\Pixlee\Cron\ExportCron;

class ExportCronRegistrationTest extends TestCase
{
    /**
     * @magentoAppIsolation enabled
     */
    public function testProductExportCronJobIsRegistered(): void
    {
        /** @var CronConfig $cronConfig */
        $cronConfig = Bootstrap::getObjectManager()->get(CronConfig::class);
        $jobsByGroup = $cronConfig->getJobs();

        $this->assertArrayHasKey('default', $jobsByGroup);
        $this->assertArrayHasKey('pixlee_product_export', $jobsByGroup['default']);

        $job = $jobsByGroup['default']['pixlee_product_export'];
        $this->assertSame(ExportCron::class, $job['instance']);
        $this->assertSame('execute', $job['method']);
    }
}
