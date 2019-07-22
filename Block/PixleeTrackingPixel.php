<?php

namespace Pixlee\Pixlee\Block;

use Magento\Framework\View\Element\Template;

class PixleeTrackingPixel extends Template
{
    public function __construct(
        \Pixlee\Pixlee\Helper\Data $pixleeData,
        Template\Context $context,
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
