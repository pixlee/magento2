<?php
/**
 * Copyright © Pixlee TurnTo, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Cron;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Pixlee\Pixlee\Model\Export\Product;
use Pixlee\Pixlee\Model\Logger\PixleeLogger;

class ExportCron
{
    /**
     * @var PixleeLogger
     */
    protected $logger;
    /**
     * @var Product
     */
    protected $product;
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param PixleeLogger $logger
     * @param Product $product
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        PixleeLogger $logger,
        Product $product,
        StoreManagerInterface $storeManager
    ) {
        $this->logger = $logger;
        $this->product = $product;
        $this->storeManager = $storeManager;
    }

    /**
     * Export products
     *
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $websites = $this->storeManager->getWebsites();
        foreach ($websites as $website) {
            $websiteId = $website->getId();
            $this->logger->info("Cron product export for website $websiteId started");
            $this->product->exportProducts($websiteId);
            $this->logger->info("Cron product export for website $websiteId complete");
        }
    }
}
