<?php
/**
 * Copyright © Pixlee TurnTo, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Widget\Button;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\LocalizedException;

class Demo extends Field
{
    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('Pixlee_Pixlee::system/config/demo.phtml');
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    /**
     * Get endpoint url
     *
     * @return string
     */
    public function getRequestDemoUrl()
    {
        return 'https://app.pixlee.com/leads/add';
    }

    /**
     * Generate button html
     *
     * @return string
     * @throws LocalizedException
     */
    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock(Button::class)->setData(
            [
                'id' => 'pixlee_request_demo',
                'label' => __('Request Access'),
                'onclick' => 'javascript:requestPixleeDemo(\'' . $this->getRequestDemoUrl() . '\'); return false;'
            ]
        );

        return $button->toHtml();
    }

    /**
     * Remove scope from label
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }
}
