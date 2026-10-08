<?php
/**
 * Copyright © Pixlee TurnTo, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Cron;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Pixlee\Pixlee\Model\Config\Product as ProductConfig;
use Pixlee\Pixlee\Model\Export\ExportPricingCacheResetInterface;
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
     * @var ProductConfig
     */
    protected $productConfig;

    /**
     * @var ExportPricingCacheResetInterface
     */
    protected $exportPricingCacheReset;

    /**
     * @param PixleeLogger $logger
     * @param Product $product
     * @param StoreManagerInterface $storeManager
     * @param ProductConfig $productConfig
     * @param ExportPricingCacheResetInterface $exportPricingCacheReset
     */
    public function __construct(
        PixleeLogger $logger,
        Product $product,
        StoreManagerInterface $storeManager,
        ProductConfig $productConfig,
        ExportPricingCacheResetInterface $exportPricingCacheReset
    ) {
        $this->logger = $logger;
        $this->product = $product;
        $this->storeManager = $storeManager;
        $this->productConfig = $productConfig;
        $this->exportPricingCacheReset = $exportPricingCacheReset;
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
            if ($this->productConfig->isCronEnabled(ScopeInterface::SCOPE_WEBSITES, $websiteId)) {
                $this->logger->info("Cron product export for website $websiteId started");
                $this->product->exportProducts($websiteId);
                $this->exportPricingCacheReset->reset();
                $this->logger->info("Cron product export for website $websiteId complete");
            }
        }
    }
}
