<?php

namespace Pixlee\Pixlee\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Widget\Button;

class Demo extends \Magento\Config\Block\System\Config\Form\Field
{

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        array $data = []
    ) {
        $this->_logger = $context->getLogger();
        parent::__construct($context, $data);
    }

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('system/config/demo.phtml');
    }

    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return $this->_toHtml();
    }

    public function getRequestDemoUrl()
    {
        return "https://app.pixlee.com/leads/add";
    }

    public function getButtonHtml()
    {
        $buttonData = [
            'id' => 'pixlee_request_demo',
            'label' => __('Request Access'),
            'onclick' => 'javascript:requestPixleeDemo(\''.$this->getRequestDemoUrl().'\'); return false;'
        ];

        $button = $this->getLayout()->createBlock(Button::class)->setData($buttonData);
        return $button->toHtml();
    }

    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        // Remove scope label
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }
}
