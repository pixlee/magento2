<?php
/**
 * Copyright © Emplifi, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Test\Integration\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Pixlee\Pixlee\Model\Config\Product;

class DefaultConfigTest extends TestCase
{
    public function testProductExportIsEnabledByDefault(): void
    {
        /** @var ScopeConfigInterface $scopeConfig */
        $scopeConfig = Bootstrap::getObjectManager()->get(ScopeConfigInterface::class);

        $this->assertSame(
            '1',
            $scopeConfig->getValue(Product::XML_PATH_PRODUCTS_EXPORT_ENABLED)
        );
    }
}
