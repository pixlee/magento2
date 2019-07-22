<?php

namespace Pixlee\Pixlee\Block;

use \Magento\Catalog\Block\Category\View;

class Catalog extends View
{
    public function __construct(
        \Pixlee\Pixlee\Helper\Data $pixleeData,
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Catalog\Model\Layer\Resolver $layerResolver,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Helper\Category $categoryHelper,
        array $data = []
    ) {
        parent::__construct($context, $layerResolver, $registry, $categoryHelper, $data);
        $this->_pixleeData  = $pixleeData;
    }

    public function getDataHelper()
    {
        return $this->_pixleeData;
    }
}
