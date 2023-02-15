<?php
/**
 * Copyright © Pixlee TurnTo, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Cron;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Pixlee\Pixlee\Model\Logger\PixleeLogger;
use Pixlee\Pixlee\Model\Export\Product;

class ExportCron {
    /**
     * @var PixleeLogger
     */
    protected $logger;
    /**
     * @var Product
     */
    protected $product;

    public function __construct(
        PixleeLogger $logger,
        Product $product
    ) {
        $this->logger = $logger;
        $this->product = $product;
    }

    /**
     * Export products
     *
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute() {
        $this->logger->info('Exporting products from Cron Job');
        $this->product->exportProducts(1);
    }
}
