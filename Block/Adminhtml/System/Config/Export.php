<?php
/**
 * Copyright © Emplifi, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Pixlee\Pixlee\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Button;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
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

    /**
     * @param Api $apiConfig
     * @param Http $request
     * @param Context $context
     * @param array $data
     */
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

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('system/config/export_button.phtml');
    }

    /**
     * @inheritdoc
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    /**
     * Get the export controller URL for the current website scope
     *
     * @return string
     */
    public function getAjaxExportUrl()
    {
        return $this->getUrl('pixlee_export/product/export', [ 'website_id' => $this->websiteId ]);
    }

    /**
     * Get the note shown below the export button
     *
     * @return Phrase
     */
    public function getPixleeRemainingText()
    {
        $scope = $this->apiConfig->getScope($this->websiteId);
        if ($this->apiConfig->isActive($scope['scopeType'], $scope['scopeCode'])) {
            return __('Export your products to Emplifi and start collecting photos.');
        }
        return __('Export products for current website to Emplifi');
    }

    /**
     * Render the export button, disabled when the extension is inactive for the scope
     *
     * @return string
     * @throws LocalizedException
     */
    public function getButtonHtml()
    {
        $buttonData = [
            'id' => 'pixlee_export_button',
            'label' => __('Export Products to Emplifi'),
            'data_attribute' => [
                'mage-init' => [
                    'Pixlee_Pixlee/js/export-button' => ['url' => $this->getAjaxExportUrl()],
                ],
            ],
        ];

        $scope = $this->apiConfig->getScope($this->websiteId);
        if (!$this->apiConfig->isActive($scope['scopeType'], $scope['scopeCode'])) {
            $buttonData['class'] = 'disabled';
        }

        $button = $this->getLayout()->createBlock(Button::class)->setData($buttonData);
        return $button->toHtml();
    }

    /**
     * @inheritdoc
     */
    public function render(AbstractElement $element)
    {
        // Remove scope label
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }
}
