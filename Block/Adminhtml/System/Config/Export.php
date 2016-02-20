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
		\Psr\Log\LoggerInterface $logger,
		array $data = []
	) {
		$this->_adminhtmlData = $adminhtmlData;
		$this->_pixleeData  = $pixleeData;
		$this->_logger = $logger;
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
		// return $this->_adminhtmlData->getUrl('pixlee_pixlee/export');
		return $this->getUrl('pixlee_export/product/export');
	}

	public function getAPIKey()
	{
	  return $this->_pixleeData->getApiKey();
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
