<?php
/**
 * Copyright © Emplifi, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Test\Integration\Module;

use Magento\Framework\Module\ModuleList;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Pixlee\Pixlee\Model\Pixlee;

class ModuleRegistrationTest extends TestCase
{
    public function testModuleIsEnabled(): void
    {
        /** @var ModuleList $moduleList */
        $moduleList = Bootstrap::getObjectManager()->get(ModuleList::class);

        $this->assertTrue(
            $moduleList->has(Pixlee::MODULE_NAME),
            'Pixlee_Pixlee must be enabled for integration tests.'
        );
    }
}
