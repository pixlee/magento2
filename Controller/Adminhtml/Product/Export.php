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
use Pixlee\Pixlee\Model\Export\Product;

class Export extends Action
{
    /**
     * @var Product
     */
    protected $product;

    /**
     * Constructor
     *
     * @param Context $context
     * @param Product $product
     */
    public function __construct(
        Context $context,
        Product $product
    ) {
        parent::__construct($context);
        $this->product = $product;
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
        $this->product->exportProducts($websiteId);
    }
}
