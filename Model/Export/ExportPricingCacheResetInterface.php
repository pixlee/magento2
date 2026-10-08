<?php
/**
 * Copyright © Emplifi, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Model\Export;

/**
 * Releases Magento pricing caches retained across long-running export jobs.
 */
interface ExportPricingCacheResetInterface
{
    /**
     * Clear shared configurable-product pricing caches after one website export.
     *
     * @return void
     */
    public function reset(): void;
}
