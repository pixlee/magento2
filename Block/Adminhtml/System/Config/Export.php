<?php
/**
 * Copyright © Pixlee TurnTo, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Button;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Pixlee\Pixlee\Model\Config\Api;

class Export extends Field
{
    /**
     * @var Api
     */
    protected $apiConfig;
    /**
     * @var int
     */
    protected $websiteId;

    public function __construct(
        Api $apiConfig,
        Http $request,
        Context $context,
        array $data = []
    ) {
        $this->apiConfig = $apiConfig;
        $this->websiteId = $request->getParam('website');
        parent::__construct($context, $data);
    }

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('system/config/export_button.phtml');
    }

    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    public function getAjaxExportUrl()
    {
        return $this->getUrl('pixlee_export/product/export', [ 'website_id' => $this->websiteId ]);
    }

    public function getPixleeRemainingText()
    {
        $scope = $this->apiConfig->getScope($this->websiteId);
        if ($this->apiConfig->isActive($scope['scopeType'], $scope['scopeCode'])) {
            return 'Export your products to Pixlee and start collecting photos.';
        }
        return 'Export products for current website to Pixlee';
    }

    public function getButtonHtml()
    {
        $buttonData = [
            'id' => 'pixlee_export_button',
            'label' => __('Export Products to Pixlee'),
            'onclick' => 'javascript:exportToPixlee(\''.$this->getAjaxExportUrl().'\'); return false;'
        ];

        $scope = $this->apiConfig->getScope($this->websiteId);
        if (!$this->apiConfig->isActive($scope['scopeType'], $scope['scopeCode'])) {
            $buttonData['class'] = 'disabled';
        }

        $button = $this->getLayout()->createBlock(Button::class)->setData($buttonData);
        return $button->toHtml();
    }

    public function render(AbstractElement $element)
    {
        // Remove scope label
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }
}
