<?php
/**
 * Copyright © Pixlee TurnTo, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Observer;

use Exception;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Pixlee\Pixlee\Model\Config\Api;
use Pixlee\Pixlee\Model\Export\Product;
use Psr\Log\LoggerInterface;

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
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        Product $productExport,
        Api $apiConfig,
        LoggerInterface $logger
    ) {
        $this->productExport = $productExport;
        $this->apiConfig = $apiConfig;
        $this->logger = $logger;
    }

    public function execute(EventObserver $observer)
    {
        $product = $observer->getEvent()->getProduct();
        $websiteIds = $product->getWebsiteIds();
        foreach ($websiteIds as $websiteId) {
            try {
                $pixleeEnabled = $this->apiConfig->isActive($websiteId);

                if ($pixleeEnabled && $product->getStatus() == 1) {
                    $categoriesMap = $this->productExport->getCategoriesMap();
                    $this->productExport->exportProductToPixlee($product, $categoriesMap, $websiteId);
                }
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }
    }
}
