<?php
/**
 * Copyright © 2015 Pixlee
 * @author teemingchew
 */

namespace Pixlee\Pixlee\Controller\Adminhtml\Product;

use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\HTTP\Client\Curl;

class Export extends \Magento\Backend\App\Action
{
    protected $resultJsonFactory;
    protected $_pixleeData;
    protected $_logger;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        JsonFactory $resultJsonFactory,
        \Magento\Framework\App\Request\Http $request,
        \Pixlee\Pixlee\Helper\Data $pixleeData,
        \Pixlee\Pixlee\Helper\Logger\PixleeLogger $logger
    ) {
        parent::__construct($context);
        $this->resultJsonFactory  = $resultJsonFactory;
        $this->request            = $request;
        $this->_pixleeData        = $pixleeData;
        $this->_logger            = $logger;
        $this->_curl              = new Curl;
    }

    public function execute()
    {
        $url = $this->request->getRequestUri();
        preg_match("/pixlee_export\/product\/export\/website_id\/(.*)\/key\//", $url, $matches);
        $websiteId = (int) ($matches[1]);
        $this->_pixleeData->exportProducts($websiteId);
    }
}
