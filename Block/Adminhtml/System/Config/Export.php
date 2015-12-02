<?php

/**
*
*
* @author teemingchew
*/

namespace Pixlee\Pixlee\Block\Adminhtml\System\Config;

class Export extends \Magento\Config\Block\System\Config\Form\Field
{
	protected $_exportButtonLabel = "Export Products to Pixlee";

	public function __construct(
		\Magento\Backend\Helper\Data $adminhtmlData,
		\Pixlee\Pixlee\Helper\Data $pixleeData,
		\Magento\Backend\Block\Template\Context $context,
        array $data = []
	) {
		$this->_adminhtmlData = $adminhtmlData;
		$this->_pixleeData  = $pixleeData;
		parent::__construct($context, $data);
	}

	// protected function _prepareLayout()
	// {
	// 	parent::_prepareLayout();
	// 	if(!$this->getTemplate()) {
	// 		$this->setTemplate('system/config/export_button.phtml')
	// 	}
	// 	return $this;
	// }

	// public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
	// {
	// 	$element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
	// 	return parent::render($element);
	// }

	// protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
	// {
	// 	$originalData = $element-> getOriginalData();
	// 	$buttonLabel = !empty($originalData['button_label']) ? $originalData['button_label'] : $this->_exportButtonLabel;
	// 	$this->addData(
	// 		[
	// 			'button_label' => __($buttonLabel),
	// 			'html_id' => $element->getHtmlId(),
	// 			'ajax_url'
	// 		]
	// 	);
	// }

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
		// return $this->_adminhtmlData->getUrl('pixlee_pixlee/export');
		return $this->getUrl('*/system_config_system_export/product');
	}

	public function getButtonHtml()
	{
		$buttonData = array(
			'id' => 'pixlee_export_button',
			'label' => __('Export Products to Pixlee'),
			'onclick' => 'javascript:exportToPixlee(\''.$this->getAjaxExportUrl().'\'); return false;'
		);

		if($this->_pixleeData->isInactive() || $this->_pixleeData->getUnexportedProducts()->count() == 0){
			$buttonData['class'] = 'disabled';
		}
		
		$button = $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Button')->setData($buttonData);

		return $button->toHtml();
	}

	public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        // Remove scope label
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }
}