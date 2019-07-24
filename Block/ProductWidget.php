<?php

namespace Pixlee\Pixlee\Block;

use \Magento\Catalog\Block\Product\AbstractProduct;

class ProductWidget extends AbstractProduct
{
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Pixlee\Pixlee\Helper\Data $pixleeData,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_pixleeData = $pixleeData;
    }

    public function getDataHelper()
    {
        return $this->_pixleeData;
    }
}
