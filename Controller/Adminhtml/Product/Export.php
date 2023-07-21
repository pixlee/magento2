<?php
/**
 * Copyright © Pixlee TurnTo, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Controller\Adminhtml\Product;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Pixlee\Pixlee\Model\Export\Product;

class Export extends Action
{
    /**
     * @var Product
     */
    protected $product;
    /**
     * @var StoreManagerInterface
     */
    protected StoreManagerInterface $storeManager;

    /**
     * Constructor
     *
     * @param Context $context
     * @param Product $product
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        Product $product,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->product = $product;
        $this->storeManager = $storeManager;
    }

    /**
     * Export products to Pixlee
     *
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute(): void
    {
        $websiteId = $this->_request->getParam('website_id');
        if (empty($websiteId)) {
            $websites = $this->storeManager->getWebsites();
            foreach ($websites as $website) {
                $this->product->exportProducts($website->getId());
            }
            return;
        }
        $this->product->exportProducts($websiteId);
    }
}
