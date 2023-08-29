<?php
/**
 * Copyright © Pixlee TurnTo, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Observer;

use Exception;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\ScopeInterface;
use Pixlee\Pixlee\Model\Config\Api;
use Pixlee\Pixlee\Model\Export\Product;
use Pixlee\Pixlee\Model\Logger\PixleeLogger;

class CreateProductTriggerObserver implements ObserverInterface
{
    /**
     * @var Product
     */
    protected $productExport;
    /**
     * @var Api
     */
    protected $apiConfig;
    /**
     * @var PixleeLogger
     */
    protected $logger;

    /**
     * @param Product $productExport
     * @param Api $apiConfig
     * @param PixleeLogger $logger
     */
    public function __construct(
        Product $productExport,
        Api $apiConfig,
        PixleeLogger $logger
    ) {
        $this->productExport = $productExport;
        $this->apiConfig = $apiConfig;
        $this->logger = $logger;
    }

    /**
     * @param EventObserver $observer
     * @return void
     */
    public function execute(EventObserver $observer)
    {
        /** @var \Magento\Catalog\Model\Product\Interceptor $product */
        $product = $observer->getEvent()->getProduct();
        $websiteIds = $product->getWebsiteIds();
        foreach ($websiteIds as $websiteId) {
            try {
                $pixleeEnabled = $this->apiConfig->isActive(ScopeInterface::SCOPE_WEBSITES, $websiteId);

                if ($pixleeEnabled && $product->getStatus() === Status::STATUS_ENABLED) {
                    $categoriesMap = $this->productExport->getCategoriesMap();
                    $this->productExport->exportProductToPixlee($product, $categoriesMap, $websiteId);
                }
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }
    }
}
