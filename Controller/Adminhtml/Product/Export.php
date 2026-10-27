<?php
/**
 * Copyright © Emplifi, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Controller\Adminhtml\Product;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Pixlee\Pixlee\Model\Export\Product;

class Export extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Pixlee_Pixlee::config_pixlee_pixlee';

    /**
     * @var Product
     */
    protected $product;
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

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
     * @return Json
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $websiteId = $this->_request->getParam('website_id');
        if (empty($websiteId)) {
            $websites = $this->storeManager->getWebsites();
            foreach ($websites as $website) {
                $this->product->exportProducts($website->getId());
            }
        } else {
            $this->product->exportProducts($websiteId);
        }

        /** @var Json $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        return $result->setData(['success' => true]);
    }
}
