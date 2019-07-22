<?php

/**
 *
 *
 * @author teemingchew
 */

namespace Pixlee\Pixlee\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Widget\Button;

class Export extends \Magento\Config\Block\System\Config\Form\Field
{
    protected $_exportButtonLabel = "Export Products to Pixlee";

    public function __construct(
        \Magento\Backend\Helper\Data $adminhtmlData,
        \Pixlee\Pixlee\Helper\Data $pixleeData,
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Request\Http $request,
        array $data = []
    ) {
        $this->_adminhtmlData = $adminhtmlData;
        $this->_pixleeData  = $pixleeData;
        $this->_logger = $context->getLogger();
        $this->_storeManager = $storeManager;
        $this->websiteId = (int) $request->getParam('website');
        parent::__construct($context, $data);
    }

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('system/config/export_button.phtml');
    }

    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return $this->_toHtml();
    }

    public function getAjaxExportUrl()
    {
        return $this->getUrl('pixlee_export/product/export', [ 'website_id' => (string) $this->websiteId ]);
    }

    public function getAPIKey()
    {
        return $this->_pixleeData->getApiKey();
    }

    public function getDataHelper()
    {
        return $this->_pixleeData;
    }

    public function getButtonHtml()
    {
        $buttonData = [
            'id' => 'pixlee_export_button',
            'label' => __('Export Products to Pixlee'),
            'onclick' => 'javascript:exportToPixlee(\''.$this->getAjaxExportUrl().'\'); return false;'
        ];

        $this->_pixleeData->initializePixleeAPI($this->websiteId);
        if ($this->_pixleeData->isInactive()) {
            $buttonData['class'] = 'disabled';
        }

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
